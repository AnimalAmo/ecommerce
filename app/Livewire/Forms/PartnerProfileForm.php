<?php

namespace App\Livewire\Forms;

use App\Models\User;
use App\Services\Partner\PartnerPaymentModeService;
use App\Support\Phone;
use App\Support\Translations;
use DateTimeInterface;
use Illuminate\Validation\Rule;
use Livewire\Form;

/**
 * Profilo partner — "Informazioni personali". Dati personali (su users) +
 * anagrafica fiscale (su partner_profiles), come da mock XD, più gli orari di
 * apertura o disponibilità chiesti dalla cliente il 27/09/2026 e i recapiti
 * pubblici chiesti il 26/09/2026 (punto 6).
 */
class PartnerProfileForm extends Form
{
    // Personali (users)
    public string $firstName = '';

    public string $lastName = '';

    public string $email = '';

    public string $phone = '';

    // Fiscali (partner_profiles)
    public string $businessName = '';

    public string $address = '';

    public string $city = '';

    public string $province = '';

    public string $zip = '';

    public string $vat = '';

    public string $taxCode = '';

    /**
     * Orari di apertura o disponibilità (richiesta della cliente, 27/09/2026:
     * fra i recapiti pubblici, e al punto 3 «eventuali orari di
     * apertura/disponibilità» al posto delle date sulla scheda dell'attività).
     *
     * Un campo solo e sul partner, non sulla bozza: la cliente li nomina in due
     * punti e due campi che possono contraddirsi sono peggio di uno. Testo
     * libero localizzato it/en (spatie), facoltativo in entrambe le lingue,
     * nella stessa forma dei testi del wizard ({@see ActivityLocationForm}).
     */
    public array $openingHours = ['it' => '', 'en' => ''];

    /*
     * Recapiti pubblici (risposta della cliente, 26/09/2026, punto 6): voci
     * nuove, tutte facoltative, mai il telefono o l'email qui sopra né la sede
     * legale. Sul profilo e non sulla struttura (decisione di Matteo,
     * 27/09/2026). Compaiono sulle schede solo con la spunta di consenso.
     */
    public string $publicPhone = '';

    public string $publicWhatsapp = '';

    public string $publicEmail = '';

    public string $publicWebsite = '';

    public string $publicAddress = '';

    public bool $publicContactsConsent = false;

    public function rules(): array
    {
        return [
            'firstName' => ['required', 'string', 'max:64'],
            'lastName' => ['required', 'string', 'max:64'],
            'email' => ['required', 'email', 'max:128', Rule::unique('users', 'email')->ignore($this->userId())],
            'phone' => ['required', ...Phone::rules()],
            'businessName' => ['required', 'string', 'max:128'],
            'address' => ['required', 'string', 'max:128'],
            'city' => ['required', 'string', 'max:64'],
            'province' => ['required', 'string', 'max:64'],
            'zip' => ['required', 'digits:5'],
            'vat' => ['required', 'string', 'max:13'],
            'taxCode' => ['required', 'string', 'max:16'],
            // Facoltativi in tutte due le lingue: la cliente li chiama
            // «eventuali». `max:200` non è un numero nuovo — è il tetto che il
            // repo dà già ai testi liberi localizzati del partner
            // (`additionalOther` in HotelServicesForm e ActivityIncludedForm).
            // I 128 dei campi qui sopra valgono per dati strutturati (ragione
            // sociale, indirizzo), non per una riga di orari con più fasce.
            'openingHours.it' => ['nullable', 'string', 'max:200'],
            'openingHours.en' => ['nullable', 'string', 'max:200'],
            // Tutti facoltativi: la cliente li vuole a scelta del partner. Sito
            // con le regole del link di pagamento (stesso tipo di dato, stesso
            // href su una pagina pubblica); indirizzo col tetto dei testi
            // liberi del partner, come gli orari.
            'publicPhone' => ['nullable', ...Phone::rules()],
            'publicWhatsapp' => ['nullable', ...Phone::rules()],
            // `email:filter` e non `email` (RFC): è lo stesso FILTER_VALIDATE_EMAIL
            // con cui PartnerContacts ripulisce l'indirizzo prima di pubblicarlo.
            // Con la regola RFC il form salvava «info@caffè.it» e la card lo
            // scartava: il partner leggeva «Salvato» e l'email non usciva mai.
            'publicEmail' => ['nullable', 'email:filter', 'max:128'],
            'publicWebsite' => PartnerPaymentModeService::PAYMENT_URL_RULES,
            'publicAddress' => ['nullable', 'string', 'max:200'],
            'publicContactsConsent' => ['boolean'],
        ];
    }

    public function setFromUser(User $user): void
    {
        $this->firstName = $user->first_name ?? '';
        $this->lastName = $user->last_name ?? '';
        $this->email = $user->email;
        $this->phone = $user->phone ?? '';

        $profile = $user->partnerProfile;
        $this->businessName = $profile->business_name ?? '';
        $this->address = $profile->address ?? '';
        $this->city = $profile->city ?? '';
        $this->province = $profile->province ?? '';
        $this->zip = $profile->zip ?? '';
        $this->vat = $profile->vat ?? '';
        $this->taxCode = $profile->tax_code ?? '';
        // Idioma preso da ActivityLocationForm::setFromDraft() (meeting_point):
        // array_merge sui default, così una lingua mai scritta resta ''.
        // `?->` e non `->` come le righe qui sopra: chi non ha ancora un profilo
        // (lo crea il primo salvataggio) sopravvive a una lettura di proprietà,
        // non a una chiamata di metodo.
        $this->openingHours = array_merge(
            ['it' => '', 'en' => ''],
            $profile?->getTranslations('opening_hours') ?? [],
        );

        $this->publicPhone = $profile?->public_phone ?? '';
        $this->publicWhatsapp = $profile?->public_whatsapp ?? '';
        $this->publicEmail = $profile?->public_email ?? '';
        $this->publicWebsite = $profile?->public_website ?? '';
        $this->publicAddress = $profile?->public_address ?? '';
        $this->publicContactsConsent = $profile?->publishesContacts() ?? false;
    }

    /** Campi personali per l'update di users. */
    public function toUser(): array
    {
        return [
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'email' => $this->email,
            'phone' => $this->phone,
        ];
    }

    /**
     * Campi fiscali, orari e recapiti pubblici per l'update di partner_profiles.
     *
     * `$consentedAt` è la data del consenso già salvata sul profilo: con la
     * spunta ancora data resta quella, perché la data dice quando il partner
     * ha acconsentito la prima volta, non quando ha salvato l'ultima.
     */
    public function toProfile(?DateTimeInterface $consentedAt = null): array
    {
        return [
            'business_name' => $this->businessName,
            'address' => $this->address,
            'city' => $this->city,
            'province' => $this->province,
            'zip' => $this->zip,
            'vat' => $this->vat,
            'tax_code' => $this->taxCode,
            // Una lingua lasciata vuota va a null, non viene fatta cadere:
            // updateOrCreate() la fonde sul profilo esistente, e senza la
            // chiave gli orari inglesi salvati una volta restavano per sempre
            // sulla scheda /en (difetto W5, 28/09/2026 — vedi
            // App\Support\Translations). Su EN scatta il fallback IT.
            'opening_hours' => Translations::replacing($this->openingHours),
            // Un campo svuotato va a null, non a '': «voce non data» si scrive
            // in un modo solo, e updateOrCreate() cancella la copia vecchia.
            // Telefoni in E.164 come quello personale: è la forma che serve ai
            // link tel: e wa.me delle schede.
            'public_phone' => Phone::toE164($this->publicPhone),
            'public_whatsapp' => Phone::toE164($this->publicWhatsapp),
            'public_email' => $this->nullIfBlank($this->publicEmail),
            'public_website' => $this->nullIfBlank($this->publicWebsite),
            'public_address' => $this->nullIfBlank($this->publicAddress),
            // Spunta tolta → null: i recapiti restano salvati ma spariscono
            // dalle schede, e un nuovo consenso riparte con la sua data.
            'public_contacts_consent_at' => $this->publicContactsConsent ? ($consentedAt ?? now()) : null,
        ];
    }

    private function nullIfBlank(string $value): ?string
    {
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function userId(): ?int
    {
        return auth()->id();
    }
}
