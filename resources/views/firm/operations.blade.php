@extends('layouts.firm')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css">
<h1 class="text-2xl font-semibold mb-4">{{ $title }}</h1>
<p class="text-sm text-slate-600 mb-4">Canlı özet ve harita (OpenStreetMap). Restoran <strong>Kurye çağır</strong> dedikten sonra <strong>hazır</strong> ve kuryesiz siparişte ilk atama; <strong>kurye atandı / alındı / yolda</strong> iken aynı panelden <strong>kurye değiştirme</strong>.</p>
@if(!$geoRedisEnabled)
<p class="text-amber-900 text-sm mb-4 rounded-lg bg-amber-50 px-3 py-2 border border-amber-200">
    Redis GEO kapalı; yalnızca hazır ve kuryesiz siparişlerde yakın kurye önerisi üretilmez.
    Açmak için <code class="rounded bg-amber-100 px-1">COURIER_GEO_REDIS=true</code> — bkz. <code class="rounded bg-amber-100 px-1">config/courier.php</code>.
</p>
@endif
<p class="text-xs text-slate-500 mb-4">Yakın kurye önerisi için taze konum limiti: <span id="op-max-age" class="font-medium text-slate-700">—</span></p>
<div class="mb-4 flex flex-wrap gap-2">
    <button type="button" id="op-refresh" class="touch-manipulation rounded-lg bg-slate-900 px-4 py-2.5 text-sm text-white hover:bg-slate-800 sm:py-2">Yenile</button>
    <button type="button" id="op-fit" class="touch-manipulation rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 sm:py-2">Haritayı sığdır</button>
</div>
<div class="grid gap-4 lg:grid-cols-2 mb-6">
    <div class="rounded-xl border border-slate-200 bg-white overflow-hidden min-h-[240px] sm:min-h-[280px] lg:min-h-[320px]">
        <div id="op-map" class="h-[min(22rem,52svh)] min-h-[240px] sm:h-[360px] sm:min-h-0 w-full z-0"></div>
        <p class="text-xs text-slate-500 px-3 py-2 border-t border-slate-100">
            <span class="inline-block w-3 h-3 rounded-full bg-blue-600 align-middle mr-1"></span> Kurye
            <span class="inline-block w-3 h-3 rounded-full bg-orange-600 align-middle ml-3 mr-1"></span> Teslimat
            <span class="inline-block w-3 h-3 rounded-full bg-green-600 align-middle ml-3 mr-1"></span> Firma (çıkış noktası)
        </p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
        <p class="font-medium text-slate-800 mb-2">Kısayol</p>
        <p>Tabloda kurye seçip <strong>Ata</strong> / <strong>Değiştir</strong> ile güncelleme; satıra (link/dışı) tıklayınca haritada o siparişe odaklanır. Haritada teslimat/firma pinine tıklayınca satır vurgulanır. Vurgu birkaç saniye sonra kalkar; <kbd class="rounded border border-slate-300 bg-white px-1">Esc</kbd> ile de temizlenir.</p>
        <div class="mt-3 rounded-lg border border-slate-200 bg-white p-3">
            <p class="text-xs font-medium text-slate-700 mb-1">Seçili öğe</p>
            <div id="op-selection" class="text-xs text-slate-600">
                <span class="text-slate-400">—</span>
            </div>
        </div>
    </div>
</div>
<div class="overflow-x-auto rounded-xl border border-slate-200 bg-white text-sm">
    <table class="w-full" id="op-orders">
        <thead>
            <tr class="text-left text-slate-500 border-b">
                <th class="py-2 px-4">#</th>
                <th class="py-2">Kanal</th>
                <th class="py-2">Durum</th>
                <th class="py-2">Restoran</th>
                <th class="py-2 max-w-[min(320px,32vw)]">Adres</th>
                <th class="py-2">Yakın kurye</th>
                <th class="py-2 pr-4">Kurye</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>
@php
    // Use relative paths to avoid localhost <-> 127.0.0.1 origin mismatches.
    $siparisBase = '/firma/siparisler/';
    $otomatikAtaBase = '/firma/siparisler';
    $tileUrl = (string) config('map.tiles.url', 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png');
    $tileAttribution = (string) config('map.tiles.attribution', '&copy; OpenStreetMap');
@endphp
<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
    const siparisBase = @json($siparisBase);
    const otomatikAtaBase = @json($otomatikAtaBase);
    const csrfToken = @json(csrf_token());
    const tb = document.querySelector('#op-orders tbody');
    const btn = document.getElementById('op-refresh');
    const fitBtn = document.getElementById('op-fit');
    const maxAgeEl = document.getElementById('op-max-age');
    const selectionEl = document.getElementById('op-selection');
    const nearbyCouriersBase = '/firma/operasyon/kuryeler/';
    let map = null;
    let markersLayer = null;
    // İşaretçileri kimliğe göre saklayıp konumu yerinde güncelliyoruz (harita yenilenince zoom/konum korunur).
    const courierMarkers = new Map();
    const orderDeliveryMarkers = new Map();
    const orderRestaurantMarkers = new Map();
    let mapFittedOnce = false;
    const REASSIGN_STATUSES = ['courier_assigned', 'courier_accepted', 'picked_up', 'on_the_way'];
    let lastSnapshot = null;
    let highlightClearTimer = null;
    const seenPendingOrderIds = new Set();
    let pendingSnapInitialized = false;

    function setSelectionHtml(html) {
        if (!selectionEl) return;
        selectionEl.innerHTML = html || '<span class="text-slate-400">—</span>';
    }

    function setSelectedOrder(orderId) {
        if (!lastSnapshot || !lastSnapshot.orders) return;
        const o = (lastSnapshot.orders || []).find(function (x) { return Number(x.id) === Number(orderId); });
        if (!o) return;
        const rest = o.restaurant && o.restaurant.name ? o.restaurant.name : '—';
        const status = o.status_label || o.status || '—';
        const channel = o.source_label || o.source || '—';
        setSelectionHtml(
            '<div class="flex items-center justify-between gap-2">' +
                '<div class="min-w-0">' +
                    '<div class="text-slate-800 font-medium truncate">Sipariş #' + o.id + '</div>' +
                    '<div class="text-slate-500 truncate">' + rest + ' · ' + status + ' · ' + channel + '</div>' +
                '</div>' +
                '<a class="shrink-0 text-amber-700 hover:underline" href="' + (siparisBase + o.id) + '">Aç</a>' +
            '</div>'
        );
    }

    function setSelectedCourier(courierId) {
        if (!lastSnapshot || !lastSnapshot.couriers) return;
        const c = (lastSnapshot.couriers || []).find(function (x) { return Number(x.id) === Number(courierId); });
        if (!c) return;
        const name = c.name ? c.name : ('#' + c.id);
        const stale = c.is_stale ? ' (konum eski)' : '';
        setSelectionHtml(
            '<div class="text-slate-800 font-medium">Kurye #' + c.id + (c.name ? (' — ' + c.name) : '') + '</div>' +
            '<div class="text-slate-500">Durum: ' + (c.status || '—') + stale + '</div>' +
            '<div class="mt-2 text-[11px] text-slate-500">Yakın kuryeler yükleniyor…</div>'
        );

        fetch(nearbyCouriersBase + encodeURIComponent(String(courierId)) + '/yakin', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (d) {
                if (!d) {
                    return;
                }
                if (d.ok === false && d.reason === 'geo_disabled') {
                    setSelectionHtml(
                        '<div class="text-slate-800 font-medium">Kurye #' + c.id + (c.name ? (' — ' + c.name) : '') + '</div>' +
                        '<div class="text-slate-500">Durum: ' + (c.status || '—') + stale + '</div>' +
                        '<div class="mt-2 text-[11px] text-amber-700">Yakın kurye için Redis GEO kapalı.</div>'
                    );
                    return;
                }
                const list = d.couriers || [];
                if (!list.length) {
                    setSelectionHtml(
                        '<div class="text-slate-800 font-medium">Kurye #' + c.id + (c.name ? (' — ' + c.name) : '') + '</div>' +
                        '<div class="text-slate-500">Durum: ' + (c.status || '—') + stale + '</div>' +
                        '<div class="mt-2 text-[11px] text-slate-500">Yakında uygun kurye yok.</div>'
                    );
                    return;
                }

                const pills = list.map(function (x) {
                    return '<button type="button" class="inline-flex items-center rounded-full bg-slate-100 px-2 py-1 text-[11px] font-medium text-slate-700 hover:bg-slate-200" data-near-courier-id="' + x.id + '">' +
                        '#' + x.id + (x.name ? (' ' + x.name) : '') +
                        '</button>';
                }).join(' ');

                setSelectionHtml(
                    '<div class="text-slate-800 font-medium">Kurye #' + c.id + (c.name ? (' — ' + c.name) : '') + '</div>' +
                    '<div class="text-slate-500">Durum: ' + (c.status || '—') + stale + '</div>' +
                    '<div class="mt-2 text-[11px] text-slate-500">Yakın kuryeler</div>' +
                    '<div class="mt-1 flex flex-wrap gap-1">' + pills + '</div>'
                );

                // click on a nearby courier pill => highlight that courier's orders on table
                selectionEl.querySelectorAll('button[data-near-courier-id]').forEach(function (btn) {
                    btn.addEventListener('click', function (ev) {
                        ev.preventDefault();
                        const id = btn.getAttribute('data-near-courier-id');
                        if (!id) return;
                        highlightCourierRows(Number(id));
                    });
                });
            })
            .catch(function () {
                // ignore
            });
    }

    function clearRowHighlight() {
        tb.querySelectorAll('tr.op-row-flash').forEach(function (el) {
            el.classList.remove('op-row-flash', 'bg-amber-100');
        });
    }

    function scheduleHighlightClear() {
        if (highlightClearTimer) {
            clearTimeout(highlightClearTimer);
        }
        highlightClearTimer = setTimeout(function () {
            highlightClearTimer = null;
            clearRowHighlight();
        }, 4500);
    }

    function highlightOrderRow(orderId) {
        clearRowHighlight();
        const tr = tb.querySelector('tr[data-order-id="' + orderId + '"]');
        if (!tr) {
            return;
        }
        tr.classList.add('bg-amber-100', 'op-row-flash');
        setSelectedOrder(orderId);
        scheduleHighlightClear();
    }

    function highlightCourierRows(courierId) {
        clearRowHighlight();
        setSelectedCourier(courierId);
        const orders = lastSnapshot && lastSnapshot.orders ? lastSnapshot.orders : [];
        let scrolled = false;
        orders.forEach(function (o) {
            if (Number(o.courier_id) !== Number(courierId)) {
                return;
            }
            const tr = tb.querySelector('tr[data-order-id="' + o.id + '"]');
            if (!tr) {
                return;
            }
            tr.classList.add('bg-amber-100', 'op-row-flash');
            scrolled = true;
        });
        scheduleHighlightClear();
    }

    function focusOrderOnMap(order) {
        if (!map) {
            return;
        }
        const pts = [];
        if (order.delivery && order.delivery.lat != null && order.delivery.lng != null) {
            pts.push([order.delivery.lat, order.delivery.lng]);
        }
        if (order.restaurant && order.restaurant.lat != null && order.restaurant.lng != null) {
            pts.push([order.restaurant.lat, order.restaurant.lng]);
        }
        if (!pts.length && order.courier_id && lastSnapshot) {
            const c = (lastSnapshot.couriers || []).find(function (x) { return x.id === order.courier_id; });
            if (c && c.lat != null && c.lng != null) {
                pts.push([c.lat, c.lng]);
            }
        }
        if (!pts.length) {
            return;
        }
        if (pts.length === 1) {
            map.setView(pts[0], 15);
        } else {
            map.fitBounds(pts, { padding: [40, 40], maxZoom: 16 });
        }
    }

    function ensureMap() {
        if (map) {
            return;
        }
        map = L.map('op-map');
        L.tileLayer(@json($tileUrl), {
            maxZoom: 19,
            attribution: @json($tileAttribution),
        }).addTo(map);
        markersLayer = L.layerGroup().addTo(map);
        // Default fallback center: Hatay / Dörtyol (demo-friendly)
        map.setView([36.8399, 36.2310], 12);
    }

    function upsertMarker(store, key, lat, lng, opts, tooltip, onClick) {
        let m = store.get(key);
        if (m) {
            m.setLatLng([lat, lng]);
            if (tooltip) {
                m.setTooltipContent(tooltip);
            }
        } else {
            m = L.circleMarker([lat, lng], opts);
            if (tooltip) {
                m.bindTooltip(tooltip);
            }
            if (onClick) {
                m.on('click', onClick);
            }
            markersLayer.addLayer(m);
            store.set(key, m);
        }
        return m;
    }

    function pruneMarkers(store, seenKeys) {
        store.forEach(function (m, key) {
            if (!seenKeys.has(key)) {
                markersLayer.removeLayer(m);
                store.delete(key);
            }
        });
    }

    function collectBounds() {
        const bounds = [];
        [courierMarkers, orderDeliveryMarkers, orderRestaurantMarkers].forEach(function (store) {
            store.forEach(function (m) {
                const ll = m.getLatLng();
                bounds.push([ll.lat, ll.lng]);
            });
        });
        return bounds;
    }

    function fitAll() {
        if (!map) {
            return;
        }
        const bounds = collectBounds();
        if (bounds.length) {
            map.fitBounds(bounds, { padding: [28, 28], maxZoom: 15 });
        }
    }

    // Sadece işaretçi konumlarını günceller; kullanıcı zoom/kaydırmasını korur.
    function renderMap(d) {
        ensureMap();

        const seenCouriers = new Set();
        (d.couriers || []).forEach(function (c) {
            if (c.lat != null && c.lng != null && !Number.isNaN(c.lat) && !Number.isNaN(c.lng)) {
                const key = Number(c.id);
                upsertMarker(
                    courierMarkers, key, c.lat, c.lng,
                    { radius: 8, color: '#2563eb', weight: 2, fillColor: '#3b82f6', fillOpacity: 0.85 },
                    'Kurye #' + c.id + (c.name ? ' — ' + c.name : ''),
                    function () { highlightCourierRows(c.id); }
                );
                seenCouriers.add(key);
            }
        });
        pruneMarkers(courierMarkers, seenCouriers);

        const seenDelivery = new Set();
        const seenRestaurant = new Set();
        (d.orders || []).forEach(function (o) {
            const delOk = o.delivery && o.delivery.lat != null && o.delivery.lng != null && (o.delivery.show_on_map === true || o.delivery.show_on_map === 1 || o.delivery.show_on_map === '1');
            if (delOk) {
                const key = Number(o.id);
                upsertMarker(
                    orderDeliveryMarkers, key, o.delivery.lat, o.delivery.lng,
                    { radius: 9, color: '#ea580c', weight: 2, fillColor: '#f97316', fillOpacity: 0.85 },
                    'Sipariş #' + o.id + ' (teslimat)',
                    function () { highlightOrderRow(o.id); }
                );
                seenDelivery.add(key);
            }
            if (o.restaurant && o.restaurant.lat != null && o.restaurant.lng != null) {
                const key = Number(o.id);
                upsertMarker(
                    orderRestaurantMarkers, key, o.restaurant.lat, o.restaurant.lng,
                    { radius: 7, color: '#16a34a', weight: 2, fillColor: '#22c55e', fillOpacity: 0.75 },
                    (o.restaurant.name || 'Restoran') + ' (çıkış)',
                    function () { highlightOrderRow(o.id); }
                );
                seenRestaurant.add(key);
            }
        });
        pruneMarkers(orderDeliveryMarkers, seenDelivery);
        pruneMarkers(orderRestaurantMarkers, seenRestaurant);

        // Yalnızca ilk veri geldiğinde haritayı sığdır; sonraki yenilemelerde kullanıcı görünümü korunur.
        if (!mapFittedOnce && collectBounds().length) {
            fitAll();
            mapFittedOnce = true;
        }
        setTimeout(function () { map.invalidateSize(); }, 100);
    }

    function autoDispatchOrder(orderId) {
        const fd = new FormData();
        fd.append('_token', csrfToken);
        return fetch(otomatikAtaBase + '/' + orderId + '/otomatik-ata', {
            method: 'POST',
            body: fd,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html,application/json' },
        }).then(function (r) {
            if (!r.ok) {
                throw new Error('HTTP ' + r.status);
            }
            return r;
        });
    }

    function assignCourier(orderId, courierId) {
        const fd = new FormData();
        fd.append('courier_id', String(courierId));
        fd.append('_token', csrfToken);
        return fetch(siparisBase + orderId + '/kurye', {
            method: 'POST',
            body: fd,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html,application/json' },
        }).then(function (r) {
            if (!r.ok) {
                throw new Error('HTTP ' + r.status);
            }
            return r;
        });
    }

    function buildTable(d) {
        tb.replaceChildren();
        const couriers = d.couriers || [];
        const courierById = new Map();
        couriers.forEach(function (c) {
            courierById.set(Number(c.id), c);
        });
        (d.orders || []).forEach(function (o) {
            const tr = document.createElement('tr');
            tr.className = 'border-b border-slate-100 transition-colors cursor-pointer';
            tr.setAttribute('data-order-id', String(o.id));
            tr.addEventListener('click', function (ev) {
                if (ev.target.closest('a, select, button')) {
                    return;
                }
                setSelectedOrder(o.id);
                focusOrderOnMap(o);
            });
            const td1 = document.createElement('td');
            td1.className = 'py-2 px-4';
            const a = document.createElement('a');
            a.href = siparisBase + o.id;
            a.className = 'text-amber-700 hover:underline';
            a.textContent = '#' + o.id;
            td1.appendChild(a);
            const tdCh = document.createElement('td');
            tdCh.className = 'py-2 text-slate-600 text-xs';
            tdCh.textContent = o.source_label || (o.source || '—');
            const td2 = document.createElement('td');
            td2.textContent = o.status_label || '';
            const td3 = document.createElement('td');
            td3.className = 'py-2 align-top';
            td3.textContent = (o.restaurant && o.restaurant.name) ? o.restaurant.name : '—';
            const tdAddr = document.createElement('td');
            tdAddr.className = 'py-2 align-top max-w-[min(320px,42vw)] text-xs text-slate-700 break-words whitespace-normal';
            tdAddr.title = (o.delivery && o.delivery.address) ? o.delivery.address : '';
            tdAddr.textContent = (o.delivery && o.delivery.address) ? o.delivery.address : '—';
            const td4 = document.createElement('td');
            const sug = o.suggested_nearby_courier_ids;
            if (sug && sug.length) {
                const wrap = document.createElement('div');
                wrap.className = 'flex flex-wrap gap-1 py-1';
                sug.forEach(function (cid) {
                    const c = courierById.get(Number(cid));
                    const pill = document.createElement('span');
                    const isStale = c && c.is_stale;
                    pill.className = isStale
                        ? 'inline-flex items-center gap-1 rounded-full bg-rose-50 px-2 py-1 text-[11px] font-medium text-rose-700 border border-rose-100'
                        : 'inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-1 text-[11px] font-medium text-slate-700';
                    pill.textContent = c && c.name ? ('#' + cid + ' ' + c.name) : ('#' + cid);
                    wrap.appendChild(pill);
                });
                td4.appendChild(wrap);
            } else {
                td4.textContent = '—';
            }

            const td5 = document.createElement('td');
            td5.className = 'py-2 pr-4';
            const canFirstAssign = o.status === 'ready' && (o.courier_id === null || o.courier_id === undefined) && o.restaurant_courier_requested;
            const canReassign = REASSIGN_STATUSES.indexOf(o.status) !== -1 && o.courier_id != null;
            const showCourierUi = (canFirstAssign || canReassign) && couriers.length;
            if (showCourierUi) {
                const autoOn = d.firm && d.firm.auto_dispatch_enabled;
                if (canFirstAssign && autoOn) {
                    const autoBtn = document.createElement('button');
                    autoBtn.type = 'button';
                    autoBtn.className = 'rounded bg-slate-700 px-2 py-1 text-xs text-white hover:bg-slate-800 mr-2';
                    autoBtn.textContent = 'Otomatik ata';
                    autoBtn.addEventListener('click', function (ev) {
                        ev.stopPropagation();
                        autoBtn.disabled = true;
                        autoDispatchOrder(o.id)
                            .then(function () { loadOp(); })
                            .catch(function () { alert('Otomatik atama başarısız oldu.'); })
                            .finally(function () { autoBtn.disabled = false; });
                    });
                    td5.appendChild(autoBtn);
                }
                const sel = document.createElement('select');
                sel.className = 'rounded border border-slate-300 px-2 py-1 text-xs mr-2 max-w-[140px]';
                const opt0 = document.createElement('option');
                opt0.value = '';
                opt0.textContent = '— Kurye —';
                sel.appendChild(opt0);
                couriers.forEach(function (c) {
                    const opt = document.createElement('option');
                    opt.value = String(c.id);
                    opt.textContent = '#' + c.id + (c.name ? ' ' + c.name : '');
                    sel.appendChild(opt);
                });
                if (canReassign) {
                    sel.value = String(o.courier_id);
                }
                const go = document.createElement('button');
                go.type = 'button';
                go.className = 'rounded bg-amber-600 px-2 py-1 text-xs text-white hover:bg-amber-700';
                go.textContent = canReassign ? 'Değiştir' : 'Ata';
                go.addEventListener('click', function () {
                    const cid = sel.value;
                    if (!cid) {
                        return;
                    }
                    go.disabled = true;
                    assignCourier(o.id, cid)
                        .then(function () { loadOp(); })
                        .catch(function () { alert('İşlem başarısız oldu.'); })
                        .finally(function () { go.disabled = false; });
                });
                td5.appendChild(sel);
                td5.appendChild(go);
            } else if ((canFirstAssign || canReassign) && !couriers.length) {
                td5.textContent = 'Aktif kurye yok';
                td5.className += ' text-slate-400 text-xs';
            } else {
                td5.textContent = '—';
                td5.className += ' text-slate-400';
            }

            tr.append(td1, tdCh, td2, td3, tdAddr, td4, td5);
            tb.appendChild(tr);
        });
    }

    let opLoading = false;
    function loadOp() {
        // Onceki istek bitmeden yenisini baslatma (5 sn aralikta ust uste binmeyi onler).
        if (opLoading) {
            return;
        }
        opLoading = true;
        fetch(@json(route('firm.operations.snapshot', [], false)), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
            .then(function (r) {
                const ct = (r.headers.get('content-type') || '').toLowerCase();
                if (!r.ok || ct.indexOf('application/json') === -1) {
                    return r.text().then(function (txt) {
                        const e = new Error('snapshot_failed');
                        e.status = r.status;
                        e.contentType = ct;
                        e.bodyPreview = (txt || '').slice(0, 180);
                        throw e;
                    });
                }
                return r.json();
            })
            .then(function (d) {
                lastSnapshot = d;
                if (maxAgeEl && d.settings && d.settings.location_max_age_minutes) {
                    maxAgeEl.textContent = String(d.settings.location_max_age_minutes) + ' dk';
                }
                const pendingIds = (d.orders || []).filter(function (o) {
                    return o.status === 'pending';
                }).map(function (o) {
                    return o.id;
                });
                if (!pendingSnapInitialized) {
                    pendingIds.forEach(function (id) {
                        seenPendingOrderIds.add(id);
                    });
                    pendingSnapInitialized = true;
                } else {
                    let anyNew = false;
                    pendingIds.forEach(function (id) {
                        if (!seenPendingOrderIds.has(id)) {
                            anyNew = true;
                            seenPendingOrderIds.add(id);
                        }
                    });
                    if (anyNew && window.__kuryeOrderChime) {
                        window.__kuryeOrderChime.play();
                        if (document.hidden && window.__kuryeOrderChime.flashTitle) {
                            window.__kuryeOrderChime.flashTitle();
                        }
                    }
                }
                buildTable(d);
                renderMap(d);
            })
            .catch(function (e) {
                tb.replaceChildren();
                const tr = document.createElement('tr');
                const td = document.createElement('td');
                td.colSpan = 6;
                td.className = 'py-4 px-4 text-red-700';
                const status = e && e.status ? ('HTTP ' + e.status) : '';
                const hint = (e && e.status === 302) || (e && e.status === 401) || (e && e.status === 419)
                    ? 'Oturum/host (localhost ↔ 127.0.0.1) uyuşmazlığı olabilir.'
                    : 'Snapshot endpoint kontrol edilmeli.';
                td.textContent = 'Özet yüklenemedi. ' + (status ? (status + '. ') : '') + hint;
                tr.appendChild(td);
                tb.appendChild(tr);
            })
            .finally(function () {
                opLoading = false;
            });
    }

    btn.addEventListener('click', loadOp);
    if (fitBtn) {
        fitBtn.addEventListener('click', fitAll);
    }
    document.addEventListener('keydown', function (ev) {
        if (ev.key !== 'Escape') {
            return;
        }
        if (highlightClearTimer) {
            clearTimeout(highlightClearTimer);
            highlightClearTimer = null;
        }
        clearRowHighlight();
        setSelectionHtml('');
    });
    loadOp();
    setInterval(loadOp, 5000);
    window.addEventListener('kurye-firm-board-changed', function () {
        loadOp();
    });
})();
</script>
@endsection
