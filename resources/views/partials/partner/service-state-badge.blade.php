{{--
    Badge di stato di un servizio in "I miei servizi". $state arriva da
    App\Services\Partner\DraftPublicationState: qui si decide solo il colore e
    quale copy mostrare, la diagnosi è del service.

    'published' e 'draft' non hanno badge: il primo è la normalità, il secondo
    è una bozza a metà che il partner sta ancora compilando.
--}}
@php
    use App\Services\Partner\DraftPublicationState as State;

    $badge = match ($state) {
        State::AWAITING_STRIPE => ['label' => 'partner.my_services.awaiting_stripe', 'class' => '!bg-brand-yellow !text-ink', 'hint' => null],
        State::PUBLISHING => ['label' => 'partner.my_services.publishing', 'class' => '!bg-brand-cyan !text-white', 'hint' => 'partner.my_services.publishing_hint'],
        State::INCOMPLETE => ['label' => 'partner.my_services.incomplete', 'class' => '!bg-[#FDEBE8] !text-[#F85933]', 'hint' => 'partner.my_services.incomplete_hint'],
        State::AWAITING_APPROVAL => ['label' => 'partner.my_services.awaiting_approval', 'class' => '!bg-brand-purple-soft !text-ink', 'hint' => null],
        State::SUSPENDED => ['label' => 'partner.my_services.suspended', 'class' => '!bg-[#FDEBE8] !text-[#F85933]', 'hint' => null],
        default => null,
    };
@endphp

@if ($badge !== null)
    <flux:badge size="sm" class="mt-1 !rounded-[3px] {{ $badge['class'] }}">{{ __($badge['label']) }}</flux:badge>
    @if ($badge['hint'] !== null)
        <p class="mt-1 text-[13px] text-[#959595]">{{ __($badge['hint']) }}</p>
    @endif
@endif
