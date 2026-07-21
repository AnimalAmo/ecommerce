{{-- Cuore preferiti condiviso (card listing e hero delle schede di dettaglio).
     Contratto: $type (alias morph), $id, $active (bool stato iniziale),
     $classes (classi extra di posizionamento, opzionale — es. il cuore assoluto delle card listing). --}}
{{-- Base bianca come !bg-[#fff] (non !bg-white): nel CSS compilato i valori arbitrari precedono !bg-brand-yellow, così il toggle vince --}}
{{-- Stato iniziale dal server (riga favorites), flip Alpine ottimistico solo da autenticati; gli ospiti aprono il login --}}
<flux:button square x-data="{ fav: {{ $active ? 'true' : 'false' }} }" @click="if ({{ auth()->check() ? 'true' : 'false' }}) fav = !fav; $wire.toggleFavorite('{{ $type }}', {{ $id }})" ::class="fav && '!bg-brand-yellow'" ::aria-pressed="fav" aria-label="{{ __('nav.card.add_to_favorites') }}" class="{{ trim(($classes ?? '').' !h-[30px] !w-[30px] !rounded-full !border-0 !bg-[#fff] !text-black !shadow-none') }}">
    <flux:icon.heart class="h-4 w-4" />
</flux:button>
