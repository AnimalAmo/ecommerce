<?php

namespace App\Console\Commands;

use App\Exceptions\PaymentModeException;
use App\Models\Event\Event;
use App\Models\Partner\PartnerProfile;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Services\Partner\PartnerPaymentModeService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Sposta un partner fra incasso online su AnimalAmo e pagamento diretto.
 *
 * Nasce da un caso di produzione del 26/09/2026: «Bio Boutique Hotel Laurino»
 * (utente 11) è a incasso online ma non ha mai collegato Stripe, quindi il
 * cliente arriva a un checkout che non può funzionare.
 *
 * Perché non una UPDATE a mano. La colonna è una sola, ma cambiarla di forza
 * salta {@see PartnerPaymentModeService::set()}, e con essa:
 *  - la validazione del link pubblico, che finisce in un href nelle pagine B2C
 *    e nelle mail (uno schema `javascript:` passerebbe);
 *  - il rilascio delle bozze rimaste in attesa di Stripe: passando a pagamento
 *    diretto il partner diventa pubblicabile, e i servizi che aspettavano da
 *    settimane vanno a catalogo;
 *  - il rifiuto del passaggio inverso, perché tornare online senza un conto
 *    operativo rende le schede invendibili.
 *
 * E soprattutto: **il flag è del partner, non della singola scheda.** Chi lo
 * cambia sposta tutte le schede di quel partner, quindi il comando le conta e
 * lo dice prima di chiedere conferma.
 */
class PaymentModeCommand extends Command
{
    protected $signature = 'animalamo:payment-mode
        {partner : Ragione sociale, email del titolare, o id utente (quello di /admin/users/{id})}
        {--offline : Passa al pagamento diretto: nessun incasso su AnimalAmo}
        {--online : Torna all\'incasso online su AnimalAmo (richiede Stripe operativo)}
        {--url= : Sito dove pagare o prenotare, mostrato sulla scheda. Omesso, si tiene quello attuale}
        {--force : Non chiedere conferma}';

    protected $description = 'Sposta un partner fra incasso online e pagamento diretto, contando le schede che tocca';

    public function handle(PartnerPaymentModeService $modes): int
    {
        $online = (bool) $this->option('online');
        $offline = (bool) $this->option('offline');

        if ($online === $offline) {
            $this->error('Serve una direzione sola: --offline oppure --online.');

            return self::FAILURE;
        }

        $profile = $this->resolve((string) $this->argument('partner'));

        if ($profile === null) {
            return self::FAILURE;
        }

        $this->describe($profile, $online);

        if (! $this->option('force') && ! $this->confirm($online
            ? 'Confermi il ritorno all\'incasso online?'
            : 'Confermi il passaggio a pagamento diretto?')) {
            $this->warn('Niente fatto.');

            return self::FAILURE;
        }

        // Omettere --url non deve cancellare il sito del partner: set()
        // riscrive payment_url con quello che riceve.
        $url = $this->option('url') ?? $profile->payment_url;

        try {
            $modes->set($profile, $online, $url === null ? null : (string) $url);
        } catch (PaymentModeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        } catch (ValidationException $exception) {
            $this->error(implode(' ', $exception->validator->errors()->all()));

            return self::FAILURE;
        }

        $this->info($online
            ? $profile->business_name.' incassa di nuovo online su AnimalAmo.'
            : $profile->business_name.' incassa direttamente: le sue schede non passano più dal checkout.');

        return self::SUCCESS;
    }

    /**
     * Un partner solo, o niente. L'ambiguità è un errore e non una scelta a
     * caso: cambiare il partner sbagliato gli spegne l'incasso in silenzio.
     */
    private function resolve(string $needle): ?PartnerProfile
    {
        $needle = trim($needle);

        if ($needle === '') {
            $this->error('Manca il partner.');

            return null;
        }

        // Un numero è l'id UTENTE, non quello del profilo: è l'unico dei due
        // che l'operatore vede (il pannello apre /admin/users/{id} e la
        // creazione scheda passa ?partner={id}, entrambi risolti su User), ed
        // è quello che il resto dell'applicazione usa come maniglia del
        // partner (`structures.user_id`, `cart_items.partner_user_id`). Quando
        // i due id divergono, leggerlo come id del profilo cambia la modalità
        // a un altro partner senza che nessuno se ne accorga.
        if (ctype_digit($needle)) {
            $profile = PartnerProfile::query()->where('user_id', (int) $needle)->first();

            if ($profile === null) {
                $this->error('L\'utente '.$needle.' non ha un profilo partner.');
            }

            return $profile;
        }

        if (str_contains($needle, '@')) {
            $profile = PartnerProfile::query()
                ->whereHas('user', fn ($query) => $query->where('email', $needle))
                ->first();

            if ($profile === null) {
                $this->error('Nessun partner con l\'email '.$needle.'.');
            }

            return $profile;
        }

        // Nome esatto prima: con due ragioni sociali dove una è il prefisso
        // dell'altra, il LIKE le prenderebbe entrambe e non si potrebbe più
        // nominare la prima.
        $exact = PartnerProfile::query()->where('business_name', $needle)->get();
        $matches = $exact->isNotEmpty()
            ? $exact
            : PartnerProfile::query()->where('business_name', 'like', '%'.$needle.'%')->get();

        if ($matches->isEmpty()) {
            $this->error('Nessun partner che si chiami «'.$needle.'».');

            return null;
        }

        if ($matches->count() > 1) {
            $this->error('«'.$needle.'» corrisponde a '.$matches->count().' partner: usa il nome esatto o l\'id utente.');
            $this->candidates($matches);

            return null;
        }

        return $matches->first();
    }

    /** @param  Collection<int, PartnerProfile>  $matches */
    private function candidates(Collection $matches): void
    {
        $this->table(
            // L'id utente e non quello del profilo: è quello che l'operatore
            // può ridare al comando, e quello che apre /admin/users/{id}.
            ['utente', 'Ragione sociale', 'Modalità'],
            $matches->map(fn (PartnerProfile $profile): array => [
                $profile->user_id,
                $profile->business_name,
                $profile->requiresOnlinePayment() ? 'online' : 'diretto',
            ])->all(),
        );
    }

    /** Quello che l'operatore deve sapere prima di premere invio. */
    private function describe(PartnerProfile $profile, bool $online): void
    {
        $structures = Structure::query()->where('user_id', $profile->user_id)->count();
        $events = Event::query()->where('user_id', $profile->user_id)->count();
        $smartboxes = SmartboxPackage::query()->where('user_id', $profile->user_id)->count();
        $total = $structures + $events + $smartboxes;

        $this->line($profile->business_name.' (profilo #'.$profile->id.', utente #'.$profile->user_id.')');
        $this->line('Modalità attuale: '.($profile->requiresOnlinePayment() ? 'incasso online su AnimalAmo' : 'pagamento diretto al partner'));
        $this->line('Stripe: '.($profile->canBePaid() ? 'operativo' : 'non operativo, non può essere bonificato'));
        $this->line('A catalogo: '.$structures.' strutture, '.$events.' eventi, '.$smartboxes.' smartbox — '.$total.' schede in tutto');
        $this->line('Nuova modalità: '.($online ? 'incasso online su AnimalAmo' : 'pagamento diretto al partner'));
    }
}
