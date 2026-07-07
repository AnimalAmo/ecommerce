<div class="relative">
    <flux:button variant="ghost" size="sm" square aria-label="Carrello" href="{{ route('carrello') }}" class="!text-ink hover:!text-brand-cyan">
        <flux:icon.cart class="h-5 w-5" />
    </flux:button>
    @if ($count > 0)
        <flux:badge size="sm" class="pointer-events-none absolute -top-1 -right-1.5 !h-5 !min-w-5 items-center !justify-center !rounded-full !bg-brand-yellow !px-1 !text-sm !leading-none !text-black">{{ $count }}</flux:badge>
    @endif
</div>
