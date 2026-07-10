<?php

namespace App\Livewire\Partner\Activity;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use Livewire\Component;

class ActivityInfo extends Component
{
    use InteractsWithStructureDraft;

    public string $dateStart = '';

    public string $dateEnd = '';

    /** Orari: solo per gli Eventi. */
    public string $timeStart = '';

    public string $timeEnd = '';

    /** Gli Eventi hanno anche ora inizio/fine; le Attività solo le date. */
    public bool $isEvent = false;

    public function mount(): void
    {
        $draft = $this->draft();
        $this->dateStart = $draft->date_start ? $draft->date_start->format('Y-m-d') : '';
        $this->dateEnd = $draft->date_end ? $draft->date_end->format('Y-m-d') : '';
        $this->timeStart = $draft->time_start ?? '';
        $this->timeEnd = $draft->time_end ?? '';
        $this->isEvent = $draft->type === 'eventi';
    }

    /** Orari selezionabili (mezz'ora): 00:00 → 23:30. */
    public function times(): array
    {
        $times = [];
        for ($h = 0; $h < 24; $h++) {
            $times[] = sprintf('%02d:00', $h);
            $times[] = sprintf('%02d:30', $h);
        }

        return $times;
    }

    public function next(): void
    {
        $rules = [
            'dateStart' => ['required', 'date'],
            'dateEnd' => ['required', 'date', 'after_or_equal:dateStart'],
        ];
        if ($this->isEvent) {
            $rules['timeStart'] = ['required', 'string'];
            $rules['timeEnd'] = ['required', 'string'];
        }

        $this->validate($rules);

        $attributes = ['date_start' => $this->dateStart, 'date_end' => $this->dateEnd];
        if ($this->isEvent) {
            $attributes['time_start'] = $this->timeStart;
            $attributes['time_end'] = $this->timeEnd;
        }

        $this->saveStep($attributes, 5);
        $this->redirectRoute('partner.activity.included');
    }

    public function render()
    {
        return view('livewire.partner.activity.activity-info', ['times' => $this->times()])
            ->title(__('partner.activity_info.title'));
    }
}
