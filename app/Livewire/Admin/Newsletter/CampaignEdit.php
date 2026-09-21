<?php

namespace App\Livewire\Admin\Newsletter;

use App\Mail\Newsletter\NewsletterCampaignMail;
use App\Models\Newsletter\NewsletterCampaign;
use App\Services\Admin\Newsletter\NewsletterAdmin;
use App\Services\Newsletter\CampaignSender;
use App\Services\Newsletter\DmarcChecker;
use App\Services\Newsletter\Exceptions\CampaignNotLaunchable;
use App\Services\Newsletter\HtmlSanitizer;
use Flux\Flux;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Throwable;

/**
 * "Scrivi una newsletter": testo in italiano e inglese, pubblico e ritmo,
 * anteprima, invio di prova, "Invia a tutti". Una campagna già partita si
 * apre in sola lettura, con l'avanzamento dell'invio.
 *
 * Adattatore sottile: la spedizione è di CampaignSender, la checklist di
 * NewsletterAdmin, la pulizia dell'HTML di HtmlSanitizer.
 */
class CampaignEdit extends Component
{
    private const LOCALES = ['it', 'en'];

    #[Locked]
    public ?int $campaignId = null;

    /** @var array{it: string, en: string} */
    public array $subject = ['it' => '', 'en' => ''];

    /** @var array{it: string, en: string} */
    public array $preheader = ['it' => '', 'en' => ''];

    /** @var array{it: string, en: string} */
    public array $body = ['it' => '', 'en' => ''];

    public string $audience = NewsletterCampaign::AUDIENCE_ALL;

    public int $hourlyRate = 200;

    /** Scheda di lingua aperta nell'editor. */
    public string $tab = 'it';

    public string $testEmail = '';

    public string $testLocale = 'it';

    /** Lingua dell'anteprima aperta, null = modale chiusa. */
    public ?string $previewLocale = null;

    /** Record DMARC: null finché il browser non lo chiede (checkDmarc). */
    #[Locked]
    public ?bool $dmarc = null;

    public function mount(CampaignSender $sender, ?NewsletterCampaign $campaign = null): void
    {
        $this->hourlyRate = $sender->rateOptions()[0] ?? 0;
        $this->testEmail = (string) auth()->user()?->email;

        if ($campaign === null) {
            return;
        }

        $this->campaignId = $campaign->getKey();
        $this->audience = $campaign->audience;
        $this->hourlyRate = $campaign->hourly_rate;

        foreach (self::LOCALES as $locale) {
            $this->subject[$locale] = (string) $campaign->getTranslation('subject', $locale, false);
            $this->preheader[$locale] = (string) $campaign->getTranslation('preheader', $locale, false);
            $this->body[$locale] = (string) $campaign->getTranslation('body', $locale, false);
        }
    }

    public function save(): void
    {
        $this->persist();

        Flux::toast(text: __('admin-newsletter.editor.saved'), variant: 'success');
    }

    public function openPreview(string $locale): void
    {
        $this->previewLocale = in_array($locale, self::LOCALES, true) ? $locale : 'it';

        Flux::modal('newsletter-preview')->show();
    }

    public function openTest(): void
    {
        $this->testLocale = $this->tab;
        $this->resetErrorBag(['testEmail', 'testLocale']);

        Flux::modal('newsletter-test')->show();
    }

    public function sendTest(CampaignSender $sender): void
    {
        $this->validate([
            'testEmail' => ['required', 'email', 'max:191'],
            'testLocale' => ['required', Rule::in(self::LOCALES)],
        ]);

        $campaign = $this->persist();

        try {
            $sender->sendTest($campaign, $this->testEmail, $this->testLocale);
        } catch (Throwable $exception) {
            Log::warning('Prova newsletter non partita', ['campaign' => $campaign->getKey(), 'error' => $exception->getMessage()]);
            $this->addError('testEmail', __('admin-newsletter.errors.test_failed'));

            return;
        }

        Flux::modal('newsletter-test')->close();
        Flux::toast(text: __('admin-newsletter.editor.test_sent', ['email' => $this->testEmail]), variant: 'success');
    }

    public function askLaunch(CampaignSender $sender): void
    {
        $campaign = $this->persist();

        if (($blocker = $sender->launchBlocker($campaign)) !== null) {
            Flux::toast(text: $blocker, variant: 'danger');

            return;
        }

        Flux::modal('newsletter-launch')->show();
    }

    public function launch(CampaignSender $sender): void
    {
        $campaign = $this->campaign();

        if ($campaign === null) {
            return;
        }

        try {
            $sender->launch($campaign);
        } catch (CampaignNotLaunchable $refused) {
            Flux::modal('newsletter-launch')->close();
            Flux::toast(text: $refused->getMessage(), variant: 'danger');

            return;
        }

        Flux::toast(text: __('admin-newsletter.editor.launched'), variant: 'success');
        $this->redirectRoute('admin.newsletter.edit', $campaign, navigate: true);
    }

    /** "Riprendi l'invio" su una campagna ferma: stesso percorso di newsletter:resume. */
    public function resume(CampaignSender $sender): void
    {
        $campaign = $this->campaign();

        if ($campaign === null || $campaign->status !== NewsletterCampaign::STATUS_SENDING || $sender->looksAlive($campaign)) {
            return;
        }

        try {
            $sender->resume($campaign);
        } catch (CampaignNotLaunchable $refused) {
            Flux::toast(text: $refused->getMessage(), variant: 'danger');

            return;
        }

        Flux::toast(text: __('admin-newsletter.editor.resumed'), variant: 'success');
    }

    /** Chiamata dal browser dopo il caricamento: è una query DNS, non sta nel render. */
    public function checkDmarc(DmarcChecker $checker): void
    {
        $this->dmarc = $checker->hasRecord();
    }

    public function render(NewsletterAdmin $admin, CampaignSender $sender, DmarcChecker $checker)
    {
        $campaign = $this->campaign();
        $draft = $this->draft();
        $counts = [
            NewsletterCampaign::AUDIENCE_ALL => $sender->audienceCount(NewsletterCampaign::AUDIENCE_ALL),
            NewsletterCampaign::AUDIENCE_IT => $sender->audienceCount(NewsletterCampaign::AUDIENCE_IT),
            NewsletterCampaign::AUDIENCE_EN => $sender->audienceCount(NewsletterCampaign::AUDIENCE_EN),
        ];
        $readOnly = $campaign !== null && ! $campaign->isDraft();

        return view('livewire.admin.newsletter.campaign-edit', [
            'campaign' => $campaign,
            'readOnly' => $readOnly,
            'counts' => $counts,
            'rates' => $sender->rateOptions(),
            'effectiveRate' => $sender->effectiveRate($this->hourlyRate),
            'duration' => $admin->duration($sender->estimatedMinutes($counts[$this->audience] ?? 0, $this->hourlyRate)),
            'checklist' => $readOnly ? [] : $admin->checklist($campaign, $draft, $this->dmarc, $checker->domain()),
            'stalled' => $campaign?->status === NewsletterCampaign::STATUS_SENDING && ! $sender->looksAlive($campaign),
            'lastActivity' => $campaign?->status === NewsletterCampaign::STATUS_SENDING ? $sender->lastActivity($campaign) : null,
            'previewHtml' => $this->previewLocale !== null
                ? (new NewsletterCampaignMail($readOnly ? $campaign : $draft, $this->previewLocale, preview: true))->render()
                : null,
        ])
            ->layout('layouts::admin')
            ->title($campaign === null ? __('admin-newsletter.editor.title_new') : __('admin-newsletter.editor.title'));
    }

    private function campaign(): ?NewsletterCampaign
    {
        return $this->campaignId === null ? null : NewsletterCampaign::find($this->campaignId);
    }

    /**
     * La campagna com'è nell'editor, non salvata: anteprima e checklist
     * seguono quello che si sta scrivendo.
     */
    private function draft(): NewsletterCampaign
    {
        $draft = new NewsletterCampaign([
            'audience' => $this->audience,
            'hourly_rate' => $this->hourlyRate,
        ]);

        foreach (['subject', 'preheader', 'body'] as $field) {
            $draft->replaceTranslations($field, $this->translations($field));
        }

        return $draft;
    }

    /** @return array<string, string> le lingue compilate, il testo già ripulito */
    private function translations(string $field): array
    {
        $values = [];

        foreach (self::LOCALES as $locale) {
            $value = $field === 'body'
                ? HtmlSanitizer::clean($this->body[$locale] ?? '')
                : trim((string) ($this->{$field}[$locale] ?? ''));

            if ($value !== '') {
                $values[$locale] = $value;
            }
        }

        return $values;
    }

    /**
     * Salva la bozza (la crea alla prima azione). Una campagna partita non si
     * tocca più.
     */
    private function persist(): NewsletterCampaign
    {
        $campaign = $this->campaign();

        abort_if($campaign !== null && ! $campaign->isDraft(), 403);

        foreach (self::LOCALES as $locale) {
            $this->body[$locale] = HtmlSanitizer::clean($this->body[$locale] ?? '');
        }

        $this->validate();

        $campaign ??= new NewsletterCampaign(['created_by' => auth()->id()]);
        $campaign->fill([
            'audience' => $this->audience,
            'hourly_rate' => $this->hourlyRate,
        ]);

        foreach (['subject', 'preheader', 'body'] as $field) {
            $campaign->replaceTranslations($field, $this->translations($field));
        }

        $created = ! $campaign->exists;
        $campaign->save();

        if ($created) {
            $this->campaignId = $campaign->getKey();
            // La pagina resta questa, ma un ricarica deve riaprire la bozza.
            $this->js('window.history.replaceState(null, "", '.json_encode(route('admin.newsletter.edit', $campaign)).')');
        }

        return $campaign;
    }

    protected function rules(): array
    {
        return [
            'subject.it' => ['required', 'string', 'max:200'],
            'subject.en' => ['nullable', 'string', 'max:200'],
            'preheader.it' => ['nullable', 'string', 'max:200'],
            'preheader.en' => ['nullable', 'string', 'max:200'],
            'body.it' => ['required', 'string', 'max:65000'],
            'body.en' => ['nullable', 'string', 'max:65000'],
            'audience' => ['required', Rule::in([NewsletterCampaign::AUDIENCE_ALL, NewsletterCampaign::AUDIENCE_IT, NewsletterCampaign::AUDIENCE_EN])],
            'hourlyRate' => ['required', 'integer', Rule::in(app(CampaignSender::class)->rateOptions())],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'subject.it' => __('admin-newsletter.editor.attributes.subject_it'),
            'subject.en' => __('admin-newsletter.editor.attributes.subject_en'),
            'preheader.it' => __('admin-newsletter.editor.attributes.preheader_it'),
            'preheader.en' => __('admin-newsletter.editor.attributes.preheader_en'),
            'body.it' => __('admin-newsletter.editor.attributes.body_it'),
            'body.en' => __('admin-newsletter.editor.attributes.body_en'),
            'audience' => __('admin-newsletter.editor.audience'),
            'hourlyRate' => __('admin-newsletter.editor.rate'),
            'testEmail' => __('admin-newsletter.editor.test_email'),
            'testLocale' => __('admin-newsletter.editor.test_locale'),
        ];
    }
}
