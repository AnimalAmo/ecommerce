{{-- Footer B2B: tre colonne di link (Azienda / Help & Support / Sicurezza). Richiede $px definito dalla pagina. --}}
<footer class="bg-white shadow-[1px_1px_10px_#0000001A]">
    <div class="{{ $px }} py-14">
        {{-- Blocco footer centrato e allineato alla larghezza del form (card 816px); link allineati a sinistra --}}
        <div class="mx-auto grid w-full max-w-[816px] grid-cols-1 gap-10 sm:grid-cols-3">
            <div>
                <h3 class="text-lg font-bold text-ink">{{ __('partner.footer_company') }}</h3>
                <ul class="mt-6 space-y-4 text-sm text-[#555555]">
                    <li><a href="#" class="hover:text-brand-cyan">{{ __('partner.footer_about') }}</a></li>
                    <li><a href="#" class="hover:text-brand-cyan">{{ __('partner.footer_website') }}</a></li>
                </ul>
            </div>
            <div>
                <h3 class="text-lg font-bold text-ink">{{ __('partner.footer_help') }}</h3>
                <ul class="mt-6 space-y-4 text-sm text-[#555555]">
                    <li><a href="#" class="hover:text-brand-cyan">{{ __('partner.help_contact') }}</a></li>
                    <li><a href="#" class="hover:text-brand-cyan">{{ __('partner.help_support') }}</a></li>
                    <li><a href="#" class="hover:text-brand-cyan">{{ __('partner.help_faq') }}</a></li>
                </ul>
            </div>
            <div>
                <h3 class="text-lg font-bold text-ink">{{ __('partner.footer_security') }}</h3>
                <ul class="mt-6 space-y-4 text-sm text-[#555555]">
                    <li><a href="#" class="hover:text-brand-cyan">{{ __('partner.footer_privacy') }}</a></li>
                    <li><a href="#" class="hover:text-brand-cyan">{{ __('partner.footer_cookie') }}</a></li>
                    <li><a href="#" class="hover:text-brand-cyan">{{ __('partner.footer_terms') }}</a></li>
                </ul>
            </div>
        </div>
    </div>
</footer>
