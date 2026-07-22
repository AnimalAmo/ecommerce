{{-- Footer minimal (pagine secondarie): dati societari + privacy / cookie policy --}}
<footer class="border-t border-gray-150 bg-white">
    <div class="mx-auto flex w-full max-w-[1600px] items-center justify-center gap-4 px-4 py-6 text-xs font-light text-[#8D8D8D] max-lg:flex-wrap max-lg:gap-y-2 lg:px-8">
        {{-- Dati societari (non tradotti: denominazione e dati fiscali) --}}
        <span>Animal Amo Srl — P.IVA 02746270228 — Capitale sociale 10.000,00 €</span>
        <a href="#" class="hover:text-brand-cyan">{{ __('nav.footer_minimal.privacy_policy') }}</a>
        <span aria-hidden="true">|</span>
        <a href="#" class="hover:text-brand-cyan">{{ __('nav.footer_minimal.cookie_policy') }}</a>
    </div>
</footer>
