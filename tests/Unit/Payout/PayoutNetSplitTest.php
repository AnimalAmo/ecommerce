<?php

namespace Tests\Unit\Payout;

use App\Support\PayoutNetSplit;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

/**
 * L'invariante è uno solo e non ammette eccezioni: la somma dei netti deve
 * fare esattamente il netto accreditato. Un centesimo in più e il bonifico
 * chiede più di quanto il saldo del partner contiene.
 */
class PayoutNetSplitTest extends TestCase
{
    public function test_ripartisce_in_proporzione_al_lordo(): void
    {
        $split = PayoutNetSplit::across($this->rows([6000, 4000]), 9800, 10000);

        $this->assertSame([1 => 5880, 2 => 3920], $split);
        $this->assertSame(9800, array_sum($split));
    }

    public function test_il_resto_dell_arrotondamento_va_all_ultima_riga(): void
    {
        $split = PayoutNetSplit::across($this->rows([3333, 3333, 3334]), 9799, 10000);

        $this->assertSame(9799, array_sum($split));
        $this->assertGreaterThan($split[1], $split[3]);
    }

    public function test_una_riga_sola_prende_tutto_il_netto(): void
    {
        $this->assertSame([1 => 10595], PayoutNetSplit::across($this->rows([12000]), 10595, 12000));
    }

    public function test_un_totale_lordo_a_zero_non_divide_per_zero(): void
    {
        $split = PayoutNetSplit::across($this->rows([0, 0]), 0, 0);

        $this->assertSame(0, array_sum($split));
    }

    /** @param  list<int>  $grossi */
    private function rows(array $grossi): Collection
    {
        return collect($grossi)->map(fn (int $gross, int $i): object => (object) [
            'id' => $i + 1,
            'gross_cents' => $gross,
        ]);
    }
}
