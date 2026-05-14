@extends($layout)

@section('content')
<h1 class="text-2xl font-semibold mb-6">Bildirimler</h1>

@if($hasUnread)
    <form method="post" action="{{ route('notifications.markAllRead') }}" class="mb-6">
        @csrf
        <button type="submit" class="text-sm text-amber-700 hover:underline">Tümünü okundu işaretle</button>
    </form>
@endif

<div class="space-y-3 max-w-3xl">
    @forelse($notifications as $n)
        <div class="rounded-xl border p-4 {{ $n->status === 'unread' ? 'border-amber-200 bg-amber-50/50' : 'border-slate-200 bg-white' }}">
            <div class="flex justify-between gap-4">
                <div>
                    <p class="font-medium">{{ $n->title }}</p>
                    <p class="text-sm text-slate-600 mt-1">{{ $n->message }}</p>
                    <p class="text-xs text-slate-400 mt-2">{{ $n->created_at?->format('d.m.Y H:i') }}</p>
                </div>
                @if($n->status === 'unread')
                    <form method="post" action="{{ route('notifications.markRead', $n) }}" class="shrink-0">
                        @csrf
                        <button type="submit" class="text-xs text-amber-700 hover:underline">Okundu</button>
                    </form>
                @endif
            </div>
        </div>
    @empty
        <p class="text-slate-500">Bildirim yok.</p>
    @endforelse
</div>
<div class="mt-6">{{ $notifications->links() }}</div>
@endsection
