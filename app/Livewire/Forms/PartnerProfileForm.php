<?php

namespace App\Livewire\Forms;

use App\Models\User;
use App\Support\Phone;
use App\Support\Translations;
use Illuminate\Validation\Rule;
use Livewire\Form;

/**
 * Profilo partner — "Informazioni personali". Dati personali (su users) +
 * anagrafica fiscale (su partner_profiles), come da mock XD, più gli orari di
 * apertura o disponibilità chiesti dalla cliente il 27/09/2026.
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

    /** Campi fiscali per l'update di partner_profiles. */
    public function toProfile(): array
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
        ];
    }

    private function userId(): ?int
    {
        return auth()->id();
    }
}
