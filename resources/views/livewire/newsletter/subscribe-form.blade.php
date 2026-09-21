{{-- Iscrizione alla newsletter nel piede del sito (nessuna artboard XD: stessi
     campi e pulsanti delle modali auth). La frase del consenso è quella salvata
     come prova: non spostarla lontano dal pulsante. --}}
<div class="flex flex-wrap items-start justify-between gap-x-10 gap-y-5 border-b border-gray-150 pb-10 max-lg:pb-8">
    <div class="max-w-[520px]">
        <h4 class="text-base font-extrabold tracking-wide text-[#2B2B2B]">{{ __('newsletter.footer.heading') }}</h4>
        <p class="mt-2 text-sm leading-[1.55] text-[#555555]">{{ __('newsletter.footer.intro') }}</p>
    </div>

    <div class="w-full max-w-[560px]">
        @if ($done)
            <div class="flex items-start gap-3 rounded-[5px] bg-brand-cyan-bg px-4 py-3.5" role="status">
                <flux:icon.envelope class="mt-0.5 size-5 shrink-0 text-admin-teal" />
                <div>
                    <p class="text-sm font-bold text-ink">{{ __('newsletter.footer.success_title') }}</p>
                    <p class="mt-1 text-sm leading-[1.5] text-[#555555]">{{ __('newsletter.footer.success') }}</p>
                </div>
            </div>
        @else
            <form wire:submit="subscribe" novalidate>
                <div class="flex items-start gap-2.5 max-sm:flex-col">
                    <div class="w-full flex-1">
                        <flux:input
                            type="email"
                            wire:model="email"
                            :aria-label="__('newsletter.footer.email_label')"
                            :placeholder="__('newsletter.footer.email_placeholder')"
                            autocomplete="email"
                            class="[&_input]:!h-10 [&_input]:!rounded-full [&_input]:!border-[#C8C8C8] [&_input]:!px-5 [&_input]:!text-sm [&_input]:!shadow-none"
                        />
                        <flux:error name="email" class="!mt-1 !pl-5 !text-xs" />
                    </div>
                    <flux:button type="submit" class="!h-10 !shrink-0 !rounded-full !border-0 !bg-brand-yellow !px-7 !text-sm !font-bold !text-ink hover:!bg-ink hover:!text-white max-sm:!w-full">
                        {{ __('newsletter.footer.submit') }}
                    </flux:button>
                </div>
                <p class="mt-3 text-xs leading-[1.5] text-[#8D8D8D]">
                    {{ __('newsletter.footer.consent') }}
                    <a href="{{ route('privacy') }}" class="underline hover:text-brand-cyan">{{ __('newsletter.footer.privacy') }}</a>
                </p>
            </form>
        @endif
    </div>
</div>
