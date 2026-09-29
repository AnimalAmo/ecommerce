{{-- Galleria «Vedere tutte le foto» (nessun artboard XD: modale Flux col
     carousel di Flux Pro, una foto per slide, frecce e indicatori).

     Va incluso a livello di pagina, FUORI dall'hero: sulla scheda struttura l'hero
     desktop è `max-lg:hidden`, e un <dialog> aperto dentro un antenato nascosto
     blocca tutta la pagina senza mostrarsi (vedi CLAUDE.md, Flux gotchas).

     Parametri: $photos (URL, HasCatalogImages::galleryPhotos(): vuoto con una foto
     sola, la soglia sta lì per pulsante e modale insieme), $title (nome della
     scheda, per titolo e testi alternativi). --}}
@if ($photos !== [])
    {{-- backdrop:!bg-black/30: lo stesso velo dei pop-up delle schede e del modale di login. --}}
    <flux:modal name="photo-gallery" class="w-full max-w-5xl backdrop:!bg-black/30">
        <flux:heading size="lg" class="pe-10 !font-bold !text-black">{{ $title }}</flux:heading>

        <flux:carousel snap="mandatory" indicators aria-label="{{ __('catalog.gallery.label', ['title' => $title]) }}" class="mt-5">
            @foreach ($photos as $url)
                <flux:carousel.slide class="w-full">
                    {{-- loading=lazy: con il modale chiuso il browser non scarica le foto dopo la prima.
                         max-h: su uno schermo basso titolo, foto e indicatori stanno insieme senza scroll. --}}
                    <img src="{{ $url }}" alt="{{ __('catalog.gallery.photo_alt', ['title' => $title, 'number' => $loop->iteration, 'total' => count($photos)]) }}" loading="lazy" class="aspect-[3/2] max-h-[65dvh] w-full rounded-[3px] bg-gray-100 object-contain">
                </flux:carousel.slide>
            @endforeach

            <x-slot:previous>
                <flux:button square icon="chevron-left" aria-label="{{ __('catalog.gallery.previous') }}" class="!rounded-full !border-0 !bg-white/90 !text-ink !shadow-none" />
            </x-slot:previous>
            <x-slot:next>
                <flux:button square icon="chevron-right" aria-label="{{ __('catalog.gallery.next') }}" class="!rounded-full !border-0 !bg-white/90 !text-ink !shadow-none" />
            </x-slot:next>
        </flux:carousel>
    </flux:modal>
@endif
