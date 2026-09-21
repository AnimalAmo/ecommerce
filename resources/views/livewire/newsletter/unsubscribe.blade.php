{{-- Disiscrizione dal link delle mail: il GET mostra il pulsante, disiscrive
     il clic. Cornice della pagina "Reimposta password". --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.site-header')

    <main class="flex-1 bg-[linear-gradient(296deg,#FF3EA526_0%,#68CDEB33_100%)]">
        <div class="{{ $px }} flex justify-center pb-32 pt-[120px] max-lg:pb-10 max-lg:pt-10">
            <div class="w-full max-w-[865px] rounded-[3px] border border-gray-150 bg-white/50 px-8 py-[60px] text-center max-lg:rounded-[10px] max-lg:border-brand-cyan max-lg:bg-[#EBF9FD] max-lg:px-6 max-lg:py-10">
                @if ($done)
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-brand-cyan">
                        <flux:icon.check class="h-7 w-7 text-white" />
                    </div>

                    <flux:heading level="1" class="mt-8 !text-4xl !font-bold !text-brand-cyan max-lg:!mt-6 max-lg:!text-lg">{{ __('newsletter.unsubscribe.done_title') }}</flux:heading>

                    <p class="mx-auto mt-6 max-w-[560px] text-lg text-black max-lg:mt-4 max-lg:text-[15px] max-lg:text-[#555555]">{{ __('newsletter.unsubscribe.done_text') }}</p>

                    <div class="mt-10 flex justify-center max-lg:mt-8">
                        <flux:button href="{{ route('home') }}" class="!rounded-full !bg-brand-yellow !px-8 !text-sm !font-bold !text-ink hover:!bg-[#0D171A] hover:!text-white max-lg:!h-[39px] max-lg:!w-full">{{ __('newsletter.unsubscribe.back_home') }}</flux:button>
                    </div>
                @else
                    <flux:heading level="1" class="!text-4xl !font-bold !text-ink max-lg:!text-lg max-lg:!text-brand-cyan">{{ __('newsletter.unsubscribe.title') }}</flux:heading>

                    <p class="mx-auto mt-6 max-w-[560px] text-lg text-black max-lg:mt-4 max-lg:text-[15px] max-lg:text-[#555555]">{{ __('newsletter.unsubscribe.text', ['email' => $email]) }}</p>

                    <div class="mt-10 flex flex-col items-center gap-3 max-lg:mt-8 max-lg:w-full lg:flex-row lg:justify-center lg:gap-4">
                        <flux:button wire:click="unsubscribe" class="!rounded-full !bg-brand-yellow !px-8 !text-sm !font-bold !text-ink hover:!bg-[#0D171A] hover:!text-white max-lg:!h-[39px] max-lg:!w-full">{{ __('newsletter.unsubscribe.submit') }}</flux:button>
                        <flux:button href="{{ route('home') }}" variant="ghost" class="!rounded-full !px-6 !text-sm !font-bold !text-ink max-lg:!h-[39px] max-lg:!w-full">{{ __('newsletter.unsubscribe.back_home') }}</flux:button>
                    </div>
                @endif
            </div>
        </div>
    </main>

    @include('partials.footer-minimal')
</div>
