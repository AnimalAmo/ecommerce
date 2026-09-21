@php
    use App\Models\Review\Review;
    use App\Support\Format;

    // Soglie del design: verde da 4 in su, giallo da 3, rosso sotto.
    $scoreTone = fn (float $rating): string => $rating >= 4 ? 'success' : ($rating >= 3 ? 'warning' : 'danger');
    $catalogTypes = ['structure', 'event', 'smartbox_package'];
    $sub = trans_choice('admin-people.reviews.subtitle', $counts[Review::STATUS_PENDING], ['count' => $counts[Review::STATUS_PENDING]])
        .' '.__('admin-people.reviews.subtitle_published');
@endphp

<div class="flex flex-col gap-[18px]">
    <x-admin.page-header :heading="__('admin-people.reviews.title')" :sub="$sub" />

    <x-admin.tabs
        model="tab"
        :current="$tab"
        :items="[
            ['key' => Review::STATUS_PENDING, 'label' => __('admin-people.reviews.tab_pending'), 'count' => $counts[Review::STATUS_PENDING]],
            ['key' => Review::STATUS_PUBLISHED, 'label' => __('admin-people.reviews.tab_published'), 'count' => $counts[Review::STATUS_PUBLISHED]],
            ['key' => Review::STATUS_HIDDEN, 'label' => __('admin-people.reviews.tab_hidden'), 'count' => $counts[Review::STATUS_HIDDEN]],
        ]"
    />

    <flux:input
        wire:model.live.debounce.300ms="q"
        icon="magnifying-glass"
        type="search"
        :placeholder="__('admin-people.reviews.search')"
        :aria-label="__('admin-people.reviews.search')"
        class="sm:max-w-[340px]"
    />

    <div class="flex flex-col gap-3.5">
        @forelse ($reviews as $review)
            @php
                $target = $review->reviewable;
                // Le strutture hanno `name`, eventi e smartbox `title`.
                $targetName = $review->reviewable_type === 'structure' ? $target?->name : $target?->title;
                $author = $review->user?->anonymized_at === null ? $review->user : null;
            @endphp
            <x-admin.card wire:key="review-{{ $review->id }}" class="px-5 py-[18px]">
                <div class="flex flex-wrap items-start justify-between gap-3.5">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="flex size-[38px] shrink-0 items-center justify-center rounded-full bg-admin-row text-[13px] font-bold text-gray-600">{{ $review->author_initials }}</span>
                        <div class="min-w-0">
                            <p class="m-0 text-[15px] font-bold text-admin-rail">{{ $review->author_name }}</p>
                            <p class="mt-[3px] text-[12.5px] text-gray-400">
                                {{ __('admin-people.reviews.on', [
                                    'target' => $targetName ?? __('admin-people.reviews.target_gone'),
                                    'date' => $review->reviewed_at?->locale('it')->isoFormat('D MMM YYYY'),
                                ]) }}
                                @if ($review->status === Review::STATUS_HIDDEN && $review->moderated_at !== null)
                                    · {{ __('admin-people.reviews.hidden_on', ['date' => $review->moderated_at->locale('it')->isoFormat('D MMM YYYY')]) }}
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2.5">
                        <x-admin.badge :tone="$scoreTone((float) $review->rating)">{{ __('admin-people.reviews.score', ['rating' => Format::rating((float) $review->rating)]) }}</x-admin.badge>
                        @if ($review->flagged_at !== null)
                            <x-admin.badge tone="pink">{{ __('admin-people.reviews.flagged') }}</x-admin.badge>
                        @endif
                    </div>
                </div>

                @if (filled($review->title))
                    <p class="mt-3.5 text-[15px] font-bold text-admin-rail">{{ $review->title }}</p>
                @endif
                @if (filled($review->body))
                    <p class="mt-1.5 text-[14.5px] leading-relaxed whitespace-pre-line text-[#1E2E33]">{{ $review->body }}</p>
                @endif

                <div class="mt-4 flex flex-wrap gap-2.5">
                    @if ($review->status === Review::STATUS_PENDING)
                        <x-admin.button tone="primary" icon="check" wire:click="publish({{ $review->id }})">{{ __('admin-people.reviews.publish') }}</x-admin.button>
                    @elseif ($review->status === Review::STATUS_HIDDEN)
                        <x-admin.button tone="primary" icon="arrow-uturn-left" wire:click="publish({{ $review->id }})">{{ __('admin-people.reviews.restore') }}</x-admin.button>
                    @endif
                    @if ($review->status !== Review::STATUS_HIDDEN)
                        <x-admin.button tone="outline" icon="eye-slash" wire:click="hide({{ $review->id }})">{{ __('admin-people.reviews.hide') }}</x-admin.button>
                    @endif
                    @if ($target !== null && in_array($review->reviewable_type, $catalogTypes, true))
                        <x-admin.button tone="ghost" icon="eye" :href="route('admin.catalog.show', ['type' => $review->reviewable_type, 'id' => $review->reviewable_id])" wire:navigate>{{ __('admin-people.reviews.open_listing') }}</x-admin.button>
                    @endif
                    @if ($author !== null)
                        <x-admin.button tone="ghost" icon="envelope" href="mailto:{{ $author->email }}?subject={{ rawurlencode(__('admin-people.reviews.mail_subject')) }}">{{ __('admin-people.reviews.write_author') }}</x-admin.button>
                    @endif
                    <x-admin.button tone="danger" icon="trash" wire:click="askDelete({{ $review->id }})">{{ __('admin-people.reviews.delete') }}</x-admin.button>
                </div>
            </x-admin.card>
        @empty
            <x-admin.card>
                <x-admin.empty>{{ filled($q) ? __('admin-people.reviews.empty_search') : __('admin-people.reviews.empty.'.$tab) }}</x-admin.empty>
            </x-admin.card>
        @endforelse
    </div>

    @if ($reviews->hasPages())
        <flux:pagination :paginator="$reviews" />
    @endif

    <flux:modal name="review-delete" class="w-full max-w-[460px]">
        @if ($deleting !== null)
            <h2 class="m-0 text-[19px] font-bold text-admin-rail">{{ __('admin-people.reviews.delete_title') }}</h2>
            <p class="mt-3 text-[14.5px] leading-normal text-gray-600">{{ __('admin-people.reviews.delete_body', ['name' => $deleting->author_name]) }}</p>
            <div class="mt-6 flex flex-wrap justify-end gap-2.5">
                <flux:modal.close>
                    <x-admin.button tone="outline">{{ __('admin-people.reviews.cancel') }}</x-admin.button>
                </flux:modal.close>
                <x-admin.button tone="danger" wire:click="delete">{{ __('admin-people.reviews.delete') }}</x-admin.button>
            </div>
        @endif
    </flux:modal>
</div>
