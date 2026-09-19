<?php

namespace App\Livewire\Admin\People;

use App\Models\User;
use App\Services\Admin\People\AnonymizeUser;
use App\Services\Admin\People\UserDirectory;
use Flux\Flux;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Iscritti: elenco degli account (clienti e partner) con filtri, ordinamento
 * e "Cancella su richiesta". Filtri in query string: sono gli stessi che
 * l'export in Excel rilegge.
 */
class UserIndex extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $q = '';

    #[Url(except: 'all')]
    public string $newsletter = 'all';

    #[Url(except: 'all')]
    public string $status = 'all';

    #[Url(except: 'always')]
    public string $period = 'always';

    #[Url(except: 'all')]
    public string $role = 'all';

    #[Url(except: 'created_at')]
    public string $sort = 'created_at';

    #[Url(except: 'desc')]
    public string $dir = 'desc';

    /** Utente nella modale "Cancella su richiesta". */
    public ?int $anonymizingId = null;

    public function updated(string $property): void
    {
        if (in_array($property, ['q', 'newsletter', 'status', 'period', 'role'], true)) {
            $this->resetPage();
        }
    }

    public function sortBy(string $column): void
    {
        if (! in_array($column, UserDirectory::SORTS, true)) {
            return;
        }

        if ($this->sort === $column) {
            $this->dir = $this->dir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $column;
            $this->dir = $column === 'name' ? 'asc' : 'desc';
        }

        $this->resetPage();
    }

    public function askAnonymize(int $id): void
    {
        $this->anonymizingId = $id;

        Flux::modal('anonymize-user')->show();
    }

    public function anonymize(AnonymizeUser $service): void
    {
        $user = $this->anonymizingUser();

        if ($user === null) {
            return;
        }

        if (($reason = $service->blockReason($user)) !== null) {
            Flux::toast(text: $reason, variant: 'danger');

            return;
        }

        $service->handle($user);

        $this->anonymizingId = null;
        Flux::modal('anonymize-user')->close();
        Flux::toast(text: 'Dati personali cancellati. Gli ordini restano in archivio.', variant: 'success');
    }

    /** @return array<string, string> */
    public function filters(): array
    {
        return app(UserDirectory::class)->normalize([
            'q' => $this->q,
            'newsletter' => $this->newsletter,
            'status' => $this->status,
            'period' => $this->period,
            'role' => $this->role,
            'sort' => $this->sort,
            'dir' => $this->dir,
        ]);
    }

    public function render(UserDirectory $directory, AnonymizeUser $anonymizer)
    {
        $filters = $this->filters();
        $pending = $this->anonymizingUser();

        return view('livewire.admin.people.user-index', [
            'users' => $directory->query($filters)->paginate(25),
            'totals' => $directory->totals(),
            'exportQuery' => array_filter($filters, fn (string $value, string $key): bool => $value !== $directory->normalize([])[$key], ARRAY_FILTER_USE_BOTH),
            'anonymizing' => $pending,
            'anonymizeBlock' => $pending !== null ? $anonymizer->blockReason($pending) : null,
        ])
            ->layout('layouts::admin')
            ->title('Iscritti');
    }

    private function anonymizingUser(): ?User
    {
        // Anche un superadmin si trova: è AnonymizeUser a rifiutarlo, con il motivo.
        return $this->anonymizingId !== null ? User::find($this->anonymizingId) : null;
    }
}
