<?php

namespace App\Services\Admin\People;

use App\Models\ContactMessage\ContactMessage;
use App\Models\Partner\PartnerApplication;
use App\Services\Partner\SendPartnerInvitation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * "Contatti e candidature": quello che arriva dal modulo Contattaci e da
 * "Lavora con noi", con lo stato di lavorazione del pannello.
 *
 * "Da lavorare" = né lavorato né archiviato: è la stessa definizione dei
 * badge di navigazione (AdminCounters::openMessages / openApplications).
 */
class InboxService
{
    public const TABS = ['messages', 'applications'];

    public const FILTERS = ['open', 'all', 'archived'];

    public function __construct(private readonly SendPartnerInvitation $invitation) {}

    /** @return Builder<ContactMessage>|Builder<PartnerApplication> */
    public function query(string $tab, string $filter): Builder
    {
        $query = $this->model($tab)::query();

        match ($filter) {
            'open' => $query->whereNull('handled_at')->whereNull('archived_at'),
            'archived' => $query->whereNotNull('archived_at'),
            default => null,
        };

        return $query->latest('created_at')->latest('id');
    }

    /** @return array<string, int> Elementi da lavorare per scheda. */
    public function openCounts(): array
    {
        return collect(self::TABS)
            ->mapWithKeys(fn (string $tab): array => [$tab => $this->query($tab, 'open')->count()])
            ->all();
    }

    public function find(string $tab, int $id): ContactMessage|PartnerApplication|null
    {
        return $this->model($tab)::query()->find($id);
    }

    public function setHandled(ContactMessage|PartnerApplication $item, bool $handled): void
    {
        $item->forceFill(['handled_at' => $handled ? now() : null])->save();
    }

    public function setArchived(ContactMessage|PartnerApplication $item, bool $archived): void
    {
        $item->forceFill(['archived_at' => $archived ? now() : null])->save();
    }

    /** Cancellazione definitiva (anche per le richieste di cancellazione dei dati). */
    public function delete(ContactMessage|PartnerApplication $item): void
    {
        $item->delete();
    }

    /**
     * (Re)invia l'invito all'iscrizione partner con lo stesso service del
     * modulo "Lavora con noi": stesso link firmato, stessa mail, stesso stato.
     */
    public function invite(PartnerApplication $application): void
    {
        if ($application->status === PartnerApplication::STATUS_REGISTERED) {
            throw new RuntimeException(__('admin-people.inbox.already_registered'));
        }

        $this->invitation->send($application);
    }

    /** @return class-string<Model> */
    private function model(string $tab): string
    {
        return $tab === 'applications' ? PartnerApplication::class : ContactMessage::class;
    }
}
