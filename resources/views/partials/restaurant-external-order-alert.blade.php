{{-- Ses kilidi + 2,5 sn poll; dış bekleyen varken çan rengi + tıklanana kadar tekrarlayan bip --}}
@php
    $pollUrl = route('restaurant.orders.poll');
    $pollMs = 2600;
    $alarmRepeatMs = 2200;
@endphp
<div id="kurye-sound-banner" class="fixed bottom-4 right-4 z-[60] max-w-sm rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950 shadow-lg">
    <p class="font-semibold">Dış sipariş sesi</p>
    <p class="mt-1 text-xs leading-relaxed text-amber-900/90">
        Tarayıcı otomatik sesi engelleyebilir; bir kez <strong>Sesi aç</strong> deyin. Dış kanaldan yeni bekleyen sipariş gelince çan uyarır; <strong>çana tıklayınca</strong> ses durur ve Göstergeye gidersiniz. Sonradan <strong>yeni</strong> bir sipariş (daha büyük numara) gelince uyarı yeniden açılır.
    </p>
    <button type="button" id="kurye-sound-enable" class="mt-2 w-full touch-manipulation rounded-lg bg-amber-600 px-3 py-2 text-xs font-medium text-white hover:bg-amber-700">
        Sesi aç
    </button>
</div>
<script>
(function () {
    var pollUrl = @json($pollUrl);
    var pollMs = {{ (int) $pollMs }};
    var alarmRepeatMs = {{ (int) $alarmRepeatMs }};
    var dashboardUrl = @json(route('restaurant.dashboard'));
    var banner = document.getElementById('kurye-sound-banner');
    var enableBtn = document.getElementById('kurye-sound-enable');
    var bell = document.getElementById('restaurant-watch-bell');
    var badge = document.getElementById('restaurant-watch-badge');
    var baseTitle = document.title;
    var titleTimer = null;
    var sinceId = null;
    var alarmTimer = null;
    var ACK_KEY = 'restaurant_bell_ack_max_id';
    var LEGACY_MUTED_KEY = 'restaurant_bell_muted';

    function getAckMaxId() {
        try {
            var v = sessionStorage.getItem(ACK_KEY);
            return v ? parseInt(v, 10) : 0;
        } catch (e) {
            return 0;
        }
    }

    function setAckMaxId(id) {
        try {
            sessionStorage.setItem(ACK_KEY, String(id));
        } catch (e) {}
    }

    function clearAck() {
        try {
            sessionStorage.removeItem(ACK_KEY);
            sessionStorage.removeItem(LEGACY_MUTED_KEY);
        } catch (e) {}
    }

    try {
        if (sessionStorage.getItem(LEGACY_MUTED_KEY) === '1') {
            sessionStorage.removeItem(LEGACY_MUTED_KEY);
        }
    } catch (e) {}
    var wasAlerting = false;

    function hideBanner() {
        if (banner) {
            banner.classList.add('hidden');
        }
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
                var prefix = '● Dış sipariş — ';
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

    function stopAlarm() {
        if (alarmTimer) {
            clearInterval(alarmTimer);
            alarmTimer = null;
        }
    }

    function startAlarm() {
        stopAlarm();
        alarmTimer = setInterval(function () {
            if (window.__kuryeOrderChime && window.__kuryeOrderChime.unlocked) {
                window.__kuryeOrderChime.play();
            }
        }, alarmRepeatMs);
    }

    function setBellIdle() {
        if (!bell) {
            return;
        }
        bell.classList.remove('animate-pulse', 'bg-amber-100', 'border-amber-400', 'border-amber-500', 'text-amber-900', 'text-amber-950', 'ring-2', 'ring-amber-300', 'ring-amber-400', 'bg-amber-50', 'border-amber-200', 'text-amber-700', 'text-amber-800');
        bell.classList.add('restaurant-watch-bell--idle', 'border-slate-200', 'bg-white', 'text-slate-600', 'shadow-sm');
        if (badge) {
            badge.classList.add('hidden');
        }
    }

    function setBellAlert() {
        if (!bell) {
            return;
        }
        bell.classList.remove(
            'restaurant-watch-bell--idle',
            'border-slate-200',
            'bg-white',
            'text-slate-600',
            'shadow-sm',
            'border-amber-200',
            'bg-amber-50',
            'text-amber-800'
        );
        bell.classList.add('animate-pulse', 'border-amber-500', 'bg-amber-100', 'text-amber-950', 'ring-2', 'ring-amber-400');
        if (badge) {
            badge.classList.remove('hidden');
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

    function goDashboardUnlessCurrent() {
        try {
            var target = new URL(dashboardUrl, window.location.href);
            var cur = (window.location.pathname || '/').replace(/\/$/, '') || '/';
            var next = (target.pathname || '/').replace(/\/$/, '') || '/';
            if (cur !== next) {
                window.location.assign(dashboardUrl);
            } else {
                window.location.reload();
            }
        } catch (e) {
            window.location.assign(dashboardUrl);
        }
    }

    if (bell) {
        bell.addEventListener('click', function (ev) {
            ev.preventDefault();
            if (sinceId !== null) {
                setAckMaxId(sinceId);
            }
            stopAlarm();
            setBellIdle();
            goDashboardUnlessCurrent();
        });
    }

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
                var maxId = d.max_id;
                sinceId = maxId;
                var cnt = parseInt(String(d.external_pending_count ?? 0), 10) || 0;
                var ack = getAckMaxId();
                var alerting = cnt > 0 && maxId > ack;

                if (cnt === 0) {
                    clearAck();
                    stopAlarm();
                    setBellIdle();
                    wasAlerting = false;
                    return;
                }

                if (alerting) {
                    setBellAlert();
                    if (window.__kuryeOrderChime && window.__kuryeOrderChime.unlocked) {
                        if (!alarmTimer) {
                            window.__kuryeOrderChime.play();
                            startAlarm();
                        }
                    }
                    if (!wasAlerting && alerting && document.hidden && window.__kuryeOrderChime) {
                        window.__kuryeOrderChime.flashTitle();
                    }
                    wasAlerting = true;
                } else {
                    setBellIdle();
                    stopAlarm();
                    wasAlerting = false;
                }
            })
            .catch(function () {});
    }

    tick();
    setInterval(tick, pollMs);
})();
</script>
