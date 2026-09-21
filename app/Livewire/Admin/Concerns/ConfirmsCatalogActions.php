<?php

namespace App\Livewire\Admin\Concerns;

use App\Services\Admin\Catalog\CatalogAdmin;
use App\Services\Admin\Catalog\CatalogItemLocked;
use Flux\Flux;

/**
 * Sospendi / riattiva / elimina con la modale di conferma del design, condivisa
 * da elenco e scheda. Una cancellazione bloccata da prenotazioni future apre la
 * stessa modale con l'avviso, e il pulsante diventa "Sospendi invece".
 */
trait ConfirmsCatalogActions
{
    /** @var array{action: string, family: string, id: int, title: string, body: string, blocked: ?string, confirm: string}|null */
    public ?array $confirming = null;

    public function askSuspend(string $family, int $id, CatalogAdmin $catalog): void
    {
        $item = $catalog->find($family, $id);
        $name = $catalog->name($item);
        $on = $item->suspended_at !== null;

        $this->confirming = [
            'action' => $on ? 'reactivate' : 'suspend',
            'family' => $family,
            'id' => $id,
            'title' => __($on ? 'admin-catalog.confirm.reactivate_title' : 'admin-catalog.confirm.suspend_title'),
            'body' => __($on ? 'admin-catalog.confirm.reactivate_body' : 'admin-catalog.confirm.suspend_body', ['name' => $name]),
            'blocked' => null,
            'confirm' => __($on ? 'admin-catalog.confirm.reactivate' : 'admin-catalog.confirm.suspend'),
        ];

        Flux::modal('catalog-confirm')->show();
    }

    public function askDelete(string $family, int $id, CatalogAdmin $catalog): void
    {
        $item = $catalog->find($family, $id);
        $blocker = $catalog->deletionBlocker($item);

        $this->confirming = [
            // Bloccata: l'unica azione utile rimasta è la sospensione.
            'action' => $blocker !== null ? 'suspend' : 'delete',
            'family' => $family,
            'id' => $id,
            'title' => __('admin-catalog.confirm.delete_title'),
            'body' => __('admin-catalog.confirm.delete_body', ['name' => $catalog->name($item)]),
            'blocked' => $blocker,
            'confirm' => __($blocker !== null ? 'admin-catalog.confirm.suspend_instead' : 'admin-catalog.confirm.delete'),
        ];

        Flux::modal('catalog-confirm')->show();
    }

    public function confirmAction(CatalogAdmin $catalog): void
    {
        if ($this->confirming === null) {
            return;
        }

        $item = $catalog->find($this->confirming['family'], $this->confirming['id']);
        $name = $catalog->name($item);

        try {
            match ($this->confirming['action']) {
                'suspend' => $catalog->suspend($item),
                'reactivate' => $catalog->reactivate($item),
                'delete' => $catalog->delete($item),
            };
        } catch (CatalogItemLocked $locked) {
            // Una prenotazione è arrivata fra l'apertura della modale e la conferma.
            $this->confirming['blocked'] = $locked->getMessage();
            $this->confirming['action'] = 'suspend';
            $this->confirming['confirm'] = __('admin-catalog.confirm.suspend_instead');

            return;
        }

        $message = __('admin-catalog.confirm.done_'.$this->confirming['action'], ['name' => $name]);
        $deleted = $this->confirming['action'] === 'delete';

        $this->confirming = null;
        Flux::modal('catalog-confirm')->close();
        Flux::toast(text: $message, variant: 'success');

        $this->afterCatalogAction($deleted);
    }

    /** Hook per chi usa il trait (la scheda torna all'elenco dopo un'eliminazione). */
    protected function afterCatalogAction(bool $deleted): void {}
}
