import 'package:flutter/material.dart';
import 'package:dio/dio.dart';
import 'package:kurye_mobile/features/restaurant/restaurant_orders_api.dart';

class RestaurantOrdersScreen extends StatefulWidget {
  const RestaurantOrdersScreen({super.key, required this.api});

  final RestaurantOrdersApi api;

  @override
  State<RestaurantOrdersScreen> createState() => _RestaurantOrdersScreenState();
}

class _RestaurantOrdersScreenState extends State<RestaurantOrdersScreen> {
  late final WidgetsBindingObserver _lifecycleObserver;
  bool _polling = false;
  bool _pollingBusy = false;
  String _lastOrdersSignature = '';
  bool _loading = true;
  List<dynamic> _orders = const [];
  String? _error;
  int _ordersTab = 0; // 0: aktif, 1: tümü
  int _menuTab = 0; // 0:sipariş 1:müşteri 2:ürün 3:kategori 4:rapor
  Future<Map<String, dynamic>>? _customersFuture;
  Future<Map<String, dynamic>>? _productsFuture;
  Future<Map<String, dynamic>>? _categoriesFuture;
  Future<Map<String, dynamic>>? _reportsFuture;
  String _customerQuery = '';
  DateTime? _customerFrom;
  DateTime? _customerTo;
  String _productQuery = '';
  String _productStatus = 'all';
  String _categoryQuery = '';
  DateTime? _reportFrom;
  DateTime? _reportTo;

  @override
  void initState() {
    super.initState();
    _lifecycleObserver = _RestaurantOrdersLifecycleObserver(onResumed: () {
      if (!mounted) return;
      _load(silent: true);
    });
    WidgetsBinding.instance.addObserver(_lifecycleObserver);
    _load(silent: false);
    _startOrderPolling();
  }

  @override
  void dispose() {
    _polling = false;
    WidgetsBinding.instance.removeObserver(_lifecycleObserver);
    super.dispose();
  }

  void _startOrderPolling() {
    if (_polling) return;
    _polling = true;
    Future<void>(() async {
      while (_polling && mounted) {
        await Future<void>.delayed(const Duration(seconds: 6));
        if (!_polling || !mounted) break;
        if (_menuTab != 0) continue; // siparis sekmesi disinda poll etme
        if (_pollingBusy) continue;
        _pollingBusy = true;
        try {
          await _load(silent: true);
        } catch (_) {
          // keep polling
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
      final list = await widget.api.list();
      if (!mounted) return;
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
      final changed = signature != _lastOrdersSignature;
      _lastOrdersSignature = signature;

      if (silent && !changed) {
        return;
      }
      setState(() {
        _orders = list;
        if (!silent) {
          _customersFuture = widget.api.customers(q: _customerQuery, dateFrom: _customerFrom, dateTo: _customerTo);
          _productsFuture = widget.api.products(q: _productQuery, status: _productStatus);
          _categoriesFuture = widget.api.categories(q: _categoryQuery);
          _reportsFuture = widget.api.reportsSummary(dateFrom: _reportFrom, dateTo: _reportTo);
        }
        _loading = false;
        _error = null;
      });
    } catch (_) {
      if (!mounted) return;
      if (!silent) {
        setState(() {
          _error = 'Siparişler alınamadı.';
          _loading = false;
        });
      }
    }
  }

  Future<void> _showOrderDetail(int id) async {
    try {
      final d = await widget.api.show(id);
      if (!mounted) return;
      final items = (d['items'] as List?) ?? const [];
      final addr = d['delivery_address'] as Map?;
      final histories = (d['status_histories'] as List?) ?? const [];
      await showModalBottomSheet<void>(
        context: context,
        isScrollControlled: true,
        showDragHandle: true,
        builder: (ctx) {
          return Padding(
            padding: const EdgeInsets.all(16),
            child: ListView(
              shrinkWrap: true,
              children: [
                Text('Sipariş #$id', style: Theme.of(ctx).textTheme.titleLarge),
                const SizedBox(height: 8),
                Text('Müşteri: ${(d['customer_name'] ?? (d['customer'] as Map?)?['name'] ?? '—')}'),
                Text('Telefon: ${(d['customer_phone'] ?? (d['customer'] as Map?)?['phone'] ?? '—')}'),
                Text('Durum: ${_statusLabel((d['status'] ?? '').toString())}'),
                Text('Toplam: ${_asDouble(d['total_price']).toStringAsFixed(2)} ₺'),
                if (addr != null) ...[
                  const SizedBox(height: 8),
                  Text('Adres: ${(addr['address'] ?? '—').toString()}'),
                ],
                if ((d['notes'] ?? '').toString().trim().isNotEmpty) ...[
                  const SizedBox(height: 8),
                  Text('Not: ${d['notes']}'),
                ],
                const SizedBox(height: 12),
                Text('Ürünler', style: Theme.of(ctx).textTheme.titleMedium),
                const SizedBox(height: 6),
                if (items.isEmpty)
                  const Text('Ürün bulunamadı.')
                else
                  ...items.map((e) {
                    final m = Map<String, dynamic>.from(e as Map);
                    final qty = (m['quantity'] as num?)?.toInt() ?? 0;
                    final name = (m['product_name'] ?? m['name'] ?? 'Ürün').toString();
                    final price = _asDouble(m['price']);
                    return ListTile(
                      dense: true,
                      contentPadding: EdgeInsets.zero,
                      title: Text(name),
                      subtitle: Text('Adet: $qty'),
                      trailing: Text('${price.toStringAsFixed(2)} ₺'),
                    );
                  }),
                const SizedBox(height: 12),
                Text('Durum geçmişi', style: Theme.of(ctx).textTheme.titleMedium),
                const SizedBox(height: 6),
                if (histories.isEmpty)
                  const Text('Durum geçmişi yok.')
                else
                  ...histories.map((e) {
                    final m = Map<String, dynamic>.from(e as Map);
                    final status = _statusLabel((m['status'] ?? '').toString());
                    final raw = (m['created_at'] ?? '').toString();
                    final dt = DateTime.tryParse(raw);
                    final dtText = dt != null
                        ? '${dt.day.toString().padLeft(2, '0')}.${dt.month.toString().padLeft(2, '0')}.${dt.year} ${dt.hour.toString().padLeft(2, '0')}:${dt.minute.toString().padLeft(2, '0')}'
                        : raw;
                    return Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Column(
                          children: [
                            Container(width: 10, height: 10, decoration: const BoxDecoration(color: Colors.green, shape: BoxShape.circle)),
                            Container(
                              width: 2,
                              height: 24,
                              color: Theme.of(ctx).colorScheme.onSurface.withValues(alpha: 0.15),
                            ),
                          ],
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: Padding(
                            padding: const EdgeInsets.only(bottom: 8),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(status, style: Theme.of(ctx).textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.w700)),
                                Text(
                                  dtText,
                                  style: Theme.of(ctx).textTheme.bodySmall?.copyWith(
                                    color: Theme.of(ctx).colorScheme.onSurfaceVariant,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ],
                    );
                  }),
              ],
            ),
          );
        },
      );
    } on DioException catch (e) {
      final data = e.response?.data;
      final msg = data is Map && data['message'] != null ? data['message'].toString() : 'Detay alınamadı.';
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));
    } catch (_) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Detay alınamadı.')));
    }
  }

  String _statusLabel(String s) {
    return switch (s) {
      'pending' => 'Beklemede',
      'accepted' => 'Onaylandı',
      'preparing' => 'Hazırlanıyor',
      'ready' => 'Hazır',
      'courier_assigned' => 'Kurye atandı',
      'courier_accepted' => 'Kabul etti',
      'picked_up' => 'Alındı',
      'on_the_way' => 'Yolda',
      'delivered' => 'Teslim edildi',
      'cancelled' => 'İptal',
      _ => s,
    };
  }

  /// Restoran + kurye aşaması: teslim veya iptal olmayan her şey (yolda, alındı, kurye atandı vb.).
  bool _isOpenOrder(String s) {
    return s != 'delivered' && s != 'cancelled';
  }

  /// “Aktif siparişler” sekmesi: açık siparişler (kuryede / yolda dahil).
  bool _isActiveStatus(String s) => _isOpenOrder(s);

  double _asDouble(dynamic v) {
    if (v is num) return v.toDouble();
    return double.tryParse('${v ?? ''}') ?? 0.0;
  }

  Future<void> _pickDate({
    required DateTime? initial,
    required ValueChanged<DateTime?> onPicked,
  }) async {
    final now = DateTime.now();
    final picked = await showDatePicker(
      context: context,
      initialDate: initial ?? now,
      firstDate: DateTime(now.year - 5),
      lastDate: DateTime(now.year + 2),
    );
    if (picked == null || !mounted) return;
    setState(() => onPicked(picked));
  }

  void _reloadInsights() {
    setState(() {
      _customersFuture = widget.api.customers(q: _customerQuery, dateFrom: _customerFrom, dateTo: _customerTo);
      _productsFuture = widget.api.products(q: _productQuery, status: _productStatus);
      _categoriesFuture = widget.api.categories(q: _categoryQuery);
      _reportsFuture = widget.api.reportsSummary(dateFrom: _reportFrom, dateTo: _reportTo);
    });
  }

  String _fmtDate(DateTime? d) {
    if (d == null) return 'Seç';
    final m = d.month.toString().padLeft(2, '0');
    final day = d.day.toString().padLeft(2, '0');
    return '${d.year}-$m-$day';
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

  ({int total, int active, int waiting, int delivered}) _orderCounts() {
    int active = 0;
    int waiting = 0;
    int delivered = 0;
    for (final raw in _orders) {
      final m = raw as Map? ?? const {};
      final s = (m['status'] ?? '').toString();
      if (s == 'delivered') {
        delivered++;
      } else if (s == 'pending') {
        waiting++;
      } else if (_isOpenOrder(s)) {
        // Onaylandı / hazırlanıyor / hazır / kuryede / yolda … (pending ve terminal hariç)
        active++;
      }
    }
    return (total: _orders.length, active: active, waiting: waiting, delivered: delivered);
  }

  /// Son yüklenen sipariş listesine göre bugünkü tutarlar (API tarih filtresi yok).
  ({double todayTotal, double todayDelivered}) _todayFinance() {
    final now = DateTime.now();
    final dayStart = DateTime(now.year, now.month, now.day);
    final dayEnd = dayStart.add(const Duration(days: 1));

    double todayTotal = 0;
    double todayDelivered = 0;
    for (final raw in _orders) {
      final m = raw as Map? ?? const {};
      final price = _asDouble(m['total_price']);
      final created = DateTime.tryParse((m['created_at'] ?? '').toString());
      if (created != null && !created.isBefore(dayStart) && created.isBefore(dayEnd)) {
        todayTotal += price;
      }
      if ((m['status'] ?? '').toString() == 'delivered') {
        final updated = DateTime.tryParse((m['updated_at'] ?? '').toString());
        final ref = updated ?? created;
        if (ref != null && !ref.isBefore(dayStart) && ref.isBefore(dayEnd)) {
          todayDelivered += price;
        }
      }
    }
    return (todayTotal: todayTotal, todayDelivered: todayDelivered);
  }

  /// Son 7 gün (bugün dahil) günlük sipariş tutarı; mini bar yüksekliği 0–1.
  List<double> _weekRevenueBarFractions() {
    final now = DateTime.now();
    final weekStart = DateTime(now.year, now.month, now.day).subtract(const Duration(days: 6));
    final daily = List<double>.filled(7, 0);

    for (final raw in _orders) {
      final m = raw as Map? ?? const {};
      final created = DateTime.tryParse((m['created_at'] ?? '').toString());
      if (created == null) continue;
      final d = DateTime(created.year, created.month, created.day);
      if (d.isBefore(weekStart)) continue;
      final idx = d.difference(weekStart).inDays;
      if (idx < 0 || idx > 6) continue;
      daily[idx] += _asDouble(m['total_price']);
    }

    final maxV = daily.reduce((a, b) => a > b ? a : b);
    if (maxV <= 0) {
      return const [0.18, 0.22, 0.16, 0.26, 0.30, 0.44, 0.58];
    }
    return daily.map((v) => (v / maxV).clamp(0.14, 1.0)).toList();
  }

  String _fmtMoney(num v) => '${v.toStringAsFixed(2)} ₺';

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final now = DateTime.now();
    final counts = _orderCounts();
    final todayFin = _todayFinance();
    final weekBars = _weekRevenueBarFractions();
    final activeOrders = _orders.where((o) {
      final m = o as Map? ?? const {};
      return _isActiveStatus((m['status'] ?? '').toString());
    }).toList(growable: false);
    final listToRender = _ordersTab == 0 ? activeOrders : _orders;
    return Scaffold(
      appBar: AppBar(
        title: const Text('Restoran'),
        actions: [IconButton(onPressed: () => _load(silent: false), icon: const Icon(Icons.refresh))],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(child: Text(_error!))
              : RefreshIndicator(
                  onRefresh: () => _load(silent: false),
                  child: ListView(
                    padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
                    children: [
                      Card(
                        child: Padding(
                          padding: const EdgeInsets.all(12),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                children: [
                                  Icon(Icons.storefront_outlined, size: 18, color: theme.colorScheme.primary),
                                  const SizedBox(width: 8),
                                  Text(
                                    'Restoran Yönetimi',
                                    style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w800),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 10),
                              SizedBox(
                                height: 34,
                                child: ListView.separated(
                                  scrollDirection: Axis.horizontal,
                                  itemCount: _menuItems.length,
                                  separatorBuilder: (_, __) => const SizedBox(width: 8),
                                  itemBuilder: (context, i) {
                                    final selected = _menuTab == i;
                                    final item = _menuItems[i];
                                    return ChoiceChip(
                                      avatar: Icon(item.icon, size: 16, color: selected ? theme.colorScheme.primary : null),
                                      label: Text(item.label),
                                      selected: selected,
                                      onSelected: (_) => setState(() => _menuTab = i),
                                      labelStyle: theme.textTheme.labelSmall?.copyWith(fontWeight: FontWeight.w700),
                                      visualDensity: VisualDensity.compact,
                                    );
                                  },
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(height: 12),
                      if (_menuTab == 0) ...[
                        Card(
                          child: Padding(
                            padding: const EdgeInsets.all(14),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  'Merhaba, restoran',
                                  style: theme.textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.w900),
                                ),
                                const SizedBox(height: 2),
                                Text(
                                  _formatTurkishDate(now),
                                  style: theme.textTheme.bodySmall?.copyWith(color: theme.colorScheme.onSurfaceVariant),
                                ),
                                const SizedBox(height: 12),
                                GridView.count(
                                  crossAxisCount: 2,
                                  crossAxisSpacing: 10,
                                  mainAxisSpacing: 10,
                                  childAspectRatio: 4.2,
                                  shrinkWrap: true,
                                  physics: const NeverScrollableScrollPhysics(),
                                  children: [
                                    _metricTile(theme, 'Toplam', '${counts.total}', Icons.receipt_long_outlined),
                                    _metricTile(theme, 'Aktif', '${counts.active}', Icons.bolt_outlined),
                                    _metricTile(theme, 'Bekleyen', '${counts.waiting}', Icons.schedule_outlined),
                                    _metricTile(theme, 'Teslim', '${counts.delivered}', Icons.check_circle_outline),
                                  ],
                                ),
                                const SizedBox(height: 10),
                                _RestaurantFinanceCard(
                                  totalRevenueTl: todayFin.todayTotal,
                                  deliveredRevenueTl: todayFin.todayDelivered,
                                  fmtMoney: _fmtMoney,
                                  barFractions: weekBars,
                                  onOpenReport: () => setState(() => _menuTab = 4),
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  'Son 100 siparişe göre (bugün ve 7 gün grafiği)',
                                  style: theme.textTheme.labelSmall?.copyWith(color: theme.colorScheme.onSurfaceVariant),
                                ),
                              ],
                            ),
                          ),
                        ),
                        const SizedBox(height: 10),
                        Row(
                          children: [
                            Text('Son Siparişler', style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w800)),
                            const Spacer(),
                            TextButton(onPressed: () => setState(() => _ordersTab = 1), child: const Text('Tümünü gör')),
                          ],
                        ),
                        const SizedBox(height: 6),
                        SegmentedButton<int>(
                          segments: const [
                            ButtonSegment(value: 0, label: Text('Aktif siparişler')),
                            ButtonSegment(value: 1, label: Text('Tüm siparişler')),
                          ],
                          selected: {_ordersTab},
                          onSelectionChanged: (s) => setState(() => _ordersTab = s.first),
                        ),
                        const SizedBox(height: 8),
                        if (_ordersTab == 0 && activeOrders.isEmpty)
                          Card(
                            child: Padding(
                              padding: const EdgeInsets.all(12),
                              child: Text(
                                'Bekleyen aktif sipariş yok.',
                                style: theme.textTheme.bodyMedium?.copyWith(color: theme.colorScheme.onSurfaceVariant),
                              ),
                            ),
                          ),
                        ...listToRender.take(20).map((row) => _buildCompactOrderRow(theme, row as Map? ?? const {})),
                      ] else if (_menuTab == 1) ...[
                        Card(
                          child: Padding(
                            padding: const EdgeInsets.all(12),
                            child: Column(
                              children: [
                                TextField(
                                  decoration: const InputDecoration(labelText: 'Müşteri ara', isDense: true),
                                  onChanged: (v) => _customerQuery = v,
                                ),
                                const SizedBox(height: 8),
                                Row(
                                  children: [
                                    Expanded(
                                      child: OutlinedButton(
                                        onPressed: () => _pickDate(
                                          initial: _customerFrom,
                                          onPicked: (d) => _customerFrom = d,
                                        ),
                                        child: Text('Başlangıç: ${_fmtDate(_customerFrom)}'),
                                      ),
                                    ),
                                    const SizedBox(width: 8),
                                    Expanded(
                                      child: OutlinedButton(
                                        onPressed: () => _pickDate(
                                          initial: _customerTo,
                                          onPicked: (d) => _customerTo = d,
                                        ),
                                        child: Text('Bitiş: ${_fmtDate(_customerTo)}'),
                                      ),
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 8),
                                Row(
                                  children: [
                                    Expanded(
                                      child: OutlinedButton(
                                        onPressed: () {
                                          setState(() {
                                            _customerQuery = '';
                                            _customerFrom = null;
                                            _customerTo = null;
                                          });
                                          _reloadInsights();
                                        },
                                        child: const Text('Sıfırla'),
                                      ),
                                    ),
                                    const SizedBox(width: 8),
                                    Expanded(
                                      child: FilledButton(
                                        onPressed: _reloadInsights,
                                        child: const Text('Uygula'),
                                      ),
                                    ),
                                  ],
                                ),
                              ],
                            ),
                          ),
                        ),
                        const SizedBox(height: 8),
                        _buildInsightsSection(_customersFuture, 'Müşteri özeti', (json) {
                          final summary = (json['summary'] as Map?) ?? const {};
                          final data = (json['data'] as List?) ?? const [];
                          return Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              _summaryLine('Müşteri', '${summary['customer_count'] ?? 0}'),
                              _summaryLine('Sipariş', '${summary['order_count'] ?? 0}'),
                              _summaryLine('Ciro', '${_asDouble(summary['total_spent']).toStringAsFixed(2)} ₺'),
                              const SizedBox(height: 8),
                              ...data.take(25).map((e) {
                                final m = Map<String, dynamic>.from(e as Map);
                                return _simpleRow(
                                  '${m['name'] ?? 'Müşteri'}',
                                  '${m['order_count'] ?? 0} sipariş',
                                  onTap: () => _showInsightItemDetail('Müşteri detayı', m),
                                );
                              }),
                            ],
                          );
                        }),
                      ] else if (_menuTab == 2) ...[
                        Card(
                          child: Padding(
                            padding: const EdgeInsets.all(12),
                            child: Column(
                              children: [
                                TextField(
                                  decoration: const InputDecoration(labelText: 'Ürün ara', isDense: true),
                                  onChanged: (v) => _productQuery = v,
                                ),
                                const SizedBox(height: 8),
                                DropdownButtonFormField<String>(
                                  initialValue: _productStatus,
                                  items: const [
                                    DropdownMenuItem(value: 'all', child: Text('Tümü')),
                                    DropdownMenuItem(value: 'active', child: Text('Aktif')),
                                    DropdownMenuItem(value: 'inactive', child: Text('Pasif')),
                                  ],
                                  onChanged: (v) => _productStatus = v ?? 'all',
                                  decoration: const InputDecoration(labelText: 'Durum', isDense: true),
                                ),
                                const SizedBox(height: 8),
                                Row(
                                  children: [
                                    Expanded(
                                      child: OutlinedButton(
                                        onPressed: () {
                                          setState(() {
                                            _productQuery = '';
                                            _productStatus = 'all';
                                          });
                                          _reloadInsights();
                                        },
                                        child: const Text('Sıfırla'),
                                      ),
                                    ),
                                    const SizedBox(width: 8),
                                    Expanded(
                                      child: FilledButton(onPressed: _reloadInsights, child: const Text('Uygula')),
                                    ),
                                  ],
                                ),
                              ],
                            ),
                          ),
                        ),
                        const SizedBox(height: 8),
                        _buildInsightsSection(_productsFuture, 'Ürün özeti', (json) {
                          final summary = (json['summary'] as Map?) ?? const {};
                          final data = (json['data'] as List?) ?? const [];
                          return Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              _summaryLine('Ürün', '${summary['product_count'] ?? 0}'),
                              _summaryLine('Aktif', '${summary['active_count'] ?? 0}'),
                              const SizedBox(height: 8),
                              ...data.take(30).map((e) {
                                final m = Map<String, dynamic>.from(e as Map);
                                return _simpleRow(
                                  '${m['name'] ?? '-'}',
                                  '${_asDouble(m['price']).toStringAsFixed(2)} ₺',
                                  onTap: () => _showInsightItemDetail('Ürün detayı', m),
                                );
                              }),
                            ],
                          );
                        }),
                      ] else if (_menuTab == 3) ...[
                        Card(
                          child: Padding(
                            padding: const EdgeInsets.all(12),
                            child: Row(
                              children: [
                                Expanded(
                                  child: TextField(
                                    decoration: const InputDecoration(labelText: 'Kategori ara', isDense: true),
                                    onChanged: (v) => _categoryQuery = v,
                                  ),
                                ),
                                const SizedBox(width: 8),
                                FilledButton(onPressed: _reloadInsights, child: const Text('Uygula')),
                              ],
                            ),
                          ),
                        ),
                        const SizedBox(height: 8),
                        _buildInsightsSection(_categoriesFuture, 'Kategori özeti', (json) {
                          final summary = (json['summary'] as Map?) ?? const {};
                          final data = (json['data'] as List?) ?? const [];
                          return Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              _summaryLine('Kategori', '${summary['category_count'] ?? 0}'),
                              _summaryLine('Kategori içi ürün', '${summary['products_in_categories'] ?? 0}'),
                              const SizedBox(height: 8),
                              ...data.take(30).map((e) {
                                final m = Map<String, dynamic>.from(e as Map);
                                return _simpleRow(
                                  '${m['name'] ?? '-'}',
                                  '${m['products_count'] ?? 0} ürün',
                                  onTap: () => _showInsightItemDetail('Kategori detayı', m),
                                );
                              }),
                            ],
                          );
                        }),
                      ] else ...[
                        Card(
                          child: Padding(
                            padding: const EdgeInsets.all(12),
                            child: Column(
                              children: [
                                Row(
                                  children: [
                                    Expanded(
                                      child: OutlinedButton(
                                        onPressed: () => _pickDate(
                                          initial: _reportFrom,
                                          onPicked: (d) => _reportFrom = d,
                                        ),
                                        child: Text('Başlangıç: ${_fmtDate(_reportFrom)}'),
                                      ),
                                    ),
                                    const SizedBox(width: 8),
                                    Expanded(
                                      child: OutlinedButton(
                                        onPressed: () => _pickDate(
                                          initial: _reportTo,
                                          onPicked: (d) => _reportTo = d,
                                        ),
                                        child: Text('Bitiş: ${_fmtDate(_reportTo)}'),
                                      ),
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 8),
                                Row(
                                  children: [
                                    Expanded(
                                      child: OutlinedButton(
                                        onPressed: () {
                                          setState(() {
                                            _reportFrom = null;
                                            _reportTo = null;
                                          });
                                          _reloadInsights();
                                        },
                                        child: const Text('Sıfırla'),
                                      ),
                                    ),
                                    const SizedBox(width: 8),
                                    Expanded(
                                      child: FilledButton(onPressed: _reloadInsights, child: const Text('Uygula')),
                                    ),
                                  ],
                                ),
                              ],
                            ),
                          ),
                        ),
                        const SizedBox(height: 8),
                        _buildInsightsSection(_reportsFuture, 'Genel rapor', (json) {
                          final byStatus = (json['by_status'] as Map?) ?? const {};
                          return Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              _summaryLine('Toplam sipariş', '${json['order_total'] ?? 0}'),
                              _summaryLine('Teslim', '${json['delivered_count'] ?? 0}'),
                              _summaryLine('İptal', '${json['cancelled_count'] ?? 0}'),
                              _summaryLine('Ciro', '${_asDouble(json['revenue']).toStringAsFixed(2)} ₺'),
                              const SizedBox(height: 8),
                              ...byStatus.entries.map((e) => _simpleRow(_statusLabel(e.key.toString()), '${e.value}')),
                            ],
                          );
                        }),
                      ],
                    ],
                  ),
                ),
    );
  }

  Widget _buildCompactOrderRow(ThemeData theme, Map o) {
    final id = int.tryParse(o['id'].toString()) ?? 0;
    final status = (o['status'] ?? '').toString();
    final customer = ((o['customer_name'] ?? '').toString().trim().isNotEmpty)
        ? o['customer_name'].toString()
        : (((o['customer'] as Map?)?['name'] ?? 'Müşteri')).toString();
    final total = _asDouble(o['total_price']);
    final createdAtRaw = (o['created_at'] ?? '').toString();
    final createdAt = DateTime.tryParse(createdAtRaw);

    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Card(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
          child: Row(
            children: [
              Container(
                width: 34,
                height: 34,
                decoration: BoxDecoration(
                  color: theme.colorScheme.surfaceContainerHighest.withValues(alpha: 0.3),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Icon(Icons.receipt_long_outlined, size: 18, color: theme.colorScheme.primary),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('#$id', style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w800)),
                    const SizedBox(height: 1),
                    Text(
                      customer,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: theme.textTheme.bodySmall?.copyWith(color: theme.colorScheme.onSurfaceVariant),
                    ),
                    const SizedBox(height: 1),
                    Text(
                      '${createdAt != null ? '${createdAt.day.toString().padLeft(2, '0')}.${createdAt.month.toString().padLeft(2, '0')} ${createdAt.hour.toString().padLeft(2, '0')}:${createdAt.minute.toString().padLeft(2, '0')}' : createdAtRaw} • ${total.toStringAsFixed(2)} ₺',
                      style: theme.textTheme.labelSmall?.copyWith(color: theme.colorScheme.onSurfaceVariant),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 8),
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  _StatusPill(text: _statusLabel(status)),
                  const SizedBox(height: 4),
                  TextButton(
                    onPressed: () => _showOrderDetail(id),
                    child: const Text('Detay'),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildInsightsSection(
    Future<Map<String, dynamic>>? future,
    String title,
    Widget Function(Map<String, dynamic> json) builder,
  ) {
    return FutureBuilder<Map<String, dynamic>>(
      future: future,
      builder: (context, snap) {
        if (snap.connectionState == ConnectionState.waiting) {
          return const Center(child: Padding(padding: EdgeInsets.all(20), child: CircularProgressIndicator()));
        }
        if (snap.hasError || !snap.hasData) {
          return Card(
            child: Padding(
              padding: const EdgeInsets.all(12),
              child: Text('$title yüklenemedi.'),
            ),
          );
        }
        return Card(
          child: Padding(
            padding: const EdgeInsets.all(12),
            child: builder(snap.data!),
          ),
        );
      },
    );
  }

  Widget _metricTile(ThemeData theme, String label, String value, IconData icon) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
      decoration: BoxDecoration(
        color: theme.colorScheme.surfaceContainerHighest.withValues(alpha: theme.brightness == Brightness.dark ? 0.14 : 0.55),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        children: [
          Icon(icon, size: 15, color: theme.colorScheme.primary),
          const SizedBox(width: 6),
          Expanded(
            child: Text(
              label.toUpperCase(),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: theme.textTheme.labelSmall?.copyWith(
                fontWeight: FontWeight.w800,
                color: theme.colorScheme.onSurfaceVariant,
              ),
            ),
          ),
          const SizedBox(width: 8),
          Text(
            value,
            style: theme.textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.w900,
              color: theme.colorScheme.primary,
            ),
          ),
        ],
      ),
    );
  }

  Widget _summaryLine(String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 4),
      child: Row(
        children: [
          Expanded(child: Text(label)),
          Text(value, style: const TextStyle(fontWeight: FontWeight.w700)),
        ],
      ),
    );
  }

  Future<void> _showInsightItemDetail(String title, Map<String, dynamic> item) async {
    await showModalBottomSheet<void>(
      context: context,
      showDragHandle: true,
      builder: (ctx) => Padding(
        padding: const EdgeInsets.all(16),
        child: ListView(
          shrinkWrap: true,
          children: [
            Text(title, style: Theme.of(ctx).textTheme.titleLarge),
            const SizedBox(height: 10),
            ...item.entries.map(
              (e) => Padding(
                padding: const EdgeInsets.only(bottom: 8),
                child: Row(
                  children: [
                    Expanded(
                      child: Text(
                        e.key,
                        style: Theme.of(ctx).textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.w700),
                      ),
                    ),
                    const SizedBox(width: 10),
                    Flexible(child: Text('${e.value ?? ''}', textAlign: TextAlign.right)),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _simpleRow(String left, String right, {VoidCallback? onTap}) {
    final row = Padding(
      padding: const EdgeInsets.only(bottom: 6),
      child: Row(
        children: [
          Expanded(child: Text(left, maxLines: 1, overflow: TextOverflow.ellipsis)),
          const SizedBox(width: 8),
          Text(right, style: TextStyle(color: Theme.of(context).colorScheme.onSurfaceVariant)),
        ],
      ),
    );
    if (onTap == null) return row;
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(8),
      child: row,
    );
  }
}

class _RestaurantFinanceCard extends StatelessWidget {
  const _RestaurantFinanceCard({
    required this.totalRevenueTl,
    required this.deliveredRevenueTl,
    required this.fmtMoney,
    required this.barFractions,
    this.onOpenReport,
  });

  final double totalRevenueTl;
  final double deliveredRevenueTl;
  final String Function(num v) fmtMoney;
  final List<double> barFractions;
  final VoidCallback? onOpenReport;

  static const Color _accentGreen = Color(0xFF2E7D32);

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
                  tooltip: 'Rapor',
                  onPressed: onOpenReport,
                  icon: const Icon(Icons.trending_up, size: 22, color: _accentGreen),
                ),
              ],
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: _RestaurantFinanceMetric(
                    label: 'TOPLAM GELİR',
                    value: fmtMoney(totalRevenueTl),
                    valueStyle: theme.textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w900),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: _RestaurantFinanceMetric(
                    label: 'TESLİM CİRO',
                    value: fmtMoney(deliveredRevenueTl),
                    valueStyle: theme.textTheme.titleLarge?.copyWith(
                      fontWeight: FontWeight.w900,
                      color: _accentGreen,
                    ),
                    trailing: const Icon(Icons.trending_up, size: 18, color: _accentGreen),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 14),
            _RestaurantMiniBarChart(fractions: barFractions),
          ],
        ),
      ),
    );
  }
}

class _RestaurantFinanceMetric extends StatelessWidget {
  const _RestaurantFinanceMetric({
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
            Expanded(child: Text(value, style: valueStyle)),
            if (trailing != null) trailing!,
          ],
        ),
      ],
    );
  }
}

class _RestaurantMiniBarChart extends StatelessWidget {
  const _RestaurantMiniBarChart({required this.fractions});

  final List<double> fractions;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final bars = List<double>.generate(7, (i) {
      if (fractions.length > i) return fractions[i].clamp(0.12, 1.0);
      return 0.2;
    });
    return SizedBox(
      height: 56,
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.end,
        children: [
          for (var i = 0; i < 7; i++) ...[
            Expanded(
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 3),
                child: AnimatedContainer(
                  duration: const Duration(milliseconds: 250),
                  height: 56 * bars[i].clamp(0.12, 1.0),
                  decoration: BoxDecoration(
                    color: i == 6
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

class _RestaurantOrdersLifecycleObserver with WidgetsBindingObserver {
  _RestaurantOrdersLifecycleObserver({required this.onResumed});

  final VoidCallback onResumed;

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      onResumed();
    }
  }
}

class _MenuItem {
  const _MenuItem(this.label, this.icon);
  final String label;
  final IconData icon;
}

const List<_MenuItem> _menuItems = [
  _MenuItem('Sipariş', Icons.receipt_long_outlined),
  _MenuItem('Müşteri', Icons.group_outlined),
  _MenuItem('Ürün', Icons.fastfood_outlined),
  _MenuItem('Kategori', Icons.category_outlined),
  _MenuItem('Rapor', Icons.bar_chart_outlined),
];

class _StatusPill extends StatelessWidget {
  const _StatusPill({required this.text});
  final String text;

  @override
  Widget build(BuildContext context) {
    final t = text.toLowerCase();
    final Color bg = switch (t) {
      _ when t.contains('ready') || t.contains('hazır') => Colors.green.shade100,
      _ when t.contains('prepar') || t.contains('hazırl') => Colors.amber.shade100,
      _ when t.contains('cancel') || t.contains('iptal') => Colors.red.shade100,
      _ => Colors.blueGrey.shade50,
    };

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        text,
        style: Theme.of(context).textTheme.labelSmall?.copyWith(fontWeight: FontWeight.w700),
      ),
    );
  }
}

