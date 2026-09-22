<?php

namespace App\Jobs;

use App\Services\Partner\Publishing\AwaitingDraftPublisher;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Pubblica le bozze in attesa di un partner appena diventato pubblicabile.
 * In coda e non inline: parte dal webhook account.updated (che deve
 * rispondere subito) e dal mount della pagina pagamento.
 *
 * Unico per partner: Stripe può mandare più account.updated insieme. Il
 * lucchetto scade dopo dieci minuti, così un worker morto non blocca il
 * partner per sempre (lo recupera comunque il comando schedulato). Parte
 * dopo il commit: chi lo lancia può essere dentro una transazione il cui
 * update dei flag Stripe non è ancora visibile.
 */
class PublishAwaitingDrafts implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $uniqueFor = 600;

    public function __construct(public int $partnerId)
    {
        // Metodo e non proprietà: Queueable dichiara già `public $afterCommit`,
        // e ridichiararla con un valore è un conflitto fatale col trait.
        $this->afterCommit();
    }

    public function uniqueId(): string
    {
        return (string) $this->partnerId;
    }

    public function handle(AwaitingDraftPublisher $publisher): void
    {
        $publisher->publishFor($this->partnerId);
    }
}
