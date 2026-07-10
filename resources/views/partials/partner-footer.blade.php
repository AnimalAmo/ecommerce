{{-- Footer B2B: tre colonne di link (Azienda / Help & Support / Sicurezza). Richiede $px definito dalla pagina. --}}
<footer class="bg-white shadow-[1px_1px_10px_#0000001A]">
    <div class="{{ $px }} py-14">
        {{-- Colonne come blocco centrato nel container; testo/link allineati a sinistra (come footer ecommerce) --}}
        <div class="flex flex-col items-center gap-10 sm:flex-row sm:items-start sm:justify-center sm:gap-24 lg:gap-32">
            <div>
                <h3 class="text-[15px] font-semibold text-[#2B2B2B]">{{ __('partner.footer_company') }}</h3>
                <ul class="mt-6 space-y-4 text-sm text-[#2B2B2B]">
                    <li><a href="#" class="hover:text-brand-cyan">{{ __('partner.footer_about') }}</a></li>
                    <li><a href="#" class="hover:text-brand-cyan">{{ __('partner.footer_website') }}</a></li>
                </ul>
            </div>
            <div>
                <h3 class="text-[15px] font-semibold text-[#2B2B2B]">{{ __('partner.footer_help') }}</h3>
                <ul class="mt-6 space-y-4 text-sm text-[#2B2B2B]">
                    <li><a href="#" class="hover:text-brand-cyan">{{ __('partner.help_contact') }}</a></li>
                    <li><a href="#" class="hover:text-brand-cyan">{{ __('partner.help_support') }}</a></li>
                    <li><a href="#" class="hover:text-brand-cyan">{{ __('partner.help_faq') }}</a></li>
                </ul>
            </div>
            <div>
                <h3 class="text-[15px] font-semibold text-[#2B2B2B]">{{ __('partner.footer_security') }}</h3>
                <ul class="mt-6 space-y-4 text-sm text-[#2B2B2B]">
                    <li><a href="#" class="hover:text-brand-cyan">{{ __('partner.footer_privacy') }}</a></li>
                    <li><a href="#" class="hover:text-brand-cyan">{{ __('partner.footer_cookie') }}</a></li>
                    <li><a href="#" class="hover:text-brand-cyan">{{ __('partner.footer_terms') }}</a></li>
                </ul>
            </div>
        </div>
    </div>
</footer>
