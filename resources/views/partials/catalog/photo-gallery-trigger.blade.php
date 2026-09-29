{{-- «Vedere tutte le foto» nell'hero delle schede di dettaglio: apre il modale di
     partials.catalog.photo-gallery, che la pagina include fuori dall'hero.

     Solo con almeno due foto: con la sola copertina (le schede del catalogo demo)
     la galleria mostrerebbe la stessa immagine che il cliente ha già davanti.

     Parametri: $photos (URL, HasCatalogImages::galleryPhotos(), l'unico posto della
     soglia: vuoto con una foto sola), $label (testo
     del pulsante, dal file della scheda), $class (posizione nell'hero). --}}
@if ($photos !== [])
    <flux:modal.trigger name="photo-gallery">
        <flux:button class="{{ $class }} h-10 !gap-2.5 !rounded-full !border-0 !bg-brand-cyan !px-7 !text-[15px] !font-bold !text-white !shadow-none">
            <flux:icon.eye class="h-[19px] w-[19px] shrink-0" />
            {{ $label }}
        </flux:button>
    </flux:modal.trigger>
@endif
