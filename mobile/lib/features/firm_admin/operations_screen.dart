import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:kurye_mobile/features/firm_admin/operations_api.dart';
import 'package:kurye_mobile/core/app_scope.dart';

class OperationsScreen extends StatefulWidget {
  const OperationsScreen({super.key, required this.api});

  final OperationsApi api;

  @override
  State<OperationsScreen> createState() => _OperationsScreenState();
}

class _OperationsScreenState extends State<OperationsScreen> {
  bool _loading = true;
  Map<String, dynamic>? _snap;
  String? _error;
  final Map<int, int?> _selectedCourierByOrder = {};
  final Map<int, Map<int, int>> _etaSecondsByOrder = {}; // orderId -> courierId -> seconds
  final Set<int> _etaLoadingOrders = {};
  final Map<String, int> _etaCache = {}; // key: fromLat,fromLng->toLat,toLng
  bool _autoAssignBestAfterEta = false;

  bool _polling = false;
  bool _pollingBusy = false;
  String _lastSignature = '';
  final Set<int> _seenAwaitingCourierIds = <int>{};

  @override
  void initState() {
    super.initState();
    _load();
    _startPolling();
  }

  @override
  void dispose() {
    _polling = false;
    super.dispose();
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
          await _load(silent: true);
        } catch (_) {
          // keep polling alive
        } finally {
          _pollingBusy = false;
        }
      }
    });
  }

  Future<void> _load({bool silent = false}) async {
    if (!silent) {
      setState(() {
        _loading = true;
        _error = null;
      });
    }
    try {
      final d = await widget.api.snapshot();
      if (!mounted) return;
      setState(() {
        _snap = d;
        final firm = d['firm'];
        if (firm is Map && firm['auto_assign_best_after_eta'] is bool) {
          _autoAssignBestAfterEta = firm['auto_assign_best_after_eta'] as bool;
        }
        if (!silent) _loading = false;
      });

      _handleFirmAlerts(d, silent: silent);
    } catch (_) {
      if (!mounted) return;
      if (!silent) {
        setState(() {
          _error = 'Operasyon özeti alınamadı.';
          _loading = false;
        });
      }
    }
  }

  void _handleFirmAlerts(Map<String, dynamic> snap, {required bool silent}) async {
    final orders = (snap['orders'] as List?) ?? const [];

    // Signature: id + status + courier_id; if unchanged, skip noisy rebuilds.
    final sigParts = <String>[];
    final awaiting = <int>[];

    for (final raw in orders) {
      final o = raw is Map ? raw : null;
      if (o == null) continue;
      final id = int.tryParse((o['id'] ?? '').toString()) ?? 0;
      if (id == 0) continue;
      final status = (o['status'] ?? '').toString();
      final courierId = (o['courier_id'] ?? '').toString();
      sigParts.add('$id:$status:$courierId');

      final bool requested = (o['restaurant_courier_requested'] ?? false) == true;
      final bool hasCourier = o['courier_id'] != null;
      final bool isReady = status == 'ready' || (o['status_label'] ?? '').toString().toLowerCase().contains('hazır');
      if (requested && !hasCourier && isReady) {
        awaiting.add(id);
      }
    }

    final signature = sigParts.join('|');
    final changed = signature != _lastSignature;
    _lastSignature = signature;

    // New "awaiting courier" orders trigger alert.
    final newlyAwaiting = awaiting.where((id) => !_seenAwaitingCourierIds.contains(id)).toList(growable: false);
    for (final id in awaiting) {
      _seenAwaitingCourierIds.add(id);
    }

    if (newlyAwaiting.isNotEmpty) {
      try {
        await SystemSound.play(SystemSoundType.alert);
      } catch (_) {}
      try {
        if (mounted) {
          AppScope.of(context).notifications.firmIncomingOrder(orderId: newlyAwaiting.first, title: 'Kurye bekleyen sipariş');
        }
      } catch (_) {}
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Yeni sipariş #${newlyAwaiting.first} kurye bekliyor.')),
      );
    } else if (changed && !silent) {
      // no-op; UI already updated by setState above
    }
  }

  ({double lat, double lng})? _latLngFrom(dynamic raw) {
    if (raw is! Map) return null;
    final lat = double.tryParse((raw['lat'] ?? raw['latitude'] ?? '').toString());
    final lng = double.tryParse((raw['lng'] ?? raw['longitude'] ?? '').toString());
    if (lat == null || lng == null) return null;
    return (lat: lat, lng: lng);
  }

  Future<void> _calcEtaForOrder({
    required int orderId,
    required List<int> courierIds,
    required Map<String, dynamic> order,
    required Map<int, Map<String, dynamic>> courierById,
  }) async {
    if (_etaLoadingOrders.contains(orderId)) return;
    setState(() => _etaLoadingOrders.add(orderId));

    try {
      final scope = AppScope.of(context);

      // Destination: restaurant first; fallback to delivery.
      final restaurant = order['restaurant'];
      final delivery = order['delivery'];
      final dest = _latLngFrom(restaurant) ?? _latLngFrom(delivery);
      if (dest == null) return;

      final limited = courierIds.take(3).toList();
      final results = <int, int>{};

      for (final cid in limited) {
        final c = courierById[cid];
        if (c == null) continue;
        final from = _latLngFrom(c);
        if (from == null) continue;

        final key =
            '${from.lat.toStringAsFixed(5)},${from.lng.toStringAsFixed(5)}->${dest.lat.toStringAsFixed(5)},${dest.lng.toStringAsFixed(5)}';
        final cached = _etaCache[key];
        if (cached != null) {
          results[cid] = cached;
          continue;
        }

        final r = await scope.api.dio.post('/api/v1/routing/route', data: {
          'from_lat': from.lat,
          'from_lng': from.lng,
          'to_lat': dest.lat,
          'to_lng': dest.lng,
        });
        final data = r.data;
        if (data is Map && data['ok'] == true) {
          final sec = int.tryParse((data['duration_seconds'] ?? '').toString());
          if (sec != null) {
            results[cid] = sec;
            _etaCache[key] = sec;
          }
        }
      }

      if (!mounted) return;
      final bestId = results.entries.isEmpty
          ? null
          : (results.entries.toList()..sort((a, b) => a.value.compareTo(b.value))).first.key;
      setState(() {
        _etaSecondsByOrder[orderId] = results;
        if (bestId != null) {
          _selectedCourierByOrder[orderId] = bestId;
        }
      });

      if (_autoAssignBestAfterEta && bestId != null && mounted) {
        await widget.api.assignCourier(orderId: orderId, courierId: bestId);
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('En hızlı kurye otomatik atandı.')));
        await _load();
      }
    } catch (_) {
      // ignore: OSRM may be disabled/unavailable
    } finally {
      if (mounted) {
        setState(() => _etaLoadingOrders.remove(orderId));
      }
    }
  }

  String _fmtEta(int seconds) {
    final m = (seconds / 60).round();
    if (m < 60) return '$m dk';
    final h = (m / 60).floor();
    final mm = m % 60;
    return '${h}s ${mm}dk';
  }

  int? _bestCourierId(int orderId, List<int> courierIds) {
    final etaMap = _etaSecondsByOrder[orderId];
    if (etaMap == null || etaMap.isEmpty) return null;

    int? bestId;
    int? bestSec;
    for (final id in courierIds) {
      final sec = etaMap[id];
      if (sec == null) continue;
      if (bestSec == null || sec < bestSec) {
        bestSec = sec;
        bestId = id;
      }
    }
    return bestId;
  }

  String _formatTurkishDate(DateTime dt) {
    const months = <String>[
      'Ocak',
      'Şubat',
      'Mart',
      'Nisan',
      'Mayıs',
      'Haziran',
      'Temmuz',
      'Ağustos',
      'Eylül',
      'Ekim',
      'Kasım',
      'Aralık',
    ];
    const weekdays = <String>[
      'Pazartesi',
      'Salı',
      'Çarşamba',
      'Perşembe',
      'Cuma',
      'Cumartesi',
      'Pazar',
    ];
    final wd = weekdays[(dt.weekday - 1).clamp(0, 6)];
    final m = months[(dt.month - 1).clamp(0, 11)];
    return '${dt.day} $m ${dt.year}, $wd';
  }

  ({int total, int active, int waiting}) _deriveOrderCounts(List orders) {
    int active = 0;
    int waiting = 0;
    for (final o in orders) {
      final m = o as Map? ?? const {};
      final st = (m['status_label'] ?? m['status'] ?? '').toString().toLowerCase();
      if (st.contains('teslim') || st.contains('iptal') || st.contains('cancel')) {
        continue;
      }
      if (st.contains('yolda') || st.contains('devam') || st.contains('aktif') || st.contains('alındı')) {
        active++;
        continue;
      }
      if (st.contains('bekle') || st.contains('hazır') || st.contains('pending') || st.contains('hazırlan')) {
        waiting++;
        continue;
      }
      // fallback: count as active-ish
      active++;
    }
    return (total: orders.length, active: active, waiting: waiting);
  }

  ({double totalRevenue, double netProfit}) _deriveFinance(Map<String, dynamic>? snap) {
    final fin = snap?['finance'] ?? snap?['financial'] ?? snap?['summary'];
    if (fin is! Map) return (totalRevenue: 0, netProfit: 0);

    double pick(List<String> keys) {
      for (final k in keys) {
        final v = fin[k];
        if (v == null) continue;
        final n = double.tryParse(v.toString().replaceAll(',', '.'));
        if (n != null) return n;
      }
      return 0;
    }

    return (
      totalRevenue: pick(['total_revenue', 'revenue', 'total_income', 'income_total', 'ciro', 'toplam_gelir']),
      netProfit: pick(['net_profit', 'profit', 'net_kar', 'netProfit', 'net']),
    );
  }

  String _fmtMoneyTl(num v) {
    final s = v.toStringAsFixed(2);
    // lightweight TR-like formatting: 3420.00 -> 3,420.00
    final parts = s.split('.');
    final whole = parts[0];
    final frac = parts.length > 1 ? parts[1] : '00';
    final buf = StringBuffer();
    for (var i = 0; i < whole.length; i++) {
      final idxFromEnd = whole.length - i;
      buf.write(whole[i]);
      if (idxFromEnd > 1 && idxFromEnd % 3 == 1) buf.write(',');
    }
    return '${buf.toString()}.$frac TL';
  }

  @override
  Widget build(BuildContext context) {
    final orders = (_snap?['orders'] as List?) ?? const [];
    final couriers = (_snap?['couriers'] as List?) ?? const [];
    final courierById = <int, Map<String, dynamic>>{};
    for (final c in couriers) {
      final m = c as Map? ?? const {};
      final id = int.tryParse(m['id'].toString());
      if (id != null) {
        courierById[id] = Map<String, dynamic>.from(m);
      }
    }
    final now = DateTime.now();
    final counts = _deriveOrderCounts(orders);
    final finance = _deriveFinance(_snap);
    final me = AppScope.of(context).auth.me;
    final meName = me?.name;
    final greetingName = (meName != null && meName.trim().isNotEmpty) ? meName.trim() : 'Yönetici';

    return Scaffold(
      appBar: AppBar(
        title: const Text('Operations'),
        actions: [
          IconButton(
            tooltip: 'Bildirimler',
            onPressed: () {
              ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Bildirimler yakında.')));
            },
            icon: const Icon(Icons.notifications_none),
          ),
          IconButton(onPressed: () => _load(silent: false), icon: const Icon(Icons.refresh)),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(child: Text(_error!))
              : RefreshIndicator(
                  onRefresh: _load,
                  child: ListView(
                    padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
                    children: [
                      _DashboardHeader(
                        title: 'Merhaba, $greetingName',
                        subtitle: _formatTurkishDate(now),
                      ),
                      const SizedBox(height: 14),
                      _StatsGrid(
                        totalOrders: counts.total,
                        activeOrders: counts.active,
                        waitingOrders: counts.waiting,
                        couriersCount: couriers.length,
                      ),
                      const SizedBox(height: 14),
                      _FinanceCard(
                        totalRevenueTl: finance.totalRevenue,
                        netProfitTl: finance.netProfit,
                        fmtMoney: _fmtMoneyTl,
                      ),
                      const SizedBox(height: 14),
                      Row(
                        children: [
                          Text(
                            'Son Siparişler',
                            style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w800),
                          ),
                          const Spacer(),
                          TextButton(
                            onPressed: () async {
                              await Navigator.of(context).push(
                                MaterialPageRoute(
                                  builder: (_) => _OperationsOrdersScreen(
                                    api: widget.api,
                                    initialSnap: _snap ?? const {},
                                    autoAssignBestAfterEta: _autoAssignBestAfterEta,
                                    onChangedAutoAssignBestAfterEta: (v) => setState(() => _autoAssignBestAfterEta = v),
                                    selectedCourierByOrder: _selectedCourierByOrder,
                                    etaSecondsByOrder: _etaSecondsByOrder,
                                    etaLoadingOrders: _etaLoadingOrders,
                                    fmtEta: _fmtEta,
                                    calcEtaForOrder: _calcEtaForOrder,
                                    bestCourierId: _bestCourierId,
                                  ),
                                ),
                              );
                            },
                            child: const Text('Tümünü Gör'),
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      if (orders.isEmpty)
                        Padding(
                          padding: const EdgeInsets.only(top: 12),
                          child: Text(
                            'Gösterilecek sipariş yok.',
                            style: Theme.of(context)
                                .textTheme
                                .bodyMedium
                                ?.copyWith(color: Theme.of(context).colorScheme.onSurfaceVariant),
                            textAlign: TextAlign.center,
                          ),
                        )
                      else
                        ...orders.take(3).map((o) {
                          final m = o as Map? ?? const {};
                          final id = int.tryParse(m['id'].toString()) ?? 0;
                          final rest = (m['restaurant'] is Map) ? (m['restaurant']['name'] ?? '—') : '—';
                          final st = (m['status_label'] ?? m['status'] ?? '').toString();
                          final time = (m['created_at'] ?? m['createdAt'] ?? '').toString();
                          return Padding(
                            padding: const EdgeInsets.only(bottom: 10),
                            child: _RecentOrderTile(
                              orderId: id,
                              restaurantName: rest.toString(),
                              timeLabel: time.isEmpty ? '—' : time,
                              statusLabel: st,
                              onTap: () async {
                                await Navigator.of(context).push(
                                  MaterialPageRoute(
                                    builder: (_) => _OperationsOrdersScreen(
                                      api: widget.api,
                                      initialSnap: _snap ?? const {},
                                      autoAssignBestAfterEta: _autoAssignBestAfterEta,
                                      onChangedAutoAssignBestAfterEta: (v) => setState(() => _autoAssignBestAfterEta = v),
                                      selectedCourierByOrder: _selectedCourierByOrder,
                                      etaSecondsByOrder: _etaSecondsByOrder,
                                      etaLoadingOrders: _etaLoadingOrders,
                                      fmtEta: _fmtEta,
                                      calcEtaForOrder: _calcEtaForOrder,
                                      bestCourierId: _bestCourierId,
                                      initialOrderId: id,
                                    ),
                                  ),
                                );
                              },
                            ),
                          );
                        }),
                    ],
                  ),
                ),
      floatingActionButton: FloatingActionButton(
        onPressed: () {
          ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Hızlı işlem yakında.')));
        },
        child: const Icon(Icons.add),
      ),
    );
  }
}

class _DashboardHeader extends StatelessWidget {
  const _DashboardHeader({required this.title, required this.subtitle});
  final String title;
  final String subtitle;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          title,
          style: theme.textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.w800),
        ),
        const SizedBox(height: 4),
        Text(
          subtitle,
          style: theme.textTheme.bodySmall?.copyWith(color: theme.colorScheme.onSurfaceVariant),
        ),
      ],
    );
  }
}

class _StatsGrid extends StatelessWidget {
  const _StatsGrid({
    required this.totalOrders,
    required this.activeOrders,
    required this.waitingOrders,
    required this.couriersCount,
  });

  final int totalOrders;
  final int activeOrders;
  final int waitingOrders;
  final int couriersCount;

  @override
  Widget build(BuildContext context) {
    final gap = 12.0;
    return LayoutBuilder(
      builder: (context, c) {
        final cardW = (c.maxWidth - gap) / 2;
        return Wrap(
          spacing: gap,
          runSpacing: gap,
          children: [
            SizedBox(
              width: cardW,
              child: _StatCard(title: 'TOPLAM SİPARİŞ', value: '$totalOrders', accent: const Color(0xFF1E66F5)),
            ),
            SizedBox(
              width: cardW,
              child: _StatCard(title: 'AKTİF', value: '$activeOrders', accent: const Color(0xFF2E7D32)),
            ),
            SizedBox(
              width: cardW,
              child: _StatCard(title: 'BEKLEYEN', value: '$waitingOrders', accent: const Color(0xFF263238)),
            ),
            SizedBox(
              width: cardW,
              child: _StatCard(title: 'KURYELER', value: '$couriersCount', accent: const Color(0xFF263238)),
            ),
          ],
        );
      },
    );
  }
}

class _StatCard extends StatelessWidget {
  const _StatCard({required this.title, required this.value, required this.accent});
  final String title;
  final String value;
  final Color accent;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Card(
      elevation: 0,
      clipBehavior: Clip.antiAlias,
      child: Padding(
        padding: const EdgeInsets.fromLTRB(14, 14, 14, 14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              title,
              style: theme.textTheme.labelSmall?.copyWith(
                color: theme.colorScheme.onSurfaceVariant,
                fontWeight: FontWeight.w800,
                letterSpacing: 0.6,
              ),
            ),
            const SizedBox(height: 10),
            Text(
              value,
              style: theme.textTheme.displaySmall?.copyWith(
                height: 1,
                fontWeight: FontWeight.w900,
                color: accent,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _FinanceCard extends StatelessWidget {
  const _FinanceCard({
    required this.totalRevenueTl,
    required this.netProfitTl,
    required this.fmtMoney,
  });

  final double totalRevenueTl;
  final double netProfitTl;
  final String Function(num v) fmtMoney;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Card(
      elevation: 0,
      clipBehavior: Clip.antiAlias,
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Finansal Özet',
                        style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w800),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        'Bugünkü performans',
                        style: theme.textTheme.bodySmall?.copyWith(color: theme.colorScheme.onSurfaceVariant),
                      ),
                    ],
                  ),
                ),
                IconButton(
                  tooltip: 'Detay',
                  onPressed: () {
                    ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Finans detayları yakında.')));
                  },
                  icon: const Icon(Icons.camera_alt_outlined),
                ),
              ],
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: _FinanceMetric(
                    label: 'TOPLAM GELİR',
                    value: fmtMoney(totalRevenueTl),
                    valueStyle: theme.textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w900),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: _FinanceMetric(
                    label: 'NET KAR',
                    value: fmtMoney(netProfitTl),
                    valueStyle: theme.textTheme.titleLarge?.copyWith(
                      fontWeight: FontWeight.w900,
                      color: const Color(0xFF2E7D32),
                    ),
                    trailing: const Icon(Icons.trending_up, size: 18, color: Color(0xFF2E7D32)),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 14),
            const _MiniBarChart(),
          ],
        ),
      ),
    );
  }
}

class _FinanceMetric extends StatelessWidget {
  const _FinanceMetric({
    required this.label,
    required this.value,
    required this.valueStyle,
    this.trailing,
  });

  final String label;
  final String value;
  final TextStyle? valueStyle;
  final Widget? trailing;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: theme.textTheme.labelSmall?.copyWith(
            color: theme.colorScheme.onSurfaceVariant,
            fontWeight: FontWeight.w800,
            letterSpacing: 0.6,
          ),
        ),
        const SizedBox(height: 6),
        Row(
          children: [
            Expanded(
              child: Text(
                value,
                style: valueStyle,
                overflow: TextOverflow.ellipsis,
              ),
            ),
            if (trailing != null) ...[
              const SizedBox(width: 6),
              trailing!,
            ],
          ],
        ),
      ],
    );
  }
}

class _MiniBarChart extends StatelessWidget {
  const _MiniBarChart();

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final bars = <double>[0.18, 0.22, 0.16, 0.26, 0.30, 0.44, 0.58];
    return SizedBox(
      height: 70,
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.end,
        children: [
          for (var i = 0; i < bars.length; i++) ...[
            Expanded(
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 3),
                child: AnimatedContainer(
                  duration: const Duration(milliseconds: 250),
                  height: 70 * bars[i],
                  decoration: BoxDecoration(
                    color: i == bars.length - 1
                        ? theme.colorScheme.primary.withValues(alpha: 0.65)
                        : theme.colorScheme.onSurface.withValues(alpha: 0.10),
                    borderRadius: BorderRadius.circular(10),
                  ),
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }
}

class _RecentOrderTile extends StatelessWidget {
  const _RecentOrderTile({
    required this.orderId,
    required this.restaurantName,
    required this.timeLabel,
    required this.statusLabel,
    this.onTap,
  });

  final int orderId;
  final String restaurantName;
  final String timeLabel;
  final String statusLabel;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Card(
      elevation: 0,
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(12, 10, 12, 10),
          child: Row(
            children: [
              Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(
                  color: theme.colorScheme.onSurface.withValues(alpha: 0.06),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(Icons.local_shipping_outlined, color: theme.colorScheme.onSurfaceVariant),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      '#ORD-$orderId',
                      style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w900),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      '$restaurantName • $timeLabel',
                      style: theme.textTheme.bodySmall?.copyWith(color: theme.colorScheme.onSurfaceVariant),
                      overflow: TextOverflow.ellipsis,
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 10),
              _StatusChip(text: statusLabel),
              if (onTap != null) ...[
                const SizedBox(width: 8),
                Icon(Icons.chevron_right, color: theme.colorScheme.onSurfaceVariant),
              ],
            ],
          ),
        ),
      ),
    );
  }
}

class _OperationsOrdersScreen extends StatefulWidget {
  const _OperationsOrdersScreen({
    required this.api,
    this.initialSnap,
    required this.autoAssignBestAfterEta,
    required this.onChangedAutoAssignBestAfterEta,
    required this.selectedCourierByOrder,
    required this.etaSecondsByOrder,
    required this.etaLoadingOrders,
    required this.fmtEta,
    required this.calcEtaForOrder,
    required this.bestCourierId,
    this.initialOrderId,
  });

  final OperationsApi api;
  final Map<String, dynamic>? initialSnap;
  final bool autoAssignBestAfterEta;
  final ValueChanged<bool> onChangedAutoAssignBestAfterEta;
  final Map<int, int?> selectedCourierByOrder;
  final Map<int, Map<int, int>> etaSecondsByOrder;
  final Set<int> etaLoadingOrders;
  final String Function(int seconds) fmtEta;
  final Future<void> Function({
    required int orderId,
    required List<int> courierIds,
    required Map<String, dynamic> order,
    required Map<int, Map<String, dynamic>> courierById,
  }) calcEtaForOrder;
  final int? Function(int orderId, List<int> courierIds) bestCourierId;
  final int? initialOrderId;

  @override
  State<_OperationsOrdersScreen> createState() => _OperationsOrdersScreenState();
}

class _OperationsOrdersScreenState extends State<_OperationsOrdersScreen> {
  bool _loading = true;
  Map<String, dynamic> _snap = const {};
  String? _error;

  void _optimisticAssign({required int orderId, required int courierId}) {
    final orders = (_snap['orders'] as List?)?.toList() ?? <dynamic>[];
    bool changed = false;
    for (var i = 0; i < orders.length; i++) {
      final raw = orders[i];
      if (raw is! Map) continue;
      final m = Map<String, dynamic>.from(raw);
      final id = int.tryParse((m['id'] ?? '').toString()) ?? 0;
      if (id != orderId) continue;
      m['courier_id'] = courierId;
      // UI: buton metni + chip için makul varsayılanlar
      m['status'] = m['status'] ?? 'courier_assigned';
      m['status_label'] = m['status_label'] ?? 'Kurye atandı';
      orders[i] = m;
      changed = true;
      break;
    }
    if (!changed) return;
    setState(() {
      _snap = Map<String, dynamic>.from(_snap)..['orders'] = orders;
    });
  }

  @override
  void initState() {
    super.initState();
    _snap = widget.initialSnap ?? const {};
    _loading = (widget.initialSnap == null);
    _refresh();
  }

  Future<void> _refresh() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final d = await widget.api.snapshot();
      if (!mounted) return;
      setState(() {
        _snap = d;
        _loading = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _error = 'Siparişler alınamadı.';
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final ordersRaw = (_snap['orders'] as List?) ?? const [];
    final couriersRaw = (_snap['couriers'] as List?) ?? const [];

    final courierById = <int, Map<String, dynamic>>{};
    for (final c in couriersRaw) {
      final m = c as Map? ?? const {};
      final id = int.tryParse(m['id'].toString());
      if (id != null) courierById[id] = Map<String, dynamic>.from(m);
    }
    final courierItems = couriersRaw
        .map((c) => c as Map? ?? const {})
        .map((c) => DropdownMenuItem<int>(
              value: int.tryParse(c['id'].toString()),
              child: Text('#${c['id']} ${c['name'] ?? ''}'.trim()),
            ))
        .where((x) => x.value != null)
        .toList();

    final orders = (widget.initialOrderId == null)
        ? ordersRaw
        : [
            ...ordersRaw.where((o) {
              final m = o as Map? ?? const {};
              final id = int.tryParse(m['id'].toString()) ?? 0;
              return id == widget.initialOrderId;
            }),
            ...ordersRaw.where((o) {
              final m = o as Map? ?? const {};
              final id = int.tryParse(m['id'].toString()) ?? 0;
              return id != widget.initialOrderId;
            }),
          ];

    return Scaffold(
      appBar: AppBar(
        title: const Text('Siparişler'),
        actions: [IconButton(onPressed: _refresh, icon: const Icon(Icons.refresh))],
      ),
      body: RefreshIndicator(
        onRefresh: _refresh,
        child: _loading
            ? const Center(child: CircularProgressIndicator())
            : (_error != null)
                ? Center(child: Text(_error!))
                : ListView(
                    padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
                    children: [
                      _SettingsCard(
                        value: widget.autoAssignBestAfterEta,
              onChanged: (v) async {
                final prev = widget.autoAssignBestAfterEta;
                widget.onChangedAutoAssignBestAfterEta(v);
                try {
                  final saved = await widget.api.setAutoAssignBestAfterEta(v);
                  widget.onChangedAutoAssignBestAfterEta(saved);
                  if (!context.mounted) return;
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(content: Text(saved ? 'Otomatik atama açıldı.' : 'Otomatik atama kapandı.')),
                  );
                } catch (_) {
                  widget.onChangedAutoAssignBestAfterEta(prev);
                  if (!context.mounted) return;
                  ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Ayar kaydedilemedi.')));
                }
              },
            ),
            const SizedBox(height: 16),
            Row(
              children: [
                Text(
                  'Siparişler',
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700),
                ),
                const Spacer(),
                Text(
                  'İlk 30',
                  style: Theme.of(context)
                      .textTheme
                      .labelMedium
                      ?.copyWith(color: Theme.of(context).colorScheme.onSurfaceVariant),
                ),
              ],
            ),
            const SizedBox(height: 8),
            if (orders.isEmpty)
              Padding(
                padding: const EdgeInsets.only(top: 24),
                child: Text(
                  'Gösterilecek sipariş yok.',
                  style: Theme.of(context)
                      .textTheme
                      .bodyMedium
                      ?.copyWith(color: Theme.of(context).colorScheme.onSurfaceVariant),
                  textAlign: TextAlign.center,
                ),
              ),
            ...orders.take(30).map((o) {
              final m = o as Map? ?? const {};
              final id = int.tryParse(m['id'].toString()) ?? 0;
              final st = (m['status_label'] ?? m['status'] ?? '').toString();
              final rest = (m['restaurant'] is Map) ? (m['restaurant']['name'] ?? '—') : '—';
              final suggested = (m['suggested_nearby_courier_ids'] as List?)
                      ?.map((e) => int.tryParse(e.toString()))
                      .whereType<int>()
                      .toList() ??
                  const <int>[];
              final currentCourierId = m['courier_id'] == null ? null : int.tryParse(m['courier_id'].toString());
              widget.selectedCourierByOrder.putIfAbsent(id, () => currentCourierId);
              final existingSelected = widget.selectedCourierByOrder[id];
              if (existingSelected == null && currentCourierId != null) {
                widget.selectedCourierByOrder[id] = currentCourierId;
              }

              final etaMap = widget.etaSecondsByOrder[id] ?? const <int, int>{};
              final etaLoading = widget.etaLoadingOrders.contains(id);
              final bestId = widget.bestCourierId(id, suggested);
              final sortedSuggested = suggested.toList()
                ..sort((a, b) {
                  final ea = etaMap[a];
                  final eb = etaMap[b];
                  if (ea == null && eb == null) return 0;
                  if (ea == null) return 1;
                  if (eb == null) return -1;
                  return ea.compareTo(eb);
                });

              return Padding(
                padding: const EdgeInsets.only(bottom: 10),
                child: _OrderCard(
                  orderId: id,
                  restaurantName: rest.toString(),
                  statusLabel: st,
                  currentCourierId: currentCourierId,
                  suggestedCourierIds: sortedSuggested,
                  courierById: courierById,
                  etaSecondsByCourierId: etaMap,
                  etaLoading: etaLoading,
                  selectedCourierId: widget.selectedCourierByOrder[id],
                  courierItems: courierItems,
                  fmtEta: widget.fmtEta,
                  onSelectCourier: (cid) => setState(() => widget.selectedCourierByOrder[id] = cid),
                  onCalcEta: etaLoading
                      ? null
                      : () => widget.calcEtaForOrder(
                            orderId: id,
                            courierIds: sortedSuggested,
                            order: Map<String, dynamic>.from(m),
                            courierById: courierById,
                          ),
                  onSelectBest: bestId == null ? null : () => setState(() => widget.selectedCourierByOrder[id] = bestId),
                  onAssignBest: bestId == null
                      ? null
                      : () async {
                          setState(() => widget.selectedCourierByOrder[id] = bestId);
                          _optimisticAssign(orderId: id, courierId: bestId);
                          await widget.api.assignCourier(orderId: id, courierId: bestId);
                          if (!context.mounted) return;
                          ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('En hızlı kurye atandı.')));
                          await _refresh();
                        },
                  onAssignSelected: widget.selectedCourierByOrder[id] == null
                      ? null
                      : () async {
                          final cid = widget.selectedCourierByOrder[id];
                          if (cid == null) return;
                          _optimisticAssign(orderId: id, courierId: cid);
                          await widget.api.assignCourier(orderId: id, courierId: cid);
                          if (!context.mounted) return;
                          ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Kurye güncellendi.')));
                          await _refresh();
                        },
                ),
              );
            }),
                    ],
                  ),
      ),
    );
  }
}

class _SettingsCard extends StatelessWidget {
  const _SettingsCard({required this.value, required this.onChanged});

  final bool value;
  final ValueChanged<bool> onChanged;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Card(
      elevation: 0,
      color: theme.colorScheme.surfaceContainerHighest.withValues(alpha: 0.55),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'ETA sonrası en hızlıyı otomatik ata',
                    style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w700),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'Açıksa “ETA hesapla” biter bitmez en hızlı kurye atanır.',
                    style: theme.textTheme.bodySmall?.copyWith(color: theme.colorScheme.onSurfaceVariant),
                  ),
                ],
              ),
            ),
            const SizedBox(width: 12),
            Switch.adaptive(value: value, onChanged: onChanged),
          ],
        ),
      ),
    );
  }
}

class _OrderCard extends StatelessWidget {
  const _OrderCard({
    required this.orderId,
    required this.restaurantName,
    required this.statusLabel,
    required this.currentCourierId,
    required this.suggestedCourierIds,
    required this.courierById,
    required this.etaSecondsByCourierId,
    required this.etaLoading,
    required this.selectedCourierId,
    required this.courierItems,
    required this.fmtEta,
    required this.onSelectCourier,
    required this.onCalcEta,
    required this.onSelectBest,
    required this.onAssignBest,
    required this.onAssignSelected,
  });

  final int orderId;
  final String restaurantName;
  final String statusLabel;
  final int? currentCourierId;
  final List<int> suggestedCourierIds;
  final Map<int, Map<String, dynamic>> courierById;
  final Map<int, int> etaSecondsByCourierId;
  final bool etaLoading;
  final int? selectedCourierId;
  final List<DropdownMenuItem<int>> courierItems;
  final String Function(int seconds) fmtEta;
  final ValueChanged<int?> onSelectCourier;
  final VoidCallback? onCalcEta;
  final VoidCallback? onSelectBest;
  final Future<void> Function()? onAssignBest;
  final Future<void> Function()? onAssignSelected;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final onSurface = theme.colorScheme.onSurface;
    final onSurfaceVariant = theme.colorScheme.onSurfaceVariant;

    return Card(
      elevation: 0,
      clipBehavior: Clip.antiAlias,
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        '#$orderId • $restaurantName',
                        style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w700),
                        overflow: TextOverflow.ellipsis,
                      ),
                      const SizedBox(height: 4),
                      Text(
                        statusLabel,
                        style: theme.textTheme.bodySmall?.copyWith(color: onSurfaceVariant),
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 12),
                _StatusChip(text: statusLabel),
              ],
            ),
            if (suggestedCourierIds.isNotEmpty) ...[
              const SizedBox(height: 10),
              Wrap(
                spacing: 6,
                runSpacing: 6,
                children: suggestedCourierIds.map((cid) {
                  final c = courierById[cid];
                  final name = (c?['name'] ?? '').toString();
                  final isStale = (c?['is_stale'] ?? false) == true;
                  final label = name.isNotEmpty ? '#$cid $name' : '#$cid';
                  final eta = etaSecondsByCourierId[cid];

                  return ChoiceChip(
                    label: ConstrainedBox(
                      constraints: const BoxConstraints(maxWidth: 240),
                      child: Text(
                        eta == null ? label : '$label • ${fmtEta(eta)}',
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                    selected: selectedCourierId == cid,
                    onSelected: (_) => onSelectCourier(cid),
                    labelStyle: TextStyle(
                      fontSize: 12,
                      color: isStale ? onSurfaceVariant : onSurface,
                    ),
                    side: isStale
                        ? BorderSide(color: onSurface.withValues(alpha: 0.12))
                        : const BorderSide(color: Colors.transparent),
                  );
                }).toList(),
              ),
              const SizedBox(height: 8),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                crossAxisAlignment: WrapCrossAlignment.center,
                children: [
                  TextButton.icon(
                    onPressed: onCalcEta,
                    icon: etaLoading
                        ? const SizedBox(width: 14, height: 14, child: CircularProgressIndicator(strokeWidth: 2))
                        : const Icon(Icons.schedule, size: 18),
                    label: const Text('ETA hesapla'),
                  ),
                  TextButton(
                    onPressed: onSelectBest,
                    child: const Text('En hızlı seç'),
                  ),
                  FilledButton.tonal(
                    onPressed: onAssignBest == null ? null : () => onAssignBest!(),
                    child: const Text('En hızlı ata'),
                  ),
                ],
              ),
            ] else ...[
              const SizedBox(height: 10),
              Text(
                'Yakın kurye önerisi yok.',
                style: theme.textTheme.bodySmall?.copyWith(color: onSurfaceVariant),
              ),
            ],
            const SizedBox(height: 10),
            Row(
              children: [
                Expanded(
                  child: DropdownButtonFormField<int>(
                    value: selectedCourierId,
                    items: courierItems,
                    onChanged: onSelectCourier,
                    isExpanded: true,
                    decoration: const InputDecoration(
                      labelText: 'Kurye seç',
                      isDense: true,
                      border: OutlineInputBorder(),
                    ),
                  ),
                ),
                const SizedBox(width: 10),
                FilledButton(
                  onPressed: onAssignSelected == null ? null : () => onAssignSelected!(),
                  child: Text(currentCourierId == null ? 'Ata' : 'Değiştir'),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _StatusChip extends StatelessWidget {
  const _StatusChip({required this.text});
  final String text;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    final t = text.toLowerCase();
    final Color bg = switch (t) {
      _ when t.contains('hazır') => isDark ? const Color(0xFF5C4700) : const Color(0xFFFFF1B8),
      _ when t.contains('hazırla') => isDark ? const Color(0xFF5C4700) : const Color(0xFFFFF1B8),
      _ when t.contains('bekle') => isDark ? const Color(0xFF203040) : const Color(0xFFE8F0FA),
      _ when t.contains('yolda') => isDark ? const Color(0xFF0F3B1E) : const Color(0xFFE3F7EA),
      _ when t.contains('teslim') => isDark ? const Color(0xFF0F3B1E) : const Color(0xFFE3F7EA),
      _ => isDark ? theme.colorScheme.surfaceContainerHighest : theme.colorScheme.surfaceContainerHigh,
    };

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        text,
        style: theme.textTheme.labelSmall?.copyWith(color: theme.colorScheme.onSurface),
      ),
    );
  }
}

