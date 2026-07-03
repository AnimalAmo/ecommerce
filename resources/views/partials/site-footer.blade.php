{{-- Footer completo (condiviso). Richiede $px definito dalla pagina. --}}
<footer class="mt-auto bg-white text-black">
    <div class="{{ $px }} border-b border-black pb-10 pt-16">
        <div class="grid grid-cols-4 gap-10">
            <div>
                <h4 class="mb-[18px] text-base font-extrabold uppercase tracking-wide text-[#2B2B2B]">Esperienze</h4>
                <ul class="space-y-4 text-sm text-[#2B2B2B]">
                    <li><a href="{{ route('holiday') }}" class="hover:text-brand-cyan">Animal Holiday</a></li>
                    <li><a href="{{ route('eventi') }}" class="hover:text-brand-cyan">Attività ed Eventi</a></li>
                    <li><a href="{{ route('smartbox') }}" class="hover:text-brand-cyan">Smartbox</a></li>
                </ul>
            </div>
            <div>
                <h4 class="mb-[18px] text-base font-extrabold uppercase tracking-wide text-[#2B2B2B]">Servizi</h4>
                <ul class="space-y-4 text-sm text-[#2B2B2B]">
                    <li><a href="/#news" class="hover:text-brand-cyan">News</a></li>
                    <li><a href="/#community" class="hover:text-brand-cyan">Community</a></li>
                    <li><a href="{{ route('work-with-us') }}" class="hover:text-brand-cyan">Diventa Partner</a></li>
                </ul>
            </div>
            <div>
                <h4 class="mb-[18px] text-base font-extrabold uppercase tracking-wide text-[#2B2B2B]">Azienda</h4>
                <ul class="space-y-4 text-sm text-[#2B2B2B]">
                    <li><a href="/#chi-siamo" class="hover:text-brand-cyan">Chi siamo</a></li>
                    <li><a href="{{ route('work-with-us') }}" class="hover:text-brand-cyan">Lavora con noi</a></li>
                    <li><a href="#" class="hover:text-brand-cyan">Contatti</a></li>
                </ul>
            </div>
            <div>
                <h4 class="mb-[18px] text-base font-extrabold uppercase tracking-wide text-[#2B2B2B]">Help &amp; Support</h4>
                <ul class="space-y-4 text-sm text-[#2B2B2B]">
                    <li><a href="#" class="hover:text-brand-cyan">FAQ</a></li>
                    <li><a href="#" class="hover:text-brand-cyan">Assistenza clienti</a></li>
                </ul>
            </div>
        </div>
        <div class="mt-10 flex items-center justify-end gap-5">
            <a href="#" aria-label="YouTube" class="text-black transition hover:text-brand-cyan"><flux:icon.youtube class="h-4 w-auto" /></a>
            <a href="#" aria-label="Instagram" class="text-black transition hover:text-brand-cyan"><flux:icon.instagram class="h-4 w-auto" /></a>
            <a href="#" aria-label="Facebook" class="text-black transition hover:text-brand-cyan"><flux:icon.facebook class="h-4 w-auto" /></a>
        </div>
    </div>
    <div class="{{ $px }} flex items-center justify-center gap-4 py-6 text-xs font-light text-[#8D8D8D]">
        <span>Copyright © {{ date('Y') }}</span>
        <a href="#" class="hover:text-brand-cyan">Termini e condizioni</a>
        <a href="#" class="hover:text-brand-cyan">Informazioni privacy</a>
        <a href="#" class="hover:text-brand-cyan">Informativa cookie</a>
        <a href="#" class="hover:text-brand-cyan">Gestisci cookie</a>
    </div>
</footer>
