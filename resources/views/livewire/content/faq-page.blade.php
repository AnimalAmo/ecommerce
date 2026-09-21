{{-- Domande frequenti (FAQ di piattaforma dal pannello): stessa impaginazione delle pagine legali, risposte a fisarmonica. --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.site-header')

    <main class="flex-1">
        <div class="{{ $px }} pt-[60px] pb-20 max-lg:pt-8 max-lg:pb-10">
            <div class="mx-auto w-full max-w-[900px]">
                <h1 class="text-4xl font-bold text-black max-lg:text-[18px] max-lg:leading-[21px] max-lg:text-[#0D171A]">{{ __('faq.title') }}</h1>
                <p class="mt-3 text-[18px] text-[#555555] max-lg:text-[15px]">{{ __('faq.subtitle') }}</p>

                @forelse ($groups as $topic => $faqs)
                    <section wire:key="faq-topic-{{ $topic }}" class="mt-10 max-lg:mt-8">
                        <h2 class="text-[22px] font-bold leading-[30px] text-black max-lg:text-lg">{{ __('faq.topics.'.$topic) }}</h2>
                        <flux:accordion transition class="mt-3">
                            @foreach ($faqs as $faq)
                                <flux:accordion.item wire:key="faq-{{ $faq->id }}" class="border-b border-[#E2EAEB]">
                                    <flux:accordion.heading class="!py-4 !text-[15px] !font-medium !text-[#0D171A]">{{ $faq->question }}</flux:accordion.heading>
                                    <flux:accordion.content>
                                        <p class="whitespace-pre-line pb-4 text-[15px] leading-[22px] text-[#627277]">{{ $faq->answer }}</p>
                                    </flux:accordion.content>
                                </flux:accordion.item>
                            @endforeach
                        </flux:accordion>
                    </section>
                @empty
                    <p class="mt-10 text-[15px] text-[#555555]">{{ __('faq.empty') }}</p>
                @endforelse

                <div class="mt-14 rounded-[4px] bg-gray-100 px-6 py-5 max-lg:mt-10">
                    <p class="text-[18px] font-bold text-[#0D171A]">{{ __('faq.contact_heading') }}</p>
                    <p class="mt-1.5 text-[15px] text-[#555555]">{{ __('faq.contact_body') }}</p>
                    <flux:button :href="route('contact')" class="mt-4 !h-10 !rounded-full !border-0 !bg-brand-yellow !px-6 !text-[15px] !font-bold !text-ink !shadow-none hover:!bg-[#0D171A] hover:!text-white">{{ __('faq.contact_cta') }}</flux:button>
                </div>
            </div>
        </div>
    </main>

    @include('partials.site-footer')
</div>
