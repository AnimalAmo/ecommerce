{{-- Footer completo (condiviso). Richiede $px definito dalla pagina. --}}
<footer class="mt-auto bg-white text-black">
    <div class="{{ $px }} border-b border-black pb-10 pt-16 max-lg:pt-10">
        {{-- Mobile: 2 colonne invece di 4 --}}
        <div class="grid grid-cols-4 gap-10 max-lg:grid-cols-2 max-lg:gap-8">
            <div>
                <h4 class="mb-[18px] text-base font-extrabold capitalize tracking-wide text-[#2B2B2B]">{{ __('nav.footer.experiences') }}</h4>
                <ul class="space-y-4 text-sm text-[#2B2B2B]">
                    <li><a href="{{ route('holiday') }}" class="hover:text-brand-cyan">Animal Holiday</a></li>
                    <li><a href="{{ route('eventi') }}" class="hover:text-brand-cyan">{{ __('nav.footer.events') }}</a></li>
                    <li><a href="{{ route('smartbox') }}" class="hover:text-brand-cyan">Smartbox</a></li>
                </ul>
            </div>
            <div>
                <h4 class="mb-[18px] text-base font-extrabold capitalize tracking-wide text-[#2B2B2B]">{{ __('nav.footer.services') }}</h4>
                <ul class="space-y-4 text-sm text-[#2B2B2B]">
                    <li><a href="{{ route('news') }}" class="hover:text-brand-cyan">{{ __('nav.footer.news') }}</a></li>
                    <li><a href="{{ route('community') }}" class="hover:text-brand-cyan">{{ __('nav.footer.community') }}</a></li>
                </ul>
            </div>
            <div>
                <h4 class="mb-[18px] text-base font-extrabold capitalize tracking-wide text-[#2B2B2B]">{{ __('nav.footer.company') }}</h4>
                <ul class="space-y-4 text-sm text-[#2B2B2B]">
                    <li><a href="{{ route('about') }}" class="hover:text-brand-cyan">{{ __('nav.footer.about') }}</a></li>
                    <li><a href="{{ route('work-with-us') }}" class="hover:text-brand-cyan">{{ __('nav.footer.work_with_us') }}</a></li>
                </ul>
            </div>
            <div>
                <h4 class="mb-[18px] text-base font-extrabold capitalize tracking-wide text-[#2B2B2B]">{{ __('nav.footer.help_support') }}</h4>
                <ul class="space-y-4 text-sm text-[#2B2B2B]">
                    {{-- Contattaci e Assistenza unificati nella pagina Contattaci (lug 2026) --}}
                    <li><a href="{{ route('contact') }}" class="hover:text-brand-cyan">{{ __('nav.footer.contact_us') }}</a></li>
                </ul>
            </div>
        </div>
        <div class="mt-10 flex items-center justify-end gap-5 max-lg:justify-center">
            <a href="https://www.instagram.com/animal___amo" target="_blank" aria-label="Instagram" class="text-black transition hover:text-brand-cyan"><flux:icon.instagram class="h-4 w-auto" /></a>
        </div>
    </div>
    <div class="{{ $px }} flex items-center justify-center gap-4 py-6 text-xs font-light text-[#8D8D8D] max-lg:flex-wrap max-lg:gap-x-4 max-lg:gap-y-2">
        <span>{{ __('nav.footer.copyright') }} {{ date('Y') }}</span>
        {{-- Dati societari (non tradotti: denominazione e dati fiscali) --}}
        <span>Animal Amo Srl — P.IVA 02746270228 — Capitale sociale 10.000,00 €</span>
        <a href="{{ route('terms.customers') }}" class="hover:text-brand-cyan">{{ __('nav.footer.terms') }}</a>
        <a href="{{ route('privacy') }}" class="hover:text-brand-cyan">{{ __('nav.footer.privacy') }}</a>
        {{-- Informativa cookie: documento ospitato da Iubenda. `iubenda-embed` lega l'anchor
             al loader (layouts/app.blade.php), che apre il documento in un riquadro sopra il
             sito; `iubenda-nostyle` evita che Iubenda lo trasformi nel suo bottone bianco.
             target/rel sono il ripiego finché lo script non è caricato (o se è bloccato). --}}
        <a href="{{ config('services.iubenda.cookie_policy_url') }}" class="iubenda-nostyle iubenda-embed hover:text-brand-cyan" target="_blank" rel="noopener">{{ __('nav.footer.cookie_policy') }}</a>
        {{-- "Gestisci cookie" riapre il pannello preferenze del widget consenso: la classe è
             l'aggancio documentato da Iubenda, non c'è una pagina da raggiungere. L'href resta
             "#" perché senza il widget il controllo non ha nulla da aprire. --}}
        <a href="#" class="iubenda-cs-preferences-link hover:text-brand-cyan">{{ __('nav.footer.manage_cookies') }}</a>
    </div>

    {{-- Le schede di dettaglio sostituiscono la tabbar con la propria barra CTA (XD app): $hideMobileTabbar --}}
    @unless ($hideMobileTabbar ?? false)
        @include('partials.mobile-tabbar')
    @endunless
</footer>
