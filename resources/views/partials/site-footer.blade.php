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
                    <li><a href="#" class="hover:text-brand-cyan">{{ __('nav.footer.how_it_works') }}</a></li>
                    <li><a href="#" class="hover:text-brand-cyan">{{ __('nav.footer.contact_us') }}</a></li>
                    <li><a href="#" class="hover:text-brand-cyan">{{ __('nav.footer.support') }}</a></li>
                </ul>
            </div>
        </div>
        <div class="mt-10 flex items-center justify-end gap-5 max-lg:justify-center">
            <a href="#" aria-label="YouTube" class="text-black transition hover:text-brand-cyan"><flux:icon.youtube class="h-4 w-auto" /></a>
            <a href="#" aria-label="Instagram" class="text-black transition hover:text-brand-cyan"><flux:icon.instagram class="h-4 w-auto" /></a>
            <a href="#" aria-label="Facebook" class="text-black transition hover:text-brand-cyan"><flux:icon.facebook class="h-4 w-auto" /></a>
        </div>
    </div>
    <div class="{{ $px }} flex items-center justify-center gap-4 py-6 text-xs font-light text-[#8D8D8D] max-lg:flex-wrap max-lg:gap-x-4 max-lg:gap-y-2">
        <span>{{ __('nav.footer.copyright') }} {{ date('Y') }}</span>
        <a href="#" class="hover:text-brand-cyan">{{ __('nav.footer.terms') }}</a>
        <a href="#" class="hover:text-brand-cyan">{{ __('nav.footer.privacy') }}</a>
        <a href="#" class="hover:text-brand-cyan">{{ __('nav.footer.cookie_policy') }}</a>
        <a href="#" class="hover:text-brand-cyan">{{ __('nav.footer.manage_cookies') }}</a>
    </div>

    @include('partials.mobile-tabbar')
</footer>
