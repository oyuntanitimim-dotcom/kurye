import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:geolocator/geolocator.dart';
import 'package:kurye_mobile/core/app_scope.dart';
import 'package:kurye_mobile/core/location/foreground_location_service.dart';
import 'package:kurye_mobile/core/ui/app_background.dart';
import 'package:kurye_mobile/core/ui/app_content.dart';
import 'package:kurye_mobile/core/ui/glass_card.dart';
import 'package:kurye_mobile/features/courier/courier_api.dart';
import 'package:kurye_mobile/features/courier/courier_order_detail_screen.dart';
import 'package:url_launcher/url_launcher.dart';

/// API `status` değerini kısa Türkçe etikete çevirir (pill / başlık için).
String courierOrderStatusLabel(String status) {
  switch (status) {
    case 'courier_assigned':
      return 'Kurye atandı';
    case 'courier_accepted':
      return 'Kabul etti';
    case 'picked_up':
      return 'Alındı';
    case 'on_the_way':
      return 'Yolda';
    case 'delivered':
      return 'Teslim edildi';
    case 'cancelled':
      return 'İptal';
    default:
      return status;
  }
}

class CourierOrdersScreen extends StatefulWidget {
  const CourierOrdersScreen({super.key, required this.api});

  final CourierApi api;

  @override
  State<CourierOrdersScreen> createState() => _CourierOrdersScreenState();
}

/// Ana sayfaya gömülebilen “Aktif siparişler” bölümü.
///
/// Not: Mevcut `CourierOrdersScreen`’in aynı iş kurallarını kullanır (kabul/teslim/navigasyon),
/// fakat sadece aktif siparişleri gösterir ve `ListView` içinde `shrinkWrap` çalışacak şekilde tasarlanmıştır.
class CourierOrdersSection extends StatefulWidget {
  const CourierOrdersSection({super.key, required this.api});

  final CourierApi api;

  @override
  State<CourierOrdersSection> createState() => _CourierOrdersSectionState();
}

class _CourierOrdersSectionState extends State<CourierOrdersSection> {
  bool _loading = true;
  List<dynamic> _orders = const [];
  String? _error;
  bool _sharing = false;
  DateTime? _lastSentAt;

  bool _polling = false;
  bool _pollingBusy = false;
  final Map<int, String> _lastKnownStatusById = <int, String>{};
  final Set<int> _seenOrderIds = <int>{};
  String _lastSignature = '';

  @override
  void initState() {
    super.initState();
    _load(silent: false);
    _startPolling();
  }

  @override
  void dispose() {
    _sharing = false;
    _polling = false;
    super.dispose();
  }

  Future<void> _startPolling() async {
    if (_polling) return;
    _polling = true;
    Future<void>(() async {
      while (_polling && mounted) {
        await Future<void>.delayed(const Duration(seconds: 3));
        if (!_polling || !mounted) break;
        if (_pollingBusy) continue;
        _pollingBusy = true;
        try {
          try {
            await _load(silent: true);
          } catch (_) {
            // Keep polling alive even if a request fails.
          }
        } finally {
          _pollingBusy = false;
        }
      }
    });
  }

  Future<void> _load({bool silent = false}) async {
    if (!silent) setState(() => _loading = true);
    List<dynamic> list;
    try {
      list = await widget.api.activeOrders();
    } catch (e) {
      if (!mounted) return;
      if (!silent) {
        setState(() {
          _loading = false;
          _error = 'Aktif işler alınamadı.';
        });
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Aktif işler alınamadı: ${e.toString()}')),
        );
      }
      return;
    }
    if (!mounted) return;
    _error = null;

    final sigParts = <String>[];
    for (final raw in list) {
      final o = raw is Map ? raw : null;
      if (o == null) continue;
      final id = (o['id'] ?? '').toString();
      final st = (o['status'] ?? '').toString();
      if (id.isEmpty) continue;
      sigParts.add('$id:$st');
    }
    final signature = sigParts.join('|');

    final alerts = <int>[];
    for (final raw in list) {
      if (raw is! Map) continue;
      final o = Map<String, dynamic>.from(raw);
      final id = int.tryParse((o['id'] ?? '').toString()) ?? 0;
      if (id == 0) continue;
      final status = (o['status'] ?? '').toString();

      final prevStatus = _lastKnownStatusById[id];
      final wasSeen = _seenOrderIds.contains(id);
      final shouldAlert = (!wasSeen && status == 'courier_assigned') ||
          (prevStatus != null && prevStatus != status && status == 'courier_assigned');

      _seenOrderIds.add(id);
      _lastKnownStatusById[id] = status;
      if (shouldAlert) alerts.add(id);
    }

    final changed = signature != _lastSignature;
    _lastSignature = signature;

    if (changed || !silent) {
      setState(() {
        _orders = list;
        _loading = false;
      });
    } else {
      _loading = false;
    }

    if (alerts.isNotEmpty) {
      await _playIncomingOrderAlert(alerts.first);
    }
  }

  Future<void> _playIncomingOrderAlert(int orderId) async {
    try {
      await SystemSound.play(SystemSoundType.alert);
      await HapticFeedback.mediumImpact();
    } catch (_) {}
    try {
      if (mounted) AppScope.of(context).notifications.incomingOrder(orderId: orderId);
    } catch (_) {}
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Yeni sipariş #$orderId size atandı.')),
    );
  }

  Future<void> _setStatus(int orderId, String status) async {
    await widget.api.updateOrderStatus(orderId: orderId, status: status);
    await _load(silent: true);
  }

  Future<void> _acceptAssignment(int orderId) async {
    await widget.api.acceptAssignment(orderId: orderId);
    await _load(silent: true);
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Görev kabul edildi.')));
  }

  Future<void> _declineAssignment(int orderId, String reason) async {
    await widget.api.declineAssignment(orderId: orderId, reason: reason);
    await _load(silent: true);
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Atama reddedildi; firma bilgilendirildi.')));
  }

  Future<void> _toggleSharing() async {
    if (_sharing) {
      setState(() => _sharing = false);
      await ForegroundLocationService.stop();
      return;
    }

    final enabled = await Geolocator.isLocationServiceEnabled();
    if (!enabled && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Konum servisi kapalı.')));
      return;
    }

    var perm = await Geolocator.checkPermission();
    if (perm == LocationPermission.denied) {
      perm = await Geolocator.requestPermission();
    }
    if (perm == LocationPermission.denied || perm == LocationPermission.deniedForever) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Konum izni gerekli.')));
      }
      return;
    }

    setState(() => _sharing = true);
    await ForegroundLocationService.start();
    while (_sharing && mounted) {
      try {
        final pos = await Geolocator.getCurrentPosition(
          locationSettings: const LocationSettings(
            accuracy: LocationAccuracy.best,
            timeLimit: Duration(seconds: 8),
          ),
        );
        await widget.api.postLocation(lat: pos.latitude, lng: pos.longitude);
        setState(() => _lastSentAt = DateTime.now());
      } catch (_) {}
      await Future<void>.delayed(const Duration(seconds: 10));
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final active = _orders.where((o) => _segmentOf(o) == _OrderSegment.active).toList(growable: false);

    return Column(
      children: [
        if (_error != null) ...[
          Padding(
            padding: const EdgeInsets.only(bottom: 10),
            child: GlassCard(
              padding: const EdgeInsets.all(12),
              child: Row(
                children: [
                  Icon(Icons.wifi_off, color: theme.colorScheme.error),
                  const SizedBox(width: 10),
                  Expanded(child: Text(_error!, style: theme.textTheme.bodySmall)),
                  TextButton(onPressed: () => _load(silent: false), child: const Text('Dene')),
                ],
              ),
            ),
          ),
        ],
        GlassCard(
          padding: const EdgeInsets.all(12),
          child: Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      _sharing ? 'Konum paylaşımı açık' : 'Konum paylaşımı kapalı',
                      style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w900),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      _lastSentAt == null
                          ? 'Henüz konum gönderilmedi.'
                          : 'Son gönderim: ${_lastSentAt!.toLocal().toString().substring(11, 19)}',
                      style: theme.textTheme.bodySmall,
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 10),
              FilledButton(
                onPressed: _toggleSharing,
                style: FilledButton.styleFrom(
                  backgroundColor: theme.colorScheme.primary.withValues(alpha: 0.90),
                  foregroundColor: Colors.white,
                ),
                child: Text(_sharing ? 'Kapat' : 'Aç'),
              ),
              const SizedBox(width: 6),
              IconButton(onPressed: () => _load(silent: false), icon: const Icon(Icons.refresh)),
            ],
          ),
        ),
        const SizedBox(height: 10),
        if (_loading)
          const Padding(
            padding: EdgeInsets.symmetric(vertical: 18),
            child: Center(child: CircularProgressIndicator()),
          )
        else if (active.isEmpty)
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 14),
            child: Text('Aktif sipariş yok.', style: theme.textTheme.bodySmall),
          )
        else
          ListView.builder(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            padding: EdgeInsets.zero,
            itemCount: active.length,
            itemBuilder: (context, i) {
              final raw = active[i];
              if (raw is! Map) {
                return const SizedBox.shrink();
              }
              final o = Map<String, dynamic>.from(raw);
              return Padding(
                padding: const EdgeInsets.only(bottom: 10),
                child: _CourierOrderCard(
                  order: o,
                  onSetStatus: _setStatus,
                  onAcceptAssignment: _acceptAssignment,
                  onDeclineAssignment: _declineAssignment,
                ),
              );
            },
          ),
      ],
    );
  }
}

class _CourierOrderCard extends StatelessWidget {
  const _CourierOrderCard({
    required this.order,
    required this.onSetStatus,
    required this.onAcceptAssignment,
    required this.onDeclineAssignment,
  });

  final Map<String, dynamic> order;
  final Future<void> Function(int orderId, String status) onSetStatus;
  final Future<void> Function(int orderId) onAcceptAssignment;
  final Future<void> Function(int orderId, String reason) onDeclineAssignment;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final id = int.tryParse(order['id'].toString()) ?? 0;
    final status = (order['status'] ?? '').toString();
    final rest = (order['restaurant'] is Map) ? (order['restaurant']['name'] ?? '—').toString() : '—';
    final delivery = (order['delivery_address'] is Map) ? Map<String, dynamic>.from(order['delivery_address'] as Map) : null;
    final customer = (order['customer'] is Map) ? Map<String, dynamic>.from(order['customer'] as Map) : null;
    final customerName = (order['customer_name'] ?? customer?['name'] ?? '—').toString();
    final customerPhone = (order['customer_phone'] ?? customer?['phone'] ?? '').toString().trim();
    final addressText = (delivery?['address'] ?? '').toString().trim();
    final notes = (order['notes'] ?? '').toString().trim();
    final fee = _asNum(order['delivery_fee'] ?? order['courier_fee'] ?? order['fee'] ?? order['payout']);

    final hasRoute = (() {
      try {
        final r = (order['restaurant'] is Map) ? Map<String, dynamic>.from(order['restaurant'] as Map) : null;
        final d = (order['delivery_address'] is Map) ? Map<String, dynamic>.from(order['delivery_address'] as Map) : null;
        double? lat(Map<String, dynamic>? m) => double.tryParse((m?['latitude'] ?? m?['lat'] ?? '').toString());
        double? lng(Map<String, dynamic>? m) => double.tryParse((m?['longitude'] ?? m?['lng'] ?? '').toString());
        return lat(r) != null && lng(r) != null && lat(d) != null && lng(d) != null;
      } catch (_) {
        return false;
      }
    })();

    return GlassCard(
      child: InkWell(
        borderRadius: BorderRadius.circular(18),
        onTap: id == 0
            ? null
            : () {
                Navigator.of(context).push(
                  MaterialPageRoute(
                    builder: (_) => CourierOrderDetailScreen(order: Map<String, dynamic>.from(order)),
                  ),
                );
              },
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  CircleAvatar(
                    radius: 16,
                    backgroundColor: theme.colorScheme.primary.withValues(alpha: 0.18),
                    child: const Icon(Icons.storefront_outlined, size: 18),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          rest,
                          style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w900),
                          overflow: TextOverflow.ellipsis,
                        ),
                        const SizedBox(height: 2),
                        Text('→ Müşteri', style: theme.textTheme.bodySmall),
                      ],
                    ),
                  ),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      _StatusPill(text: courierOrderStatusLabel(status)),
                      if (fee != null) ...[
                        const SizedBox(height: 6),
                        Text(
                          '₺${fee.toStringAsFixed(0)}',
                          style: theme.textTheme.titleSmall?.copyWith(
                            fontWeight: FontWeight.w900,
                            color: theme.colorScheme.primary,
                          ),
                        ),
                      ],
                    ],
                  ),
                ],
              ),
              const SizedBox(height: 10),
              Text(
                customerName,
                style: theme.textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.w800),
              ),
              const SizedBox(height: 6),
              Row(
                children: [
                  Expanded(
                    child: Text(
                      customerPhone.isEmpty ? 'Telefon: —' : customerPhone,
                      style: theme.textTheme.bodySmall,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                  IconButton(
                    tooltip: 'Kopyala',
                    onPressed: customerPhone.isEmpty
                        ? null
                        : () async {
                            await Clipboard.setData(ClipboardData(text: customerPhone));
                            if (!context.mounted) return;
                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(content: Text('Telefon kopyalandı.')),
                            );
                          },
                    icon: const Icon(Icons.copy),
                  ),
                  IconButton(
                    tooltip: 'Ara',
                    onPressed: customerPhone.isEmpty
                        ? null
                        : () async {
                            final uri = Uri.parse('tel:$customerPhone');
                            await launchUrl(uri, mode: LaunchMode.externalApplication);
                          },
                    icon: const Icon(Icons.call),
                  ),
                ],
              ),
              if (addressText.isNotEmpty) ...[
                const SizedBox(height: 2),
                Text(
                  addressText,
                  style: theme.textTheme.bodySmall,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
              ],
              if (notes.isNotEmpty) ...[
                const SizedBox(height: 6),
                Text(
                  'Not: $notes',
                  style: theme.textTheme.bodySmall,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
              ],
              const SizedBox(height: 10),
              _CourierOrderActions(
                orderId: id,
                status: status,
                onSetStatus: onSetStatus,
                onAcceptAssignment: onAcceptAssignment,
                onDeclineAssignment: onDeclineAssignment,
              ),
              const SizedBox(height: 10),
              Row(
                children: [
                  Expanded(
                    child: FilledButton.tonalIcon(
                      onPressed: id == 0
                          ? null
                          : () {
                              Navigator.of(context).push(
                                MaterialPageRoute(
                                  builder: (_) => CourierOrderDetailScreen(order: Map<String, dynamic>.from(order)),
                                ),
                              );
                            },
                      icon: const Icon(Icons.map_outlined),
                      label: const Text('Detay'),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: FilledButton(
                      onPressed: (!hasRoute || delivery == null)
                          ? null
                          : () async {
                              final lat = (delivery['latitude'] ?? delivery['lat']).toString();
                              final lng = (delivery['longitude'] ?? delivery['lng']).toString();
                              final uri = Uri.parse('https://www.google.com/maps/dir/?api=1&destination=$lat,$lng');
                              await launchUrl(uri, mode: LaunchMode.externalApplication);
                            },
                      style: FilledButton.styleFrom(
                        backgroundColor: theme.colorScheme.primary.withValues(alpha: 0.90),
                        foregroundColor: Colors.white,
                      ),
                      child: Text(hasRoute ? 'Navigasyon' : 'Rota yok'),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _CourierOrdersScreenState extends State<CourierOrdersScreen> {
  late final WidgetsBindingObserver _lifecycleObserver;
  bool _loading = true;
  List<dynamic> _orders = const [];
  String? _error;
  bool _sharing = false;
  DateTime? _lastSentAt;
  final Map<int, String> _lastKnownStatusById = <int, String>{};
  final Set<int> _seenOrderIds = <int>{};
  bool _polling = false;
  bool _pollingBusy = false;
  String _lastSignature = '';
  int? _popupOrderId;
  bool _popupOpen = false;
  _OrderSegment _segment = _OrderSegment.active;

  @override
  void initState() {
    super.initState();
    _load(silent: false);
    _startPolling();

    _lifecycleObserver = _CourierOrdersLifecycleObserver(onResumed: () {
      if (!mounted) return;
      _load(silent: true);
    });
    WidgetsBinding.instance.addObserver(_lifecycleObserver);
  }

  @override
  void dispose() {
    _sharing = false;
    _polling = false;
    WidgetsBinding.instance.removeObserver(_lifecycleObserver);
    super.dispose();
  }

  Future<void> _load({bool silent = false}) async {
    if (!silent) {
      setState(() => _loading = true);
    }
    List<dynamic> list;
    try {
      list = await widget.api.activeOrders();
    } catch (e) {
      if (!mounted) return;
      if (!silent) {
        setState(() {
          _loading = false;
          _error = 'Aktif işler alınamadı.';
        });
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Aktif işler alınamadı: ${e.toString()}')),
        );
      }
      return;
    }
    if (!mounted) return;
    _error = null;

    // Signature: orders id + status list. If unchanged, do nothing (prevents UI flicker).
    final sigParts = <String>[];
    for (final raw in list) {
      final o = raw is Map ? raw : null;
      if (o == null) continue;
      final id = (o['id'] ?? '').toString();
      final st = (o['status'] ?? '').toString();
      if (id.isEmpty) continue;
      sigParts.add('$id:$st');
    }
    final signature = sigParts.join('|');

    final alerts = <int>[];
    for (final raw in list) {
      final o = raw is Map ? raw : null;
      if (o == null) continue;
      final id = int.tryParse((o['id'] ?? '').toString()) ?? 0;
      if (id == 0) continue;
      final status = (o['status'] ?? '').toString();

      final prevStatus = _lastKnownStatusById[id];
      final wasSeen = _seenOrderIds.contains(id);

      // “Sipariş düştü” uyarısı: ilk kez listede görünmesi veya tekrar “kurye atandı”ya dönmesi.
      final shouldAlert = (!wasSeen && status == 'courier_assigned') ||
          (prevStatus != null && prevStatus != status && status == 'courier_assigned');

      _seenOrderIds.add(id);
      _lastKnownStatusById[id] = status;

      if (shouldAlert) {
        alerts.add(id);
      }
    }

    final changed = signature != _lastSignature;
    _lastSignature = signature;

    if (changed || !silent) {
      setState(() {
        _orders = list;
        _loading = false;
      });
    } else {
      // keep state; background poll should be invisible
      _loading = false;
    }

    if (alerts.isNotEmpty) {
      final first = alerts.first;
      _playIncomingOrderAlert(first);
      _maybeShowIncomingOrderPopup(first, list);
    }
  }

  void _maybeShowIncomingOrderPopup(int orderId, List<dynamic> list) {
    if (!mounted) return;
    if (_popupOpen) return;
    if (_popupOrderId == orderId) return;

    Map<String, dynamic>? order;
    for (final raw in list) {
      if (raw is! Map) continue;
      final m = Map<String, dynamic>.from(raw);
      final id = int.tryParse((m['id'] ?? '').toString()) ?? 0;
      if (id == orderId) {
        order = m;
        break;
      }
    }
    if (order == null) return;

    _popupOrderId = orderId;
    _popupOpen = true;

    WidgetsBinding.instance.addPostFrameCallback((_) async {
      if (!mounted) return;
      try {
        await showDialog<void>(
          context: context,
          barrierDismissible: false,
          builder: (_) => _IncomingOrderDialog(
            order: order!,
            onAccept: () async => _acceptAssignment(orderId),
            onDecline: () async => _declineAssignment(orderId, 'unavailable'),
          ),
        );
      } finally {
        _popupOpen = false;
        if (!mounted) return;
        Future<void>.delayed(const Duration(seconds: 3)).then((_) {
          if (!mounted) return;
          if (_popupOrderId == orderId) _popupOrderId = null;
        });
      }
    });
  }

  void _startPolling() {
    if (_polling) return;
    _polling = true;
    Future<void>(() async {
      while (_polling && mounted) {
        await Future<void>.delayed(const Duration(seconds: 3));
        if (!_polling || !mounted) break;
        if (_pollingBusy) continue;
        _pollingBusy = true;
        try {
          try {
            await _load(silent: true);
          } catch (_) {
            // Keep polling alive even if a request fails.
          }
        } finally {
          _pollingBusy = false;
        }
      }
    });
  }

  Future<void> _playIncomingOrderAlert(int orderId) async {
    try {
      // Web/desktop autoplay kısıtları olabilir; yine de deniyoruz.
      await SystemSound.play(SystemSoundType.alert);
      await HapticFeedback.mediumImpact();
    } catch (_) {
      // ignore
    }
    try {
      if (mounted) {
        AppScope.of(context).notifications.incomingOrder(orderId: orderId);
      }
    } catch (_) {}
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Yeni sipariş #$orderId size atandı.')),
    );
  }

  Future<void> _setStatus(int orderId, String status) async {
    await widget.api.updateOrderStatus(orderId: orderId, status: status);
    await _load(silent: true);
  }

  Future<void> _acceptAssignment(int orderId) async {
    await widget.api.acceptAssignment(orderId: orderId);
    await _load(silent: true);
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Görev kabul edildi.')));
  }

  Future<void> _declineAssignment(int orderId, String reason) async {
    await widget.api.declineAssignment(orderId: orderId, reason: reason);
    await _load(silent: true);
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Atama reddedildi; firma bilgilendirildi.')));
  }

  Future<void> _toggleSharing() async {
    if (_sharing) {
      setState(() => _sharing = false);
      return;
    }

    final enabled = await Geolocator.isLocationServiceEnabled();
    if (!enabled && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Konum servisi kapalı.')));
      return;
    }

    var perm = await Geolocator.checkPermission();
    if (perm == LocationPermission.denied) {
      perm = await Geolocator.requestPermission();
    }
    if (perm == LocationPermission.denied || perm == LocationPermission.deniedForever) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Konum izni gerekli.')));
      }
      return;
    }

    setState(() => _sharing = true);

    while (_sharing && mounted) {
      try {
        final pos = await Geolocator.getCurrentPosition(
          locationSettings: const LocationSettings(
            accuracy: LocationAccuracy.best,
            timeLimit: Duration(seconds: 8),
          ),
        );
        await widget.api.postLocation(lat: pos.latitude, lng: pos.longitude);
        setState(() => _lastSentAt = DateTime.now());
      } catch (_) {
        // ignore transient errors in MVP
      }

      await Future<void>.delayed(const Duration(seconds: 10));
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final activeCount = _orders.where((o) => _segmentOf(o) == _OrderSegment.active).length;
    final doneCount = _orders.where((o) => _segmentOf(o) == _OrderSegment.done).length;
    final cancelledCount = _orders.where((o) => _segmentOf(o) == _OrderSegment.cancelled).length;
    final visible = _orders.where((o) => _segmentOf(o) == _segment).toList(growable: false);
    return Scaffold(
      backgroundColor: Colors.transparent,
      appBar: AppBar(
        title: const Text('Siparişlerim'),
        actions: [
          IconButton(
            onPressed: _toggleSharing,
            icon: Icon(_sharing ? Icons.location_off : Icons.location_on),
            tooltip: _sharing ? 'Konum kapat' : 'Konum paylaş',
          ),
          IconButton(onPressed: _load, icon: const Icon(Icons.refresh)),
        ],
      ),
      body: AppBackground(
        child: AppContent(
          child: _loading
              ? const Center(child: CircularProgressIndicator())
              : Column(
                  children: [
                    if (_error != null)
                      Padding(
                        padding: const EdgeInsets.fromLTRB(16, 10, 16, 8),
                        child: GlassCard(
                          padding: const EdgeInsets.all(12),
                          child: Row(
                            children: [
                              Icon(Icons.wifi_off, color: theme.colorScheme.error),
                              const SizedBox(width: 10),
                              Expanded(child: Text(_error!, style: theme.textTheme.bodySmall)),
                              TextButton(onPressed: _load, child: const Text('Dene')),
                            ],
                          ),
                        ),
                      ),
                    Padding(
                      padding: const EdgeInsets.fromLTRB(16, 10, 16, 8),
                      child: _SegmentBar(
                        segment: _segment,
                        activeCount: activeCount,
                        doneCount: doneCount,
                        cancelledCount: cancelledCount,
                        onChanged: (s) => setState(() => _segment = s),
                      ),
                    ),
                    Padding(
                      padding: const EdgeInsets.fromLTRB(16, 0, 16, 10),
                      child: GlassCard(
                        padding: const EdgeInsets.all(12),
                        child: Row(
                          children: [
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    _sharing ? 'Konum paylaşımı açık' : 'Konum paylaşımı kapalı',
                                    style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w900),
                                  ),
                                  const SizedBox(height: 4),
                                  Text(
                                    _lastSentAt == null
                                        ? 'Henüz konum gönderilmedi.'
                                        : 'Son gönderim: ${_lastSentAt!.toLocal().toString().substring(11, 19)}',
                                    style: theme.textTheme.bodySmall,
                                  ),
                                ],
                              ),
                            ),
                            const SizedBox(width: 10),
                            FilledButton(
                              onPressed: _toggleSharing,
                              style: FilledButton.styleFrom(
                                backgroundColor: theme.colorScheme.primary.withValues(alpha: 0.90),
                                foregroundColor: Colors.white,
                              ),
                              child: Text(_sharing ? 'Kapat' : 'Aç'),
                            ),
                          ],
                        ),
                      ),
                    ),
                    Expanded(
                      child: RefreshIndicator(
                        onRefresh: _load,
                        child: ListView.builder(
                          padding: const EdgeInsets.fromLTRB(16, 0, 16, 24),
                          itemCount: visible.length,
                          itemBuilder: (context, i) {
                          final o = visible[i] as Map? ?? const {};
                          final id = int.tryParse(o['id'].toString()) ?? 0;
                          final status = (o['status'] ?? '').toString();
                          final rest = (o['restaurant'] is Map) ? (o['restaurant']['name'] ?? '—').toString() : '—';
                          final delivery = (o['delivery_address'] is Map) ? Map<String, dynamic>.from(o['delivery_address'] as Map) : null;
                          final customer = (o['customer'] is Map) ? Map<String, dynamic>.from(o['customer'] as Map) : null;
                          final customerName = (o['customer_name'] ?? customer?['name'] ?? '—').toString();
                          final customerPhone = (o['customer_phone'] ?? customer?['phone'] ?? '').toString().trim();
                          final addressText = (delivery?['address'] ?? '').toString().trim();
                          final notes = (o['notes'] ?? '').toString().trim();
                          final fee = _asNum(o['delivery_fee'] ?? o['courier_fee'] ?? o['fee'] ?? o['payout']);

                          final hasRoute = (() {
                            try {
                              final r = (o['restaurant'] is Map) ? Map<String, dynamic>.from(o['restaurant'] as Map) : null;
                              final d = (o['delivery_address'] is Map) ? Map<String, dynamic>.from(o['delivery_address'] as Map) : null;
                              double? lat(Map<String, dynamic>? m) => double.tryParse((m?['latitude'] ?? m?['lat'] ?? '').toString());
                              double? lng(Map<String, dynamic>? m) => double.tryParse((m?['longitude'] ?? m?['lng'] ?? '').toString());
                              return lat(r) != null && lng(r) != null && lat(d) != null && lng(d) != null;
                            } catch (_) {
                              return false;
                            }
                          })();

                          return Padding(
                            padding: const EdgeInsets.only(bottom: 10),
                            child: GlassCard(
                              child: InkWell(
                                borderRadius: BorderRadius.circular(18),
                                onTap: id == 0
                                    ? null
                                    : () {
                                        Navigator.of(context).push(
                                          MaterialPageRoute(
                                            builder: (_) => CourierOrderDetailScreen(order: Map<String, dynamic>.from(o)),
                                          ),
                                        );
                                      },
                                child: Padding(
                                  padding: const EdgeInsets.all(12),
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Row(
                                        children: [
                                          CircleAvatar(
                                            radius: 16,
                                            backgroundColor: theme.colorScheme.primary.withValues(alpha: 0.18),
                                            child: const Icon(Icons.storefront_outlined, size: 18),
                                          ),
                                          const SizedBox(width: 10),
                                          Expanded(
                                            child: Column(
                                              crossAxisAlignment: CrossAxisAlignment.start,
                                              children: [
                                                Text(
                                                  rest,
                                                  style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w900),
                                                  overflow: TextOverflow.ellipsis,
                                                ),
                                                const SizedBox(height: 2),
                                                Text('→ Müşteri', style: theme.textTheme.bodySmall),
                                              ],
                                            ),
                                          ),
                                          Column(
                                            crossAxisAlignment: CrossAxisAlignment.end,
                                            children: [
                                              _StatusPill(text: courierOrderStatusLabel(status)),
                                              if (fee != null) ...[
                                                const SizedBox(height: 6),
                                                Text(
                                                  '₺${fee.toStringAsFixed(0)}',
                                                  style: theme.textTheme.titleSmall?.copyWith(
                                                    fontWeight: FontWeight.w900,
                                                    color: theme.colorScheme.primary,
                                                  ),
                                                ),
                                              ],
                                            ],
                                          ),
                                        ],
                                      ),
                                      const SizedBox(height: 10),
                                      Text(
                                        customerName,
                                        style: theme.textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.w800),
                                      ),
                                      const SizedBox(height: 6),
                                      Row(
                                        children: [
                                          Expanded(
                                            child: Text(
                                              customerPhone.isEmpty ? 'Telefon: —' : customerPhone,
                                              style: theme.textTheme.bodySmall,
                                              overflow: TextOverflow.ellipsis,
                                            ),
                                          ),
                                          IconButton(
                                            tooltip: 'Kopyala',
                                            onPressed: customerPhone.isEmpty
                                                ? null
                                                : () async {
                                                    await Clipboard.setData(ClipboardData(text: customerPhone));
                                                    if (!context.mounted) return;
                                                    ScaffoldMessenger.of(context).showSnackBar(
                                                      const SnackBar(content: Text('Telefon kopyalandı.')),
                                                    );
                                                  },
                                            icon: const Icon(Icons.copy),
                                          ),
                                          IconButton(
                                            tooltip: 'Ara',
                                            onPressed: customerPhone.isEmpty
                                                ? null
                                                : () async {
                                                    final uri = Uri.parse('tel:$customerPhone');
                                                    await launchUrl(uri, mode: LaunchMode.externalApplication);
                                                  },
                                            icon: const Icon(Icons.call),
                                          ),
                                        ],
                                      ),
                                      if (addressText.isNotEmpty) ...[
                                        const SizedBox(height: 2),
                                        Text(
                                          addressText,
                                          style: theme.textTheme.bodySmall,
                                          maxLines: 2,
                                          overflow: TextOverflow.ellipsis,
                                        ),
                                      ],
                                      if (notes.isNotEmpty) ...[
                                        const SizedBox(height: 6),
                                        Text(
                                          'Not: $notes',
                                          style: theme.textTheme.bodySmall,
                                          maxLines: 2,
                                          overflow: TextOverflow.ellipsis,
                                        ),
                                      ],
                                      const SizedBox(height: 10),
                                      _CourierOrderActions(
                                        orderId: id,
                                        status: status,
                                        onSetStatus: _setStatus,
                                        onAcceptAssignment: _acceptAssignment,
                                        onDeclineAssignment: _declineAssignment,
                                      ),
                                      const SizedBox(height: 10),
                                      Row(
                                        children: [
                                          Expanded(
                                            child: FilledButton.tonalIcon(
                                              onPressed: id == 0
                                                  ? null
                                                  : () {
                                                      Navigator.of(context).push(
                                                        MaterialPageRoute(
                                                          builder: (_) => CourierOrderDetailScreen(order: Map<String, dynamic>.from(o)),
                                                        ),
                                                      );
                                                    },
                                              icon: const Icon(Icons.map_outlined),
                                              label: const Text('Detay'),
                                            ),
                                          ),
                                          const SizedBox(width: 10),
                                          Expanded(
                                            child: FilledButton(
                                              onPressed: (!hasRoute || delivery == null)
                                                  ? null
                                                  : () async {
                                                      final lat = (delivery['latitude'] ?? delivery['lat']).toString();
                                                      final lng = (delivery['longitude'] ?? delivery['lng']).toString();
                                                      final uri = Uri.parse('https://www.google.com/maps/dir/?api=1&destination=$lat,$lng');
                                                      await launchUrl(uri, mode: LaunchMode.externalApplication);
                                                    },
                                              style: FilledButton.styleFrom(
                                                backgroundColor: theme.colorScheme.primary.withValues(alpha: 0.90),
                                                foregroundColor: Colors.white,
                                              ),
                                              child: Text(hasRoute ? 'Navigasyon' : 'Rota yok'),
                                            ),
                                          ),
                                        ],
                                      ),
                                    ],
                                  ),
                                ),
                              ),
                            ),
                          );
                          },
                        ),
                      ),
                    ),
                  ],
                ),
        ),
      ),
    );
  }
}

num? _asNum(dynamic v) {
  if (v == null) return null;
  final n = num.tryParse(v.toString());
  if (n == null) return null;
  if (n.abs() < 0.0001) return null;
  return n;
}

_OrderSegment _segmentOf(dynamic raw) {
  final o = raw is Map ? raw : null;
  final st = (o?['status'] ?? '').toString();
  if (st == 'delivered') return _OrderSegment.done;
  if (st == 'cancelled') return _OrderSegment.cancelled;
  return _OrderSegment.active;
}

enum _OrderSegment { active, done, cancelled }

class _SegmentBar extends StatelessWidget {
  const _SegmentBar({
    required this.segment,
    required this.activeCount,
    required this.doneCount,
    required this.cancelledCount,
    required this.onChanged,
  });

  final _OrderSegment segment;
  final int activeCount;
  final int doneCount;
  final int cancelledCount;
  final ValueChanged<_OrderSegment> onChanged;

  Widget _pill(BuildContext context, {required String label, int? count, required bool active}) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    final bg = active
        ? theme.colorScheme.primary.withValues(alpha: isDark ? 0.25 : 0.16)
        : (isDark ? Colors.white.withValues(alpha: 0.06) : theme.colorScheme.surfaceContainerHighest.withValues(alpha: 0.40));
    final fg = active ? theme.colorScheme.primary : theme.textTheme.bodySmall?.color;

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(999),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(label, style: theme.textTheme.labelLarge?.copyWith(fontWeight: FontWeight.w800, color: fg)),
          if (count != null) ...[
            const SizedBox(width: 8),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
              decoration: BoxDecoration(
                color: theme.colorScheme.primary.withValues(alpha: 0.22),
                borderRadius: BorderRadius.circular(999),
              ),
              child: Text(
                count.toString(),
                style: theme.textTheme.labelSmall?.copyWith(fontWeight: FontWeight.w900, color: theme.colorScheme.primary),
              ),
            ),
          ],
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        GestureDetector(
          onTap: () => onChanged(_OrderSegment.active),
          child: _pill(context, label: 'Aktif', count: activeCount, active: segment == _OrderSegment.active),
        ),
        const SizedBox(width: 10),
        GestureDetector(
          onTap: () => onChanged(_OrderSegment.done),
          child: _pill(context, label: 'Tamamlanan', count: doneCount, active: segment == _OrderSegment.done),
        ),
        const SizedBox(width: 10),
        GestureDetector(
          onTap: () => onChanged(_OrderSegment.cancelled),
          child: _pill(context, label: 'İptal', count: cancelledCount, active: segment == _OrderSegment.cancelled),
        ),
      ],
    );
  }
}

class _IncomingOrderDialog extends StatefulWidget {
  const _IncomingOrderDialog({
    required this.order,
    required this.onAccept,
    required this.onDecline,
  });

  final Map<String, dynamic> order;
  final Future<void> Function() onAccept;
  final Future<void> Function() onDecline;

  @override
  State<_IncomingOrderDialog> createState() => _IncomingOrderDialogState();
}

class _IncomingOrderDialogState extends State<_IncomingOrderDialog> {
  static const _ttl = 10;
  int _secLeft = _ttl;
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    _tick();
  }

  Future<void> _tick() async {
    while (mounted && _secLeft > 0) {
      await Future<void>.delayed(const Duration(seconds: 1));
      if (!mounted) return;
      setState(() => _secLeft -= 1);
    }
    if (!mounted) return;
    if (_busy) return;
    _busy = true;
    try {
      await widget.onDecline();
    } catch (_) {
      // ignore
    }
    if (!mounted) return;
    Navigator.of(context).pop();
  }

  Map<String, dynamic>? _asMap(dynamic v) => v is Map ? Map<String, dynamic>.from(v) : null;

  double? _asDouble(dynamic v) => v == null ? null : double.tryParse(v.toString());

  String _fmtKm(double meters) => '${(meters / 1000).toStringAsFixed(1)} km';

  String _fmtEtaMinutes(double km) {
    // rough ETA: 20 km/h average in city
    final minutes = (km / 20.0) * 60.0;
    final m = minutes.isFinite ? minutes.round().clamp(1, 99) : 0;
    return '$m dk.';
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    final order = widget.order;

    final restaurant = _asMap(order['restaurant']);
    final delivery = _asMap(order['delivery_address']);
    final restName = (restaurant?['name'] ?? '—').toString();

    final rLat = _asDouble(restaurant?['latitude'] ?? restaurant?['lat']);
    final rLng = _asDouble(restaurant?['longitude'] ?? restaurant?['lng']);
    final dLat = _asDouble(delivery?['latitude'] ?? delivery?['lat']);
    final dLng = _asDouble(delivery?['longitude'] ?? delivery?['lng']);

    double? distMeters;
    if (rLat != null && rLng != null && dLat != null && dLng != null) {
      distMeters = Geolocator.distanceBetween(rLat, rLng, dLat, dLng);
    }

    final km = distMeters == null ? null : (distMeters / 1000);
    final distLabel = distMeters == null ? '—' : _fmtKm(distMeters);
    final etaLabel = km == null ? '—' : _fmtEtaMinutes(km);

    return Dialog(
      backgroundColor: Colors.transparent,
      insetPadding: const EdgeInsets.symmetric(horizontal: 18, vertical: 24),
      child: GlassCard(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(16, 14, 16, 16),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                decoration: BoxDecoration(
                  color: theme.colorScheme.primary.withValues(alpha: isDark ? 0.18 : 0.14),
                  borderRadius: BorderRadius.circular(999),
                ),
                child: Text(
                  '$_secLeft saniye kaldı',
                  style: theme.textTheme.labelMedium?.copyWith(
                    fontWeight: FontWeight.w900,
                    color: theme.colorScheme.primary,
                  ),
                ),
              ),
              const SizedBox(height: 12),
              Text(
                'YENİ SİPARİŞ',
                style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w900, letterSpacing: 0.3),
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  CircleAvatar(
                    radius: 18,
                    backgroundColor: theme.colorScheme.primary.withValues(alpha: 0.18),
                    child: const Icon(Icons.storefront_outlined),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Text(
                      restName,
                      style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w900),
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 10),
              Row(
                children: [
                  Expanded(child: _MiniMetric(label: 'Mesafe', value: distLabel)),
                  const SizedBox(width: 10),
                  Expanded(child: _MiniMetric(label: 'Tahmini süre', value: etaLabel)),
                ],
              ),
              const SizedBox(height: 12),
              _MiniRoutePreview(
                height: 120,
                fromOk: rLat != null && rLng != null,
                toOk: dLat != null && dLng != null,
              ),
              const SizedBox(height: 14),
              Row(
                children: [
                  Expanded(
                    child: FilledButton.icon(
                      onPressed: _busy
                          ? null
                          : () async {
                              setState(() => _busy = true);
                              try {
                                await widget.onDecline();
                              } finally {
                                if (!mounted) return;
                                Navigator.of(context).pop();
                              }
                            },
                      style: FilledButton.styleFrom(
                        backgroundColor: Colors.red.withValues(alpha: isDark ? 0.75 : 0.90),
                        foregroundColor: Colors.white,
                      ),
                      icon: const Icon(Icons.close),
                      label: const Text('Reddet'),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: FilledButton.icon(
                      onPressed: _busy
                          ? null
                          : () async {
                              setState(() => _busy = true);
                              try {
                                await widget.onAccept();
                              } finally {
                                if (!mounted) return;
                                Navigator.of(context).pop();
                              }
                            },
                      style: FilledButton.styleFrom(
                        backgroundColor: theme.colorScheme.primary.withValues(alpha: 0.95),
                        foregroundColor: Colors.white,
                      ),
                      icon: const Icon(Icons.check),
                      label: const Text('Kabul Et'),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _MiniMetric extends StatelessWidget {
  const _MiniMetric({required this.label, required this.value});
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: theme.colorScheme.surfaceContainerHighest.withValues(
          alpha: theme.brightness == Brightness.dark ? 0.14 : 0.40,
        ),
        borderRadius: BorderRadius.circular(14),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: theme.textTheme.labelMedium),
          const SizedBox(height: 6),
          Text(value, style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w900)),
        ],
      ),
    );
  }
}

class _MiniRoutePreview extends StatelessWidget {
  const _MiniRoutePreview({
    required this.height,
    required this.fromOk,
    required this.toOk,
  });

  final double height;
  final bool fromOk;
  final bool toOk;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    return Container(
      height: height,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(16),
        color: isDark
            ? Colors.white.withValues(alpha: 0.06)
            : theme.colorScheme.surfaceContainerHighest.withValues(alpha: 0.40),
      ),
      child: Stack(
        children: [
          Positioned.fill(
            child: CustomPaint(
              painter: _RoutePreviewPainter(
                isDark: isDark,
                primary: theme.colorScheme.primary,
                showRoute: fromOk && toOk,
              ),
            ),
          ),
          Align(
            alignment: Alignment.center,
            child: (fromOk && toOk)
                ? const SizedBox.shrink()
                : Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(Icons.location_off_outlined, color: theme.colorScheme.primary),
                      const SizedBox(width: 8),
                      Text('Konum yok', style: theme.textTheme.bodySmall),
                    ],
                  ),
          ),
        ],
      ),
    );
  }
}

class _RoutePreviewPainter extends CustomPainter {
  _RoutePreviewPainter({
    required this.isDark,
    required this.primary,
    required this.showRoute,
  });

  final bool isDark;
  final Color primary;
  final bool showRoute;

  @override
  void paint(Canvas canvas, Size size) {
    if (!showRoute) return;

    final bgPaint = Paint()
      ..shader = LinearGradient(
        begin: Alignment.topLeft,
        end: Alignment.bottomRight,
        colors: [
          primary.withValues(alpha: isDark ? 0.14 : 0.10),
          primary.withValues(alpha: isDark ? 0.05 : 0.03),
        ],
      ).createShader(Offset.zero & size);
    canvas.drawRRect(
      RRect.fromRectAndRadius(Offset.zero & size, const Radius.circular(16)),
      bgPaint,
    );

    final from = Offset(size.width * 0.18, size.height * 0.55);
    final to = Offset(size.width * 0.82, size.height * 0.40);

    final linePaint = Paint()
      ..color = primary.withValues(alpha: isDark ? 0.85 : 0.70)
      ..style = PaintingStyle.stroke
      ..strokeWidth = 3.5
      ..strokeCap = StrokeCap.round;

    final path = Path()
      ..moveTo(from.dx, from.dy)
      ..quadraticBezierTo(size.width * 0.50, size.height * 0.25, to.dx, to.dy);

    // dashed effect
    final dash = 8.0;
    final gap = 6.0;
    for (final metric in path.computeMetrics()) {
      var dist = 0.0;
      while (dist < metric.length) {
        final len = (dist + dash < metric.length) ? dash : (metric.length - dist);
        final extract = metric.extractPath(dist, dist + len);
        canvas.drawPath(extract, linePaint);
        dist += dash + gap;
      }
    }

    final dotStroke = Paint()
      ..color = isDark ? const Color(0xff0b1020) : Colors.white
      ..style = PaintingStyle.stroke
      ..strokeWidth = 3;
    final dotFrom = Paint()..color = const Color(0xfff59e0b); // restaurant
    final dotTo = Paint()..color = primary; // customer

    canvas.drawCircle(from, 8, dotFrom);
    canvas.drawCircle(from, 8, dotStroke);
    canvas.drawCircle(to, 8, dotTo);
    canvas.drawCircle(to, 8, dotStroke);
  }

  @override
  bool shouldRepaint(covariant _RoutePreviewPainter oldDelegate) {
    return oldDelegate.isDark != isDark || oldDelegate.primary != primary || oldDelegate.showRoute != showRoute;
  }
}

class _CourierOrderActions extends StatelessWidget {
  const _CourierOrderActions({
    required this.orderId,
    required this.status,
    required this.onSetStatus,
    required this.onAcceptAssignment,
    required this.onDeclineAssignment,
  });

  final int orderId;
  final String status;
  final Future<void> Function(int orderId, String status) onSetStatus;
  final Future<void> Function(int orderId) onAcceptAssignment;
  final Future<void> Function(int orderId, String reason) onDeclineAssignment;

  Future<bool> _confirm(BuildContext context, String message) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Onay'),
        content: Text(message),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Vazgeç')),
          FilledButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Tamam')),
        ],
      ),
    );
    return ok == true;
  }

  @override
  Widget build(BuildContext context) {
    if (orderId == 0) return const SizedBox.shrink();

    // Firma atandı → önce kabul / red / devir
    if (status == 'courier_assigned') {
      return Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          FilledButton.icon(
            onPressed: () => onAcceptAssignment(orderId),
            icon: const Icon(Icons.check_circle_outline),
            label: const Text('Kabul et'),
          ),
          const SizedBox(height: 8),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              OutlinedButton(
                onPressed: () async {
                  if (!await _confirm(context, 'Müsait değilsiniz; sipariş havuza döner ve şirket bilgilendirilir (çan/ses).')) {
                    return;
                  }
                  await onDeclineAssignment(orderId, 'unavailable');
                },
                child: const Text('Reddet'),
              ),
              OutlinedButton(
                onPressed: () async {
                  if (!await _confirm(
                    context,
                    'Kurye şirketine devir isteği gidecek; sipariş yeniden atama kuyruğuna döner (çan/ses).',
                  )) {
                    return;
                  }
                  await onDeclineAssignment(orderId, 'transfer');
                },
                child: const Text('Devir et'),
              ),
            ],
          ),
        ],
      );
    }

    // Kabul sonrası: Aldım → Alındı
    if (status == 'courier_accepted') {
      return Row(
        children: [
          Expanded(
            child: FilledButton.tonalIcon(
              onPressed: () => onSetStatus(orderId, 'picked_up'),
              icon: const Icon(Icons.shopping_bag),
              label: const Text('Aldım'),
            ),
          ),
        ],
      );
    }

    if (status == 'picked_up') {
      return FilledButton.tonalIcon(
        onPressed: () => onSetStatus(orderId, 'on_the_way'),
        icon: const Icon(Icons.directions_bike_outlined),
        label: const Text('Yolda'),
      );
    }

    if (status == 'on_the_way') {
      return Row(
        children: [
          Expanded(
            child: FilledButton.tonalIcon(
              onPressed: () => onSetStatus(orderId, 'delivered'),
              icon: const Icon(Icons.check_circle_outline),
              label: const Text('Teslim'),
            ),
          ),
        ],
      );
    }

    return Text('Bu aşamada uygulama işlemi yok.', style: Theme.of(context).textTheme.bodySmall);
  }
}

class _StatusPill extends StatelessWidget {
  const _StatusPill({required this.text});
  final String text;

  @override
  Widget build(BuildContext context) {
    final t = text.toLowerCase();
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    final Color bg = switch (t) {
      _ when t.contains('aldı') || t.contains('alındı') => Colors.amber.withValues(alpha: isDark ? 0.18 : 0.22),
      _ when t.contains('yolda') => Colors.blue.withValues(alpha: isDark ? 0.18 : 0.22),
      _ when t.contains('teslim') => Colors.green.withValues(alpha: isDark ? 0.16 : 0.22),
      _ when t.contains('kabul') => Colors.teal.withValues(alpha: isDark ? 0.16 : 0.22),
      _ when t.contains('kurye atandı') => Colors.orange.withValues(alpha: isDark ? 0.18 : 0.22),
      _ => Colors.blueGrey.withValues(alpha: isDark ? 0.18 : 0.18),
    };
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        text,
        style: theme.textTheme.labelSmall?.copyWith(fontWeight: FontWeight.w900, color: theme.colorScheme.primary),
      ),
    );
  }
}

class _CourierOrdersLifecycleObserver with WidgetsBindingObserver {
  _CourierOrdersLifecycleObserver({required this.onResumed});
  final VoidCallback onResumed;

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      onResumed();
    }
  }
}
