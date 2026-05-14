import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:maplibre_gl/maplibre_gl.dart';
import 'package:url_launcher/url_launcher.dart';

import 'package:kurye_mobile/core/app_scope.dart';
import 'package:kurye_mobile/core/map/map_style.dart';

class CourierOrderDetailScreen extends StatefulWidget {
  const CourierOrderDetailScreen({super.key, required this.order});

  final Map<String, dynamic> order;

  @override
  State<CourierOrderDetailScreen> createState() => _CourierOrderDetailScreenState();
}

class _CourierOrderDetailScreenState extends State<CourierOrderDetailScreen> {
  MapLibreMapController? _c;
  int? _etaSeconds;
  LatLng? _courierPt;
  LatLng? _deviceCourierPt;

  LatLng? _parseLatLngFromMap(dynamic m) {
    if (m is! Map) return null;
    final lat = double.tryParse((m['latitude'] ?? m['lat'] ?? '').toString());
    final lng = double.tryParse((m['longitude'] ?? m['lng'] ?? '').toString());
    if (lat == null || lng == null) return null;
    return LatLng(lat, lng);
  }

  Future<LatLng?> _getCourierLocationIfPermitted() async {
    try {
      final enabled = await Geolocator.isLocationServiceEnabled();
      if (!enabled) return null;

      var perm = await Geolocator.checkPermission();
      if (perm == LocationPermission.denied) {
        perm = await Geolocator.requestPermission();
      }
      if (perm != LocationPermission.always && perm != LocationPermission.whileInUse) {
        return null;
      }

      final pos = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.best,
          timeLimit: Duration(seconds: 6),
        ),
      );

      return LatLng(pos.latitude, pos.longitude);
    } catch (_) {
      return null;
    }
  }

  Future<void> _drawMarkersAndRoute(
    AppScope scope, {
    required LatLng? restPt,
    required LatLng? delPt,
    required bool isDark,
  }) async {
    final c = _c;
    if (c == null) return;

    final textColor = isDark ? '#e5e7eb' : '#0f172a';
    final haloColor = isDark ? '#0b1020' : '#ffffff';

    // Style yüklenmeden çizim yaparsak MapLibre bazı cihazlarda çizimleri yutuyor.
    try {
      // Not: Raster style JSON çoğu zaman sprite içermez; iconImage kullanılan symbol'ler görünmeyebilir.
      // Bu yüzden pinleri Circle ile, etiketleri sadece text Symbol ile çiziyoruz.
      // Öncelik: sunucudaki son paylaşılan kurye lokasyonu (firma paneli ile tutarlı).
      final serverCourier = _parseLatLngFromMap(widget.order['courier_location']);
      final deviceCourier = await _getCourierLocationIfPermitted();
      _deviceCourierPt = deviceCourier;

      // Öncelik: server → device fallback.
      final courier = serverCourier ?? deviceCourier;
      if (courier != null) {
        _courierPt = courier;
        if (mounted) setState(() {});
        await c.addCircle(CircleOptions(
          geometry: courier,
          circleRadius: 7,
          circleColor: '#64748b',
          circleStrokeWidth: 2,
          circleStrokeColor: isDark ? '#0b1020' : '#0f172a',
        ));
        await c.addSymbol(SymbolOptions(
          geometry: courier,
          textField: 'Kurye',
          textSize: 12,
          textOffset: const Offset(0, 1.4),
          textColor: textColor,
          textHaloColor: haloColor,
          textHaloWidth: 1.5,
        ));
      }

      if (restPt != null) {
        await c.addCircle(CircleOptions(
          geometry: restPt,
          circleRadius: 7,
          circleColor: '#16a34a',
          circleStrokeWidth: 2,
          circleStrokeColor: '#14532d',
        ));
        await c.addSymbol(SymbolOptions(
          geometry: restPt,
          textField: 'Restoran',
          textSize: 12,
          textOffset: const Offset(0, 1.4),
          textColor: textColor,
          textHaloColor: haloColor,
          textHaloWidth: 1.5,
        ));
      }
      if (delPt != null) {
        await c.addCircle(CircleOptions(
          geometry: delPt,
          circleRadius: 8,
          circleColor: '#f97316',
          circleStrokeWidth: 2,
          circleStrokeColor: '#9a3412',
        ));
        await c.addSymbol(SymbolOptions(
          geometry: delPt,
          textField: 'Müşteri',
          textSize: 12,
          textOffset: const Offset(0, 1.4),
          textColor: textColor,
          textHaloColor: haloColor,
          textHaloWidth: 1.5,
        ));
      }

      // Kamera: mevcut tüm noktaları kapsa (kurye dahil).
      final pts = <LatLng>[
        if (_courierPt != null) _courierPt!,
        if (restPt != null) restPt,
        if (delPt != null) delPt,
      ];
      if (pts.isNotEmpty) {
        var minLat = pts.first.latitude, maxLat = pts.first.latitude;
        var minLng = pts.first.longitude, maxLng = pts.first.longitude;
        for (final p in pts) {
          if (p.latitude < minLat) minLat = p.latitude;
          if (p.latitude > maxLat) maxLat = p.latitude;
          if (p.longitude < minLng) minLng = p.longitude;
          if (p.longitude > maxLng) maxLng = p.longitude;
        }
        await c.animateCamera(
          CameraUpdate.newLatLngBounds(
            LatLngBounds(
              southwest: LatLng(minLat, minLng),
              northeast: LatLng(maxLat, maxLng),
            ),
            left: 52,
            top: 52,
            right: 52,
            bottom: 52,
          ),
        );
      }

      if (restPt != null && delPt != null) {
        await _loadRoute(scope, restPt, delPt);
      }
    } catch (_) {
      // ignore
    }
  }

  @override
  Widget build(BuildContext context) {
    final scope = AppScope.of(context);
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    final cfg = scope.configStore.config;
    final style = buildRasterStyleJson(
      tilesUrlTemplate: cfg.tilesUrlTemplate,
      attribution: cfg.tilesAttribution,
    );

    final order = widget.order;
    final restaurant = (order['restaurant'] is Map) ? Map<String, dynamic>.from(order['restaurant'] as Map) : null;
    final delivery = (order['delivery_address'] is Map) ? Map<String, dynamic>.from(order['delivery_address'] as Map) : null;

    LatLng? pt(Map<String, dynamic>? m) {
      if (m == null) return null;
      final lat = double.tryParse((m['latitude'] ?? m['lat'] ?? '').toString());
      final lng = double.tryParse((m['longitude'] ?? m['lng'] ?? '').toString());
      if (lat == null || lng == null) return null;
      return LatLng(lat, lng);
    }

    final restPt = pt(restaurant);
    final delPt = pt(delivery);

    final initial = delPt ?? restPt ?? const LatLng(36.8399, 36.2310);

    return Scaffold(
      appBar: AppBar(
        title: Text('Sipariş #${order['id']}'),
        actions: [
          IconButton(
            tooltip: 'Navigasyon',
            onPressed: delPt == null ? null : () => _openNavigation(delPt),
            icon: const Icon(Icons.navigation),
          ),
          IconButton(
            tooltip: 'Konumu sunucuya gönder',
            onPressed: () async {
              final pt = await _getCourierLocationIfPermitted();
              if (pt == null || !context.mounted) return;
              try {
                await scope.api.dio.post('/api/v1/courier/location', data: {
                  'latitude': pt.latitude,
                  'longitude': pt.longitude,
                });
                if (!context.mounted) return;
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(content: Text('Konum gönderildi. Listeyi yenileyin.')),
                );
              } catch (_) {
                if (!context.mounted) return;
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(content: Text('Konum gönderilemedi.')),
                );
              }
            },
            icon: const Icon(Icons.my_location),
          ),
        ],
      ),
      body: Column(
        children: [
          Expanded(
            child: MapLibreMap(
              styleString: style,
              initialCameraPosition: CameraPosition(target: initial, zoom: 14),
              onMapCreated: (c) async {
                _c = c;
              },
              onStyleLoadedCallback: () async {
                await _drawMarkersAndRoute(
                  scope,
                  restPt: restPt,
                  delPt: delPt,
                  isDark: isDark,
                );
              },
            ),
          ),
          Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  (restaurant?['name'] ?? '—').toString(),
                  style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w900),
                ),
                const SizedBox(height: 6),
                Text('Durum: ${(order['status'] ?? '—').toString()}'),
                if (_courierPt != null) ...[
                  const SizedBox(height: 6),
                  Text('Kurye konumu: ${_courierPt!.latitude.toStringAsFixed(5)}, ${_courierPt!.longitude.toStringAsFixed(5)}'),
                ],
                if (_deviceCourierPt != null &&
                    (order['courier_location'] is Map) &&
                    _parseLatLngFromMap(order['courier_location']) != null) ...[
                  const SizedBox(height: 4),
                  Text(
                    'Cihaz konumu: ${_deviceCourierPt!.latitude.toStringAsFixed(5)}, ${_deviceCourierPt!.longitude.toStringAsFixed(5)}',
                    style: theme.textTheme.bodySmall,
                  ),
                ],
                if (_etaSeconds != null) ...[
                  const SizedBox(height: 6),
                  Text('ETA: ${_formatEta(_etaSeconds!)}'),
                ],
                const SizedBox(height: 10),
                if (delivery != null) Text('Adres: ${(delivery['address'] ?? '—').toString()}'),
                const SizedBox(height: 10),
                Wrap(
                  spacing: 10,
                  runSpacing: 6,
                  children: [
                    const _LegendChip(color: Color(0xFF64748B), label: 'Kurye'),
                    const _LegendChip(color: Color(0xFF16A34A), label: 'Restoran'),
                    const _LegendChip(color: Color(0xFFF97316), label: 'Müşteri'),
                  ],
                ),
                const SizedBox(height: 10),
                Row(
                  children: [
                    Expanded(
                      child: FilledButton(
                        onPressed: delPt == null ? null : () => _openNavigation(delPt),
                        child: const Text('Navigasyon aç'),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _loadRoute(AppScope scope, LatLng from, LatLng to) async {
    try {
      final r = await scope.api.dio.post('/api/v1/routing/route', data: {
        'from_lat': from.latitude,
        'from_lng': from.longitude,
        'to_lat': to.latitude,
        'to_lng': to.longitude,
      });
      final data = r.data;
      if (data is! Map) return;
      if (data['ok'] != true) return;

      final geom = data['geometry'];
      if (geom is! Map) return;
      final coords = geom['coordinates'];
      if (coords is! List) return;
      final pts = <LatLng>[];
      for (final p in coords) {
        if (p is List && p.length >= 2) {
          final lng = double.tryParse(p[0].toString());
          final lat = double.tryParse(p[1].toString());
          if (lat != null && lng != null) {
            pts.add(LatLng(lat, lng));
          }
        }
      }
      if (pts.length < 2) return;

      setState(() {
        _etaSeconds = int.tryParse((data['duration_seconds'] ?? '').toString());
      });

      final theme = Theme.of(context);
      final isDark = theme.brightness == Brightness.dark;
      final lineColor = isDark ? '#60a5fa' : '#0b3a7a';

      if (_c != null) {
        await _c!.addLine(LineOptions(
          geometry: pts,
          lineColor: lineColor,
          lineWidth: 4,
          lineOpacity: 0.85,
        ));
      }
    } catch (_) {
      // ignore (OSRM may be disabled)
    }
  }

  String _formatEta(int seconds) {
    final m = (seconds / 60).round();
    if (m < 60) return '$m dk';
    final h = (m / 60).floor();
    final mm = m % 60;
    return '${h}s ${mm}dk';
  }

  Future<void> _openNavigation(LatLng dest) async {
    // Use a universal Google Maps direction URL; on iOS it can open Apple Maps depending on user settings.
    final uri = Uri.parse('https://www.google.com/maps/dir/?api=1&destination=${dest.latitude},${dest.longitude}');
    await launchUrl(uri, mode: LaunchMode.externalApplication);
  }
}

class _LegendChip extends StatelessWidget {
  const _LegendChip({required this.color, required this.label});
  final Color color;
  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: color.withOpacity(0.12),
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: color.withOpacity(0.35)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 10,
            height: 10,
            decoration: BoxDecoration(color: color, shape: BoxShape.circle),
          ),
          const SizedBox(width: 8),
          Text(label, style: Theme.of(context).textTheme.labelSmall?.copyWith(fontWeight: FontWeight.w700)),
        ],
      ),
    );
  }
}

