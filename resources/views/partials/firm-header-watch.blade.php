{{-- Firma üst menü: watch_board imzası değişince yalnızca operasyon (kurye-firm-board-changed). Çan uyarısı firm-courier-request-alert tarafından yönetilir. --}}
<div class="flex items-center gap-1" id="firm-header-watch-root">
    <button
        type="button"
        id="firm-watch-bell"
        class="firm-watch-bell--idle relative inline-flex h-10 w-10 touch-manipulation items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 shadow-sm transition-colors duration-200 hover:bg-slate-50"
        title="Gösterge — kurye çağrı uyarısı (çan)"
        aria-label="Gösterge; kurye çağrı uyarısı"
    >
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
        </svg>
    </button>
</div>
<script>
(function () {
    var url = @json(route('firm.orders.watch_board'));
    var intervalMs = 10000;
    var baseline = null;

    function tick() {
        fetch(url, {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (!d || typeof d.signature !== 'string') {
                    return;
                }
                if (baseline === null) {
                    baseline = d.signature;
                    return;
                }
                if (d.signature === baseline) {
                    return;
                }
                baseline = d.signature;
                try {
                    window.dispatchEvent(new CustomEvent('kurye-firm-board-changed', { detail: d }));
                } catch (e) {}
            })
            .catch(function () {});
    }

    tick();
    setInterval(tick, intervalMs);
})();
</script>
