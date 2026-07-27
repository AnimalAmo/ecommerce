# Input telefono con prefisso internazionale

Data: 2026-07-27

## Problema

I sei campi telefono della piattaforma sono `flux:input type="tel"` con regola
`['required', 'string', 'max:30|32']`: accettano qualunque stringa. A DB
convivono formati incompatibili (`3331234567`, `340 5738920`, `+39 333 1234567`)
e non esiste il prefisso internazionale, che serve per i partner esteri e per
qualunque futuro invio di SMS/WhatsApp.

## Soluzione

Un componente Blade riusabile — select del prefisso + input del numero — con
validazione e normalizzazione E.164 lato server via `propaganistas/laravel-phone`.

### Pacchetto

`propaganistas/laravel-phone:^6.0` — richiede `illuminate/* ^11|^12|^13` e PHP
`^8.2`, quindi compatibile con Laravel 13 / PHP 8.3. Espone la regola
`Rule::phone()` e l'helper `phone()`, entrambi basati su libphonenumber di Google
(variante *lite*: dati di validazione senza le classi geocoder/carrier).

### `App\Support\Phone`

Unico punto in cui il progetto parla con il pacchetto:

| Metodo | Scopo |
| --- | --- |
| `toE164(?string, string $country = 'IT'): ?string` | normalizza; se il numero non è parsabile restituisce il valore originale (non si perdono dati) |
| `format(?string): string` | formato di lettura `+39 333 123 4567` |
| `split(?string): array{country,national}` | idrata il componente da un E.164 esistente |
| `countries(): array` | lista per il select, gruppo prioritario in cima |
| `dialCodes(): array` | mappa ISO → prefisso, usata da Alpine per ricomporre il numero |

`split()` ricava la parte nazionale dal formato internazionale meno il prefisso,
non da `formatNational()`: quest'ultimo reintroduce il *trunk prefix* (lo `0` di
`06 12 34 56 78` in Francia) che in E.164 non deve comparire.

### Componente

```blade
<x-phone-input model="form.phone" :value="$form->phone" :input-class="$inputClass" />
```

- solo Flux: `flux:select` in variante default (è un `<select>` nativo, quindi
  compatibile con `x-model` e su mobile apre il picker di sistema) + `flux:input type="tel"`;
- opzioni etichettate `🇮🇹 IT +39` — leggibili anche dove le flag emoji non
  renderizzano (Windows) e non ambigue tra paesi che condividono il prefisso
  (`+1` US/CA); nome esteso del paese nel `title`;
- Alpine tiene `country` e `national` e scrive la property Livewire con
  `$wire.set(model, '+39…', live)`. `live` è `false` di default: il checkout, che
  mostra la spunta ciano sui campi pieni, lo passa a `true`;
- se l'utente incolla un numero che inizia per `+`, viene usato così com'è;
- gli zeri iniziali non vengono rimossi: per i fissi italiani (`+39 06 …`) sono
  cifre significative;
- il wrapper è `wire:ignore` con `wire:key`: il sottoalbero è di proprietà di
  Alpine e il morph di Livewire non deve toccarlo;
- ogni pagina continua a passare le proprie classi XD (`inputClass`, `selectClass`).

### Validazione

Le sei regole diventano `['required', 'string', Rule::phone()->international()]`.
Nessun vincolo mobile-vs-fisso: rifiutare un fisso valido genererebbe supporto
clienti e non c'è ancora invio di SMS. Messaggio `validation.phone` in `it` e `en`.

### Normalizzazione

Mutator `phone` su `User`, `Order` e `PartnerApplication`: qualunque percorso di
scrittura (form, factory, seeder, futuro superadmin) produce E.164 senza
duplicare la logica nei form.

### Migrazione dati

`normalize_phone_numbers_to_e164`: chunk su `users`, `orders`,
`partner_applications`; le righe non parsabili restano invariate. `down()` è un
no-op: il formato originale non è ricostruibile e quello E.164 resta valido.

### Punti di sostituzione

| Blade | Form |
| --- | --- |
| `livewire/auth/register-modal.blade.php` | `RegisterForm` |
| `livewire/commerce/checkout.blade.php` | `Checkout` |
| `livewire/profile/profile.blade.php` | `Profile` |
| `livewire/partner/profile/info.blade.php` | `PartnerProfileForm` |
| `livewire/partner/registration/register-step1.blade.php` | `PartnerRegistrationForm` |
| `livewire/partner/registration/work-with-us.blade.php` | `PartnerApplicationForm` |

In `checkout` e `profile` il campo vive dentro un `foreach` di campi generici:
il telefono diventa un caso a parte fuori dal ciclo.

## Test

- unit su `App\Support\Phone` (normalizzazione, split, fallback sui non parsabili);
- feature sul componente (rende select e input, rifiuta numeri non validi);
- aggiornamento delle asserzioni esistenti al formato E.164 in
  `CheckoutPaymentTest`, `PlaceOrderActionTest`, `ProfileUpdateTest`,
  `WorkWithUsFlowTest`, `PartnerBookingDetailTest`.

## Fuori perimetro

Verifica del numero via OTP, invio SMS, formattazione live mentre si digita.
