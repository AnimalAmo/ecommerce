{{-- Community / Animal Network (design: is_community). --}}
@use('App\Services\Content\CommunityModerationService', 'Moderation')

<div class="flex flex-col gap-[18px]">
    <x-admin.page-header
        :heading="__('admin-content.community.title')"
        :sub="trans_choice('admin-content.community.sub', $counts[Moderation::FLAGGED])"
    />

    <x-admin.tabs model="tab" :current="$tab" :items="collect(Moderation::TABS)->map(fn ($key) => [
        'key' => $key,
        'label' => __('admin-content.community.tabs.'.$key),
        'count' => $counts[$key],
    ])->all()" />

    @if ($page->isEmpty())
        <x-admin.card>
            <x-admin.empty>{{ __('admin-content.community.empty.'.$tab) }}</x-admin.empty>
        </x-admin.card>
    @else
        <div class="grid items-start gap-3.5 lg:grid-cols-2">
            @foreach ($page as $post)
                @php
                    $initials = collect(preg_split('/\s+/u', trim($post->author_name)) ?: [])
                        ->filter()
                        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                        ->take(2)
                        ->implode('');
                @endphp
                <x-admin.card wire:key="post-{{ $post->id }}" class="flex flex-col px-5 py-[18px]">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-[11px]">
                            <span aria-hidden="true" class="flex size-9 shrink-0 items-center justify-center rounded-full bg-admin-row text-[12.5px] font-bold text-gray-600">{{ $initials }}</span>
                            <div class="min-w-0">
                                <p class="m-0 truncate text-[14.5px] font-bold text-admin-rail">{{ $post->author_name }}</p>
                                <p class="mt-0.5 text-xs text-gray-400">
                                    {{ $post->created_at->locale('it')->diffForHumans() }}
                                    @if ($post->hidden_at)
                                        · {{ __('admin-content.community.hidden_on', ['date' => $post->hidden_at->locale('it')->translatedFormat('j M Y')]) }}
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <x-admin.badge tone="muted">{{ $post->tag }}</x-admin.badge>
                            @if ($post->reports_count > 0)
                                <x-admin.badge tone="pink">{{ trans_choice('admin-content.community.reports', $post->reports_count) }}</x-admin.badge>
                            @endif
                        </div>
                    </div>

                    <p class="mt-3.5 text-[14.5px] font-bold text-admin-rail">{{ $post->title }}</p>
                    <p class="mt-1.5 line-clamp-6 whitespace-pre-line text-sm leading-relaxed text-[#1E2E33]">{{ $post->body }}</p>

                    <div class="mt-3.5 flex flex-wrap items-center gap-4 text-[12.5px] text-gray-400">
                        @if ($post->replies_count > 0)
                            <flux:button variant="ghost" size="sm" wire:click="openReplies({{ $post->id }})" class="!-ml-2 !h-7 !px-2 !text-[12.5px] !font-semibold !text-admin-teal">
                                {{ trans_choice('admin-content.community.replies', $post->replies_count) }}
                                @if ($post->hidden_replies_count > 0)
                                    {{ trans_choice('admin-content.community.hidden_replies', $post->hidden_replies_count) }}
                                @endif
                            </flux:button>
                        @else
                            <span>{{ trans_choice('admin-content.community.replies', 0) }}</span>
                        @endif
                        @unless ($post->hidden_at)
                            <a href="{{ route('community.post', $post->id) }}" target="_blank" rel="noopener" class="font-semibold text-admin-teal hover:underline">{{ __('admin-content.community.view_on_site') }}</a>
                        @endunless
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        @if ($post->hidden_at)
                            <x-admin.button icon="arrow-uturn-left" class="!h-[34px] !px-3.5 !text-[13.5px]" wire:click="restore({{ $post->id }})">{{ __('admin-content.community.restore') }}</x-admin.button>
                            <x-admin.button tone="danger" icon="trash" class="!h-[34px] !px-3.5 !text-[13.5px]" wire:click="ask('delete', {{ $post->id }})">{{ __('admin-content.community.delete') }}</x-admin.button>
                        @else
                            @if ($post->reports_count > 0)
                                <x-admin.button icon="check" class="!h-[34px] !px-3.5 !text-[13.5px]" wire:click="keep({{ $post->id }})">{{ __('admin-content.community.keep') }}</x-admin.button>
                            @endif
                            <x-admin.button tone="danger" icon="eye-slash" class="!h-[34px] !px-3.5 !text-[13.5px]" wire:click="ask('hide', {{ $post->id }})">{{ __('admin-content.community.hide') }}</x-admin.button>
                        @endif
                    </div>
                </x-admin.card>
            @endforeach
        </div>

        @if ($page->hasPages())
            <div>{{ $page->onEachSide(1)->links() }}</div>
        @endif
    @endif

    <flux:modal name="community-replies" class="w-full max-w-[640px]" wire:close="$set('repliesOf', null)">
        <div class="flex flex-col">
            <flux:heading size="lg" class="!text-[19px] !font-bold !text-admin-rail">{{ __('admin-content.community.replies_title') }}</flux:heading>

            @if ($repliesPost)
                <p class="mt-1.5 text-[13.5px] text-gray-600">«{{ $repliesPost->title }}» · {{ $repliesPost->author_name }}</p>

                @if ($repliesPost->hidden_at)
                    <p class="mt-3 rounded-lg border border-[#FFE0B2] bg-[#FFFBF4] px-3.5 py-2.5 text-[13px] text-gray-600">{{ __('admin-content.community.post_hidden_note') }}</p>
                @endif

                <div class="mt-4 flex max-h-[60vh] flex-col overflow-y-auto">
                    @forelse ($replies as $reply)
                        <div wire:key="reply-{{ $reply->id }}" class="flex items-start gap-3 border-b border-admin-row py-3.5 last:border-b-0">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="m-0 text-[14px] font-bold text-admin-rail">{{ $reply->author_name }}</p>
                                    <span class="text-xs text-gray-400">{{ $reply->created_at->locale('it')->diffForHumans() }}</span>
                                    @if ($reply->hidden_at)
                                        <x-admin.badge tone="muted">{{ __('admin-content.community.reply_hidden_badge') }}</x-admin.badge>
                                    @endif
                                </div>
                                <p @class(['mt-1 whitespace-pre-line text-[13.5px] leading-normal', 'text-gray-400 line-through' => $reply->hidden_at, 'text-[#1E2E33]' => ! $reply->hidden_at])>{{ $reply->body }}</p>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                @if ($reply->hidden_at)
                                    <x-admin.icon-action icon="arrow-uturn-left" :label="__('admin-content.community.restore')" wire:click="restoreReply({{ $reply->id }})" />
                                @else
                                    <x-admin.icon-action tone="suspend" icon="eye-slash" :label="__('admin-content.community.hide_reply')" wire:click="hideReply({{ $reply->id }})" />
                                @endif
                                <x-admin.icon-action tone="delete" icon="trash" :label="__('admin-content.community.delete')" wire:click="ask('delete_reply', {{ $reply->id }})" />
                            </div>
                        </div>
                    @empty
                        <x-admin.empty>{{ __('admin-content.community.replies_empty') }}</x-admin.empty>
                    @endforelse
                </div>
            @endif
        </div>
    </flux:modal>

    <flux:modal name="community-confirm" class="w-full max-w-[460px]" wire:close="$set('confirming', null)">
        @if ($confirmText)
            <div class="flex flex-col">
                <flux:heading size="lg" class="!text-[19px] !font-bold !text-admin-rail">{{ $confirmText['title'] }}</flux:heading>
                <p class="mt-3 text-[14.5px] leading-normal text-gray-600">{{ $confirmText['body'] }}</p>
                <div class="mt-6 flex flex-wrap justify-end gap-2.5">
                    <flux:modal.close>
                        <x-admin.button>{{ __('admin-content.common.cancel') }}</x-admin.button>
                    </flux:modal.close>
                    <x-admin.button tone="danger" wire:click="confirm">{{ $confirmText['button'] }}</x-admin.button>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
