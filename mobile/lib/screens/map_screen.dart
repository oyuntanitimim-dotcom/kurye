import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_map/flutter_map.dart' as fm;
import 'package:latlong2/latlong.dart' as ll;
import 'package:maplibre_gl/maplibre_gl.dart';

import 'package:kurye_mobile/core/app_scope.dart';
import 'package:kurye_mobile/core/map/map_style.dart';
import 'package:kurye_mobile/features/firm_admin/operations_api.dart';

/// Firma yöneticisi için [operationsApi] verilir; haritada aktif siparişlerin
/// restoran / varış noktaları ve konum paylaşan kuryeler gösterilir.
class MapScreen extends StatefulWidget {
  const MapScreen({super.key, this.operationsApi});

  final OperationsApi? operationsApi;

  @override
  State<MapScreen> createState() => _MapScreenState();
}

class _CourierPin {
  const _CourierPin({required this.id, required this.label, required this.point, required this.stale});
  final int id;
  final String label;
  final ll.LatLng point;
  final bool stale;
}

/// Web `operations.blade.php` (Leaflet) ile aynı: her sipariş için önce teslimat, sonra restoran çıkışı — aynı lokasyonda tekrarlayan marker kabul edilir.
class _OrderPins {
  const _OrderPins({
    required this.orderId,
    this.deliveryPoint,
    this.restaurantPoint,
    this.restaurantLabel,
  });

  final int orderId;
  final ll.LatLng? deliveryPoint;
  final ll.LatLng? restaurantPoint;
  final String? restaurantLabel;
}

class _FirmMapData {
  _FirmMapData({
    required this.couriers,
    required this.orders,
  });

  final List<_CourierPin> couriers;
  /// API sırasına göre; Leaflet döngüsü ile uyumlu
  final List<_OrderPins> orders;

  List<ll.LatLng> get allPoints {
    final pts = <ll.LatLng>[...couriers.map((e) => e.point)];
    for (final o in orders) {
      if (o.deliveryPoint != null) {
        pts.add(o.deliveryPoint!);
      }
      if (o.restaurantPoint != null) {
        pts.add(o.restaurantPoint!);
      }
    }
    return pts;
  }

  static _FirmMapData fromSnapshot(Map<String, dynamic> snap) {
    final couriersRaw = snap['couriers'];
    final courierPins = <_CourierPin>[];
    if (couriersRaw is List) {
      for (final row in couriersRaw) {
        if (row is! Map) continue;
        final m = Map<String, dynamic>.from(row);
        final id = int.tryParse((m['id'] ?? '').toString());
        final lat = _toDouble(m['lat']);
        final lng = _toDouble(m['lng']);
        if (id == null || lat == null || lng == null) continue;
        final name = (m['name'] ?? 'Kurye').toString().trim();
        final stale = m['is_stale'] == true;
        courierPins.add(_CourierPin(
          id: id,
          label: name.length > 14 ? '${name.substring(0, 14)}…' : name,
          point: ll.LatLng(lat, lng),
          stale: stale,
        ));
      }
    }

    final ordersRaw = snap['orders'];
    final ordersList = ordersRaw is List ? ordersRaw : const [];
    final orderPins = <_OrderPins>[];

    for (final row in ordersList) {
      if (row is! Map) continue;
      final o = Map<String, dynamic>.from(row);
      final orderId = int.tryParse((o['id'] ?? '').toString());
      if (orderId == null) continue;

      ll.LatLng? delPt;
      final cid = int.tryParse((o['courier_id'] ?? '').toString());
      final del = _asStringKeyedMap(o['delivery']);
      if (del != null && del['has_coordinates'] == true) {
        final rawShow = del['show_on_map'];
        final showDelivery = rawShow == true ||
            rawShow == 1 ||
            (rawShow == null &&
                cid != null &&
                cid >= 1); /* eski mobil uygulamalar için yedek kural */

        final dlat = _toDouble(del['lat']);
        final dlng = _toDouble(del['lng']);
        if (showDelivery && dlat != null && dlng != null) {
          delPt = ll.LatLng(dlat, dlng);
        }
      }

      ll.LatLng? restPt;
      String? restLabel;
      final rest = _asStringKeyedMap(o['restaurant']);
      if (rest != null) {
        final rlat = _toDouble(rest['lat']);
        final rlng = _toDouble(rest['lng']);
        final rname = (rest['name'] ?? 'Restoran').toString().trim();
        if (rlat != null && rlng != null) {
          restPt = ll.LatLng(rlat, rlng);
          restLabel = rname.length > 22 ? '${rname.substring(0, 22)}…' : rname;
        }
      }

      orderPins.add(_OrderPins(
        orderId: orderId,
        deliveryPoint: delPt,
        restaurantPoint: restPt,
        restaurantLabel: restLabel,
      ));
    }

    return _FirmMapData(
      couriers: courierPins,
      orders: orderPins,
    );
  }
}

double? _toDouble(dynamic v) {
  if (v == null) return null;
  if (v is num) return v.toDouble();
  return double.tryParse(v.toString());
}

Map<String, dynamic>? _asStringKeyedMap(dynamic v) {
  if (v is! Map) return null;
  return Map<String, dynamic>.from(v);
}

class _MapScreenState extends State<MapScreen> {
  MapLibreMapController? _controller;
  Timer? _poll;
  /// Her başarılı snapshot sonrası artar; sadece harita marker katmanını yeniletir (tam sayfa yok).
  int _firmDataGeneration = 0;
  _FirmMapData? _firmData;
  Object? _snapError;
  final fm.MapController _firmMapController = fm.MapController();
  bool _didFitFirmOpsMap = false;

  bool get _firmMap => widget.operationsApi != null;

  @override
  void initState() {
    super.initState();
    if (_firmMap) {
      _loadSnapshot();
      _poll = Timer.periodic(const Duration(seconds: 10), (_) => _loadSnapshot());
    }
  }

  @override
  void dispose() {
    _poll?.cancel();
    super.dispose();
  }

  Future<void> _loadSnapshot() async {
    final api = widget.operationsApi;
    if (api == null) return;
    try {
      final snap = await api.snapshot();
      if (!mounted) return;
      final parsed = _FirmMapData.fromSnapshot(snap);
      setState(() {
        _firmData = parsed;
        _firmDataGeneration++;
        _snapError = null;
      });
      _scheduleFitFirmOpsMap();
    } catch (e) {
      if (!mounted) return;
      setState(() => _snapError = e);
    }
  }

  void _scheduleFitFirmOpsMap() {
    if (!_firmMap || !mounted) return;
    WidgetsBinding.instance.addPostFrameCallback((_) => _fitFirmOpsMapIfNeeded());
  }

  /// İlk veri geldiğinde kamera tüm noktaları kapsar (web panel `fitBounds` ile aynı fikir); sonra kullanıcı zoom’unu korur.
  void _fitFirmOpsMapIfNeeded() {
    if (_didFitFirmOpsMap || !mounted) return;
    final pts = _firmData?.allPoints ?? const <ll.LatLng>[];
    if (pts.isEmpty) return;
    try {
      if (pts.length == 1) {
        _firmMapController.move(pts.first, 14);
      } else {
        _firmMapController.fitCamera(fm.CameraFit.bounds(
          bounds: fm.LatLngBounds.fromPoints(pts),
          padding: const EdgeInsets.fromLTRB(48, 80, 48, 48),
          maxZoom: 15,
        ));
      }
      _didFitFirmOpsMap = true;
    } catch (_) {
      // fitCamera bazı sınırlarda hata verebilir; sessiz geç.
    }
  }

  Widget _firmLegend(ThemeData theme) {
    final bg = theme.colorScheme.surfaceContainerHighest.withValues(alpha: 0.92);
    Text chip(String text, Color dot) {
      return Text.rich(
        TextSpan(
          children: [
            WidgetSpan(
              alignment: PlaceholderAlignment.middle,
              child: Container(
                width: 8,
                height: 8,
                margin: const EdgeInsets.only(right: 4),
                decoration: BoxDecoration(color: dot, shape: BoxShape.circle),
              ),
            ),
            TextSpan(text: text, style: theme.textTheme.labelSmall?.copyWith(fontWeight: FontWeight.w700)),
          ],
        ),
      );
    }

    return Material(
      color: bg,
      borderRadius: BorderRadius.circular(10),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
        child: DefaultTextStyle.merge(
          style: theme.textTheme.labelSmall!,
          child: Wrap(
            spacing: 12,
            runSpacing: 4,
            crossAxisAlignment: WrapCrossAlignment.center,
            children: [
              chip('Kurye', const Color(0xFF2563eb)),
              chip('Restoran', const Color(0xFF16a34a)),
              chip('Varış', const Color(0xFFF97316)),
            ],
          ),
        ),
      ),
    );
  }

  /// Firma paneli web Leaflet sırası: kuryeler → siparişler (teslim → restoran)
  List<fm.Marker> _firmOpsMarkers(_FirmMapData data, ThemeData theme) {
    final baseSmall = theme.textTheme.labelSmall?.copyWith(
      fontWeight: FontWeight.w800,
      shadows: const [Shadow(blurRadius: 3, color: Colors.black45)],
      color: Colors.white,
    );

    Widget pin(Color color, String label, {double maxWidth = 96}) {
      return Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 20,
            height: 20,
            decoration: BoxDecoration(
              color: color,
              shape: BoxShape.circle,
              border: Border.all(color: Colors.white, width: 2),
              boxShadow: const [BoxShadow(blurRadius: 4, color: Colors.black26)],
            ),
          ),
          const SizedBox(height: 2),
          ConstrainedBox(
            constraints: BoxConstraints(maxWidth: maxWidth),
            child: Text(
              label,
              textAlign: TextAlign.center,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: baseSmall,
            ),
          ),
        ],
      );
    }

    final markers = <fm.Marker>[
      ...data.couriers.map(
        (k) => fm.Marker(
          key: ValueKey<String>('k${k.id}_${k.point.latitude}_${k.point.longitude}_${k.stale}'),
          point: k.point,
          width: 100,
          height: 52,
          alignment: Alignment.bottomCenter,
          child: pin(k.stale ? const Color(0xFF64748b) : const Color(0xFF2563eb), k.label),
        ),
      ),
    ];

    for (final o in data.orders) {
      if (o.deliveryPoint != null) {
        markers.add(
          fm.Marker(
            key: ValueKey<String>('dv${o.orderId}_${o.deliveryPoint!.latitude}_${o.deliveryPoint!.longitude}'),
            point: o.deliveryPoint!,
            width: 76,
            height: 46,
            alignment: Alignment.bottomCenter,
            child: pin(const Color(0xFFF97316), '#${o.orderId}', maxWidth: 72),
          ),
        );
      }
      if (o.restaurantPoint != null && o.restaurantLabel != null) {
        markers.add(
          fm.Marker(
            key: ValueKey<String>('rs${o.orderId}_${o.restaurantPoint!.latitude}_${o.restaurantPoint!.longitude}'),
            point: o.restaurantPoint!,
            width: 132,
            height: 52,
            alignment: Alignment.bottomCenter,
            child: pin(const Color(0xFF16a34a), o.restaurantLabel!, maxWidth: 128),
          ),
        );
      }
    }

    return markers;
  }

  bool _firmMapHasPins(_FirmMapData data) {
    return data.couriers.isNotEmpty ||
        data.orders.any((o) => o.deliveryPoint != null || o.restaurantPoint != null);
  }

  Widget _buildFirmOperationsMap(BuildContext context) {
    final config = AppScope.of(context).configStore.config;
    final url = config.tilesUrlTemplate.replaceAll('{s}', 'a');
    final theme = Theme.of(context);
    final data = _firmData;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Harita'),
        actions: [
          IconButton(
            tooltip: 'Yenile',
            onPressed: _loadSnapshot,
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      body: Stack(
        children: [
          fm.FlutterMap(
            mapController: _firmMapController,
            options: fm.MapOptions(
              initialCenter: const ll.LatLng(36.8399, 36.2310),
              initialZoom: 12,
              onMapReady: _fitFirmOpsMapIfNeeded,
            ),
            children: [
              fm.TileLayer(
                urlTemplate: url,
                userAgentPackageName: 'kurye_mobile',
              ),
              if (data != null && _firmMapHasPins(data))
                fm.MarkerLayer(
                  key: ValueKey<int>(_firmDataGeneration),
                  markers: _firmOpsMarkers(data, theme),
                ),
            ],
          ),
          if (_snapError != null)
            Positioned(
              left: 12,
              right: 12,
              top: 12,
              child: Material(
                color: theme.colorScheme.errorContainer,
                borderRadius: BorderRadius.circular(8),
                child: Padding(
                  padding: const EdgeInsets.all(10),
                  child: Text(
                    'Operasyon verisi alınamadı. Yenileyin.',
                    style: theme.textTheme.bodySmall?.copyWith(color: theme.colorScheme.onErrorContainer),
                  ),
                ),
              ),
            ),
          Positioned(
            left: 12,
            right: 12,
            bottom: 16,
            child: Align(
              alignment: Alignment.center,
              child: _firmLegend(theme),
            ),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    if (_firmMap) {
      return _buildFirmOperationsMap(context);
    }

    final config = AppScope.of(context).configStore.config;
    final url = config.tilesUrlTemplate.replaceAll('{s}', 'a');

    if (kIsWeb) {
      return Scaffold(
        appBar: AppBar(title: const Text('Harita')),
        body: fm.FlutterMap(
          options: const fm.MapOptions(
            initialCenter: ll.LatLng(36.8399, 36.2310),
            initialZoom: 12,
          ),
          children: [
            fm.TileLayer(
              urlTemplate: url,
              userAgentPackageName: 'kurye_mobile',
            ),
          ],
        ),
      );
    }

    final style = buildRasterStyleJson(
      tilesUrlTemplate: config.tilesUrlTemplate,
      attribution: config.tilesAttribution,
    );

    return Scaffold(
      appBar: AppBar(title: const Text('Harita')),
      body: MapLibreMap(
        styleString: style,
        initialCameraPosition: const CameraPosition(
          target: LatLng(36.8399, 36.2310),
          zoom: 12.0,
        ),
        onMapCreated: (c) => setState(() => _controller = c),
        myLocationEnabled: true,
        myLocationTrackingMode: MyLocationTrackingMode.tracking,
        compassEnabled: true,
      ),
      floatingActionButton: _controller == null
          ? null
          : FloatingActionButton.small(
              onPressed: () {
                _controller?.animateCamera(
                  CameraUpdate.newLatLngZoom(const LatLng(36.8399, 36.2310), 12),
                );
              },
              child: const Icon(Icons.my_location),
            ),
    );
  }
}
