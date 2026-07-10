<?php

namespace App\Livewire\Partner;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use Livewire\Component;

class ActivityLocation extends Component
{
    use InteractsWithStructureDraft;

    public string $address = '';

    public string $city = '';

    public string $province = '';

    public string $zip = '';

    public string $meetingPoint = '';

    public function mount(): void
    {
        $draft = $this->draft();
        $this->address = $draft->address ?? '';
        $this->city = $draft->city ?? '';
        $this->province = $draft->province ?? '';
        $this->zip = $draft->zip ?? '';
        $this->meetingPoint = $draft->meeting_point ?? '';
    }

    public function next(): void
    {
        $this->validate([
            'address' => ['required', 'string', 'max:128'],
            'city' => ['required', 'string', 'max:64'],
            'province' => ['required', 'string', 'max:64'],
            'zip' => ['required', 'digits:5'],
            'meetingPoint' => ['required', 'string', 'max:128'],
        ]);

        $this->saveStep([
            'address' => $this->address,
            'city' => $this->city,
            'province' => $this->province,
            'zip' => $this->zip,
            'meeting_point' => $this->meetingPoint,
        ], 3);
        $this->redirectRoute('partner.activity.description');
    }

    public function render()
    {
        return view('livewire.partner.activity-location')
            ->title(__('partner.activity_location.title'));
    }
}
