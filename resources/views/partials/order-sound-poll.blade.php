{{-- Periyodik poll + tarayıcı ses kilidi (kullanıcı tıklaması gerekir). window.__kuryeOrderChime.play() operasyon sayfası vb. için. --}}
@php
    $pollUrl = (string) ($pollUrl ?? '');
    $intervalMs = max(5000, (int) ($intervalMs ?? 12000));
    $alertExternalOrdersOnly = (bool) ($alertExternalOrdersOnly ?? false);
@endphp
@if($pollUrl !== '')
<div id="kurye-sound-banner" class="fixed bottom-4 right-4 z-[60] max-w-sm rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950 shadow-lg">
    <p class="font-semibold">Sipariş ses uyarısı</p>
    <p class="mt-1 text-xs leading-relaxed text-amber-900/90">
        @if($alertExternalOrdersOnly)
            Chrome / Edge otomatik sesi engelleyebilir; bir kez tıklayın. <strong>Pazar yeri / mağaza</strong> gibi dış kanaldan gelen yeni <strong>bekleyen</strong> siparişte bip çalar (telefon veya dükkân siparişi için değil).
        @else
            Chrome / Edge, sayfa açılınca otomatik ses çalmayı engeller. Aşağıya bir kez tıklayın; yeni <strong>bekleyen</strong> sipariş geldiğinde kısa bip duyulur (sekme başlığı da kısa süre yanıp söner).
        @endif
    </p>
    <button type="button" id="kurye-sound-enable" class="mt-2 w-full touch-manipulation rounded-lg bg-amber-600 px-3 py-2 text-xs font-medium text-white hover:bg-amber-700">
        Ses bildirimini aç
    </button>
</div>
<script>
(function () {
    var pollUrl = @json($pollUrl);
    var intervalMs = {{ $intervalMs }};
    var alertExternalOrdersOnly = {{ $alertExternalOrdersOnly ? 'true' : 'false' }};
    var banner = document.getElementById('kurye-sound-banner');
    var enableBtn = document.getElementById('kurye-sound-enable');
    var baseTitle = document.title;
    var titleTimer = null;

    if (!pollUrl) {
        return;
    }

    if (!window.__kuryeOrderChime) {
        window.__kuryeOrderChime = {
            unlocked: false,
            play: function () {
                if (!this.unlocked) {
                    return;
                }
                try {
                    var Ctx = window.AudioContext || window.webkitAudioContext;
                    if (!Ctx) {
                        return;
                    }
                    var ctx = new Ctx();
                    var o = ctx.createOscillator();
                    var g = ctx.createGain();
                    o.type = 'sine';
                    o.frequency.setValueAtTime(880, ctx.currentTime);
                    o.frequency.setValueAtTime(660, ctx.currentTime + 0.12);
                    g.gain.setValueAtTime(0.0001, ctx.currentTime);
                    g.gain.exponentialRampToValueAtTime(0.12, ctx.currentTime + 0.02);
                    g.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.28);
                    o.connect(g);
                    g.connect(ctx.destination);
                    o.start(ctx.currentTime);
                    o.stop(ctx.currentTime + 0.3);
                    setTimeout(function () { ctx.close(); }, 400);
                } catch (e) {}
            },
            flashTitle: function () {
                var on = true;
                var n = 0;
                var prefix = alertExternalOrdersOnly ? '● Yeni dış sipariş — ' : '● Yeni sipariş — ';
                if (titleTimer) {
                    clearInterval(titleTimer);
                }
                titleTimer = setInterval(function () {
                    document.title = on ? prefix + baseTitle : baseTitle;
                    on = !on;
                    n++;
                    if (n >= 8) {
                        clearInterval(titleTimer);
                        titleTimer = null;
                        document.title = baseTitle;
                    }
                }, 650);
            }
        };
    }

    function hideBanner() {
        if (banner) {
            banner.classList.add('hidden');
        }
    }

    function unlock() {
        window.__kuryeOrderChime.unlocked = true;
        window.__kuryeOrderChime.play();
        hideBanner();
        try {
            localStorage.setItem('kurye_order_sound_unlocked', '1');
        } catch (e) {}
    }

    try {
        if (localStorage.getItem('kurye_order_sound_unlocked') === '1') {
            window.__kuryeOrderChime.unlocked = true;
            hideBanner();
        }
    } catch (e) {}

    if (enableBtn) {
        enableBtn.addEventListener('click', unlock);
    }

    var sinceId = null;

    function tick() {
        var url = sinceId === null
            ? pollUrl + (pollUrl.indexOf('?') === -1 ? '?' : '&') + 'bootstrap=1'
            : pollUrl + (pollUrl.indexOf('?') === -1 ? '?' : '&') + 'since_id=' + encodeURIComponent(String(sinceId));
        fetch(url, {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (!d || typeof d.max_id !== 'number') {
                    return;
                }
                var newForSound = alertExternalOrdersOnly ? !!d.new_external_pending : !!d.new_pending;
                if (sinceId !== null && newForSound && window.__kuryeOrderChime) {
                    window.__kuryeOrderChime.play();
                    if (document.hidden) {
                        window.__kuryeOrderChime.flashTitle();
                    }
                }
                sinceId = d.max_id;
            })
            .catch(function () {});
    }

    tick();
    setInterval(tick, intervalMs);
})();
</script>
@endif
