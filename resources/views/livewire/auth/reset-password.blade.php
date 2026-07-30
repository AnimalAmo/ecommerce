{{-- Reimposta password (dal link dell'email). Nessuna artboard XD: riusa la
     cornice della thank-you page "Lavora con noi" (banda gradiente + card
     centrata) e i campi della pagina Profilo → Sicurezza. --}}
@php
    $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8';
    $inputClasses = '!min-w-0 !border-0 !bg-transparent !shadow-none !ring-0 [&_input]:!h-10 [&_input]:!w-full [&_input]:!rounded-[3px] [&_input]:!border [&_input]:!border-[#C8C8C8]/70 [&_input]:!bg-white [&_input]:!px-[15px] [&_input]:!text-[15px] [&_input]:!text-[#0D171A] [&_input]:!shadow-none [&_input]:!ring-0 max-lg:[&_input]:!rounded-[5px] max-lg:[&_input]:!px-4 max-lg:[&_input]:!text-sm';
    $labelClasses = '!block !pl-[15px] !text-xs !font-normal !leading-none !text-[#555555] max-lg:!pl-[7px]';
@endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.site-header')

    <main class="flex-1 bg-[linear-gradient(296deg,#FF3EA526_0%,#68CDEB33_100%)]">
        <div class="{{ $px }} flex justify-center pb-32 pt-[120px] max-lg:pb-10 max-lg:pt-10">
            <div class="w-full max-w-[865px] rounded-[3px] border border-gray-150 bg-white/50 px-8 pb-[60px] pt-[60px] max-lg:rounded-[10px] max-lg:border-brand-cyan max-lg:bg-[#EBF9FD] max-lg:px-6 max-lg:py-10">

                @if ($done)
                    {{-- Esito positivo --}}
                    <div class="text-center">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-brand-cyan">
                            <flux:icon.check class="h-7 w-7 text-white" />
                        </div>

                        <flux:heading level="1" class="mt-8 !text-4xl !font-bold !text-brand-cyan max-lg:!mt-6 max-lg:!text-lg">{{ __('auth-modal.reset.done_title') }}</flux:heading>

                        <p class="mt-6 text-lg text-black max-lg:mt-4 max-lg:text-[15px] max-lg:text-[#555555]">{{ __('auth-modal.reset.done_text') }}</p>

                        <div class="mt-10 flex flex-col items-center gap-3 max-lg:mt-8 max-lg:w-full lg:flex-row lg:justify-center lg:gap-4">
                            <flux:button wire:click="goToLogin" class="!rounded-full !bg-brand-yellow !px-8 !text-sm !font-bold !text-ink hover:!bg-[#0D171A] hover:!text-white max-lg:!h-[39px] max-lg:!w-full">{{ __('auth-modal.reset.done_cta') }}</flux:button>
                            <flux:button href="{{ route('home') }}" variant="ghost" class="!rounded-full !px-6 !text-sm !font-bold !text-ink max-lg:!h-[39px] max-lg:!w-full">{{ __('auth-modal.reset.back_home') }}</flux:button>
                        </div>
                    </div>

                @elseif ($invalid)
                    {{-- Link scaduto, già usato o incompleto --}}
                    <div class="text-center">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-brand-magenta/15">
                            <flux:icon.lock class="h-7 w-7 text-brand-magenta" />
                        </div>

                        <flux:heading level="1" class="mt-8 !text-4xl !font-bold !text-brand-magenta max-lg:!mt-6 max-lg:!text-lg">{{ __('auth-modal.reset.invalid_title') }}</flux:heading>

                        <p class="mx-auto mt-6 max-w-[560px] text-lg text-black max-lg:mt-4 max-lg:text-[15px] max-lg:text-[#555555]">{{ __('auth-modal.reset.invalid_text') }}</p>

                        <div class="mt-10 flex flex-col items-center gap-3 max-lg:mt-8 max-lg:w-full lg:flex-row lg:justify-center lg:gap-4">
                            <flux:button wire:click="requestNewLink" class="!rounded-full !bg-brand-yellow !px-8 !text-sm !font-bold !text-ink hover:!bg-[#0D171A] hover:!text-white max-lg:!h-[39px] max-lg:!w-full">{{ __('auth-modal.reset.invalid_cta') }}</flux:button>
                            <flux:button href="{{ route('home') }}" variant="ghost" class="!rounded-full !px-6 !text-sm !font-bold !text-ink max-lg:!h-[39px] max-lg:!w-full">{{ __('auth-modal.reset.back_home') }}</flux:button>
                        </div>
                    </div>

                @else
                    {{-- Form --}}
                    <div class="text-center">
                        <flux:heading level="1" class="!text-4xl !font-bold !text-ink max-lg:!text-lg max-lg:!text-brand-cyan">{{ __('auth-modal.reset.title') }}</flux:heading>
                        <p class="mt-4 text-[15px] leading-[21px] text-[#555555]">{{ __('auth-modal.reset.intro', ['email' => $email]) }}</p>
                    </div>

                    <form wire:submit="save" class="mx-auto mt-10 w-full max-w-[472px] max-lg:mt-8">
                        <div class="space-y-4 max-lg:space-y-[21px]">
                            @foreach ([
                                ['model' => 'password', 'label' => __('auth-modal.reset.new_password')],
                                ['model' => 'passwordConfirm', 'label' => __('auth-modal.reset.repeat_password')],
                            ] as $field)
                                <div wire:key="reset-{{ $field['model'] }}">
                                    <flux:label class="{{ $labelClasses }}">{{ $field['label'] }}</flux:label>
                                    <div class="mt-[11px] max-lg:mt-1">
                                        <flux:input type="password" wire:model="{{ $field['model'] }}" placeholder="***************" class="{{ $inputClasses }}" />
                                    </div>
                                    {{-- Campo composto a mano: senza questo il submit rifiutato tornerebbe muto. --}}
                                    <flux:error name="{{ $field['model'] }}" class="!mt-1 !pl-[15px] !text-xs max-lg:!pl-[7px]" />
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-10 flex justify-center max-lg:mt-8">
                            <flux:button type="submit" class="!rounded-full !bg-brand-yellow !px-8 !text-sm !font-bold !text-ink hover:!bg-[#0D171A] hover:!text-white max-lg:!h-[39px] max-lg:!w-full">{{ __('auth-modal.reset.submit') }}</flux:button>
                        </div>
                    </form>
                @endif

            </div>
        </div>
    </main>

    <div class="max-lg:hidden">
        @include('partials.footer-minimal')
    </div>
    <div class="lg:hidden">
        @include('partials.mobile-tabbar')
    </div>
</div>
