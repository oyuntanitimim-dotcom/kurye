{{-- İşletme üst menü: dış sipariş çanı (durum script ile güncellenir) --}}
<div id="restaurant-watch-wrap" class="relative flex shrink-0 items-center" title="Göstergeye git — dış siparişleri onaylayın">
    <button
        type="button"
        id="restaurant-watch-bell"
        class="restaurant-watch-bell--idle relative inline-flex h-10 w-10 touch-manipulation items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 shadow-sm transition-colors duration-200 hover:bg-slate-50"
        aria-label="Göstergeye git; dış sipariş uyarı sesini durdur"
    >
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
        </svg>
        <span id="restaurant-watch-badge" class="pointer-events-none absolute right-1 top-1 hidden h-2.5 w-2.5 rounded-full bg-red-500 ring-2 ring-white" aria-hidden="true"></span>
    </button>
</div>
