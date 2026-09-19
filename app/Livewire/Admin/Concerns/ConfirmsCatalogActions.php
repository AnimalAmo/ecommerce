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
            'title' => $on ? 'Riattivare questa scheda?' : 'Sospendere questa scheda?',
            'body' => $on
                ? "«{$name}» torna visibile nel catalogo e ricomincia a ricevere prenotazioni."
                : "«{$name}» sparisce dal sito e dalle ricerche. Carrelli e preferiti restano intatti, gli ordini già fatti si leggono come prima. La riattivi quando vuoi.",
            'blocked' => null,
            'confirm' => $on ? 'Riattiva' : 'Sospendi',
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
            'title' => 'Cancellare definitivamente?',
            'body' => '«'.$catalog->name($item)."» verrà rimossa dal database, insieme a preferiti, carrelli e recensioni che la riguardano. Gli ordini già fatti restano leggibili. L'operazione non si può annullare.",
            'blocked' => $blocker,
            'confirm' => $blocker !== null ? 'Sospendi invece' : 'Elimina',
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
            $this->confirming['confirm'] = 'Sospendi invece';

            return;
        }

        $message = match ($this->confirming['action']) {
            'suspend' => "«{$name}» è sospesa.",
            'reactivate' => "«{$name}» è di nuovo online.",
            'delete' => "«{$name}» è stata eliminata.",
        };
        $deleted = $this->confirming['action'] === 'delete';

        $this->confirming = null;
        Flux::modal('catalog-confirm')->close();
        Flux::toast(text: $message, variant: 'success');

        $this->afterCatalogAction($deleted);
    }

    /** Hook per chi usa il trait (la scheda torna all'elenco dopo un'eliminazione). */
    protected function afterCatalogAction(bool $deleted): void {}
}
