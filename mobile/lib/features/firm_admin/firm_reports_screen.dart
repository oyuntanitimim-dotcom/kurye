import 'dart:async';
import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:kurye_mobile/core/app_scope.dart';
import 'package:kurye_mobile/core/ui/app_content.dart';
import 'package:kurye_mobile/core/ui/glass_card.dart';
import 'package:kurye_mobile/features/firm_admin/firm_admin_api.dart';

double _dbl(dynamic x) => x is num ? x.toDouble() : double.tryParse('$x') ?? 0.0;

int _int(dynamic x) => x is num ? x.toInt() : int.tryParse('$x') ?? 0;

const List<(String, String)> _firmReportPresetChips = [
  ('today', 'Bugün'),
  ('7d', 'Son 7 gün'),
  ('30d', 'Son 30 gün'),
  ('this_month', 'Bu ay'),
  ('last_month', 'Geçen ay'),
];

String _fmt2(double v) => '${v.toStringAsFixed(2)} ₺';

String _isoDate(DateTime d) =>
    '${d.year.toString().padLeft(4, '0')}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';

typedef _ReportFilterCommitted = Future<void> Function({
  required int? restaurantId,
  required String preset,
  required bool useCustomDates,
  DateTime? rangeStart,
  DateTime? rangeEnd,
});

class _ReportsFilterBottomSheetContent extends StatefulWidget {
  const _ReportsFilterBottomSheetContent({
    required this.restaurants,
    required this.initialRestaurantId,
    required this.initialPreset,
    required this.initialUseCustomDates,
    this.initialRangeStart,
    this.initialRangeEnd,
    required this.onCommitted,
  });

  final List<Map<String, dynamic>> restaurants;
  final int? initialRestaurantId;
  final String initialPreset;
  final bool initialUseCustomDates;
  final DateTime? initialRangeStart;
  final DateTime? initialRangeEnd;
  final _ReportFilterCommitted onCommitted;

  @override
  State<_ReportsFilterBottomSheetContent> createState() => _ReportsFilterBottomSheetContentState();
}

class _ReportsFilterBottomSheetContentState extends State<_ReportsFilterBottomSheetContent> {
  late int? _restaurantId;
  late String _preset;
  late bool _custom;
  DateTime? _start;
  DateTime? _end;

  @override
  void initState() {
    super.initState();
    _restaurantId = widget.initialRestaurantId;
    _preset = widget.initialPreset;
    _custom = widget.initialUseCustomDates;
    _start = widget.initialRangeStart;
    _end = widget.initialRangeEnd;
  }

  Future<void> _pickRange() async {
    final now = DateTime.now();
    final rng = await showDateRangePicker(
      context: context,
      firstDate: DateTime(now.year - 2),
      lastDate: DateTime(now.year + 1),
      locale: const Locale('tr', 'TR'),
      initialDateRange: _start != null && _end != null ? DateTimeRange(start: _start!, end: _end!) : DateTimeRange(start: DateTime(now.year, now.month, 1), end: now),
    );
    if (rng == null) return;
    setState(() {
      _custom = true;
      _start = DateTime(rng.start.year, rng.start.month, rng.start.day);
      _end = DateTime(rng.end.year, rng.end.month, rng.end.day);
    });
  }

  Future<void> _apply() async {
    if (_custom) {
      if (_start == null || _end == null) {
        ScaffoldMessenger.maybeOf(context)?.showSnackBar(
          const SnackBar(content: Text('Özel tarih için aralığı seçin.')),
        );
        return;
      }
    }
    Navigator.of(context).pop();
    await widget.onCommitted(
      restaurantId: _restaurantId,
      preset: _preset,
      useCustomDates: _custom,
      rangeStart: _custom ? _start : null,
      rangeEnd: _custom ? _end : null,
    );
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final bottomInset = MediaQuery.of(context).viewInsets.bottom;

    return Padding(
      padding: EdgeInsets.only(left: 20, right: 20, bottom: 20 + bottomInset, top: 8),
      child: SingleChildScrollView(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          mainAxisSize: MainAxisSize.min,
          children: [
            Text('Rapor filtresi', style: theme.textTheme.titleMedium),
            const SizedBox(height: 14),
            Text('İşletme', style: theme.textTheme.labelLarge),
            const SizedBox(height: 6),
            DropdownButtonHideUnderline(
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 12),
                decoration: BoxDecoration(borderRadius: BorderRadius.circular(10), border: Border.all()),
                width: double.infinity,
                child: DropdownButton<int?>(
                  isExpanded: true,
                  value: _restaurantId,
                  items: [
                    const DropdownMenuItem<int?>(value: null, child: Text('Tümü')),
                    ...widget.restaurants.map(
                      (row) => DropdownMenuItem<int?>(
                        value: row['id'] as int?,
                        child: Text('${row['name']}'),
                      ),
                    ),
                  ],
                  onChanged: (v) => setState(() => _restaurantId = v),
                ),
              ),
            ),
            const SizedBox(height: 16),
            SegmentedButton<bool>(
              segments: const [
                ButtonSegment<bool>(value: false, label: Text('Hızlı seçim')),
                ButtonSegment<bool>(value: true, label: Text('Özel tarih')),
              ],
              selected: <bool>{_custom},
              onSelectionChanged: (s) => setState(() => _custom = s.contains(true)),
            ),
            const SizedBox(height: 12),
            if (!_custom) ...[
              Text('Periyot', style: theme.textTheme.labelLarge),
              const SizedBox(height: 6),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  for (final e in _firmReportPresetChips)
                    ChoiceChip(
                      label: Text(e.$2),
                      selected: _preset == e.$1,
                      onSelected: (_) => setState(() => _preset = e.$1),
                    ),
                ],
              ),
            ] else ...[
              OutlinedButton.icon(
                onPressed: _pickRange,
                icon: const Icon(Icons.date_range_outlined),
                label: Text(
                  _start != null && _end != null ? '${_isoDate(_start!)} – ${_isoDate(_end!)}' : 'Tarih aralığı seç',
                ),
              ),
            ],
            const SizedBox(height: 20),
            Row(
              children: [
                TextButton(
                  onPressed: () => Navigator.of(context).pop(),
                  child: const Text('İptal'),
                ),
                const Spacer(),
                FilledButton(onPressed: _apply, child: const Text('Uygula')),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class FirmReportsScreen extends StatefulWidget {
  const FirmReportsScreen({super.key});

  @override
  State<FirmReportsScreen> createState() => _FirmReportsScreenState();
}

class _FirmReportsScreenState extends State<FirmReportsScreen> with WidgetsBindingObserver {
  String _preset = 'this_month';
  bool _useCustomDates = false;
  DateTime? _rangeStart;
  DateTime? _rangeEnd;
  int? _restaurantId;

  Map<String, dynamic>? _data;
  Object? _error;
  bool _loading = true;
  bool _silentLoading = false;

  Timer? _pollTimer;

  bool _bootstrappedDeps = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
  }

  @override
  void dispose() {
    _pollTimer?.cancel();
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      _load(silent: true);
    }
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (_bootstrappedDeps) return;
    _bootstrappedDeps = true;
    _load();
    _pollTimer = Timer.periodic(const Duration(seconds: 45), (_) => _load(silent: true));
  }

  FirmAdminApi get _api => FirmAdminApi(AppScope.of(context).api);

  Future<void> _load({bool silent = false}) async {
    if (!silent) {
      setState(() {
        _loading = true;
        _error = null;
      });
    } else {
      setState(() => _silentLoading = true);
    }

    try {
      String? presetParam;
      String? df;
      String? dt;
      if (_useCustomDates && _rangeStart != null && _rangeEnd != null) {
        df = _isoDate(_rangeStart!);
        dt = _isoDate(_rangeEnd!);
      } else {
        presetParam = _preset;
      }
      final m = await _api.reportsSummary(
        preset: presetParam,
        restaurantId: _restaurantId,
        dateFrom: df,
        dateTo: dt,
        seriesDays: 14,
      );

      if (!mounted) return;
      setState(() {
        _data = m;
        _loading = false;
        _silentLoading = false;
        _error = null;
      });
    } catch (e, st) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        _silentLoading = false;
        _error = e;
      });
      debugPrint('$e\n$st');
    }
  }

  void _showFilterSheet() {
    final d = _data;
    final restaurantsRaw = (d?['restaurants'] as List?) ?? const [];

    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      showDragHandle: true,
      builder: (ctx) {
        return _ReportsFilterBottomSheetContent(
          restaurants: restaurantsRaw.map<Map<String, dynamic>>((e) => Map<String, dynamic>.from(e as Map)).toList(),
          initialRestaurantId: _restaurantId,
          initialPreset: _preset,
          initialUseCustomDates: _useCustomDates,
          initialRangeStart: _rangeStart,
          initialRangeEnd: _rangeEnd,
          onCommitted: ({
            required int? restaurantId,
            required String preset,
            required bool useCustomDates,
            DateTime? rangeStart,
            DateTime? rangeEnd,
          }) async {
            setState(() {
              _restaurantId = restaurantId;
              _preset = preset;
              _useCustomDates = useCustomDates;
              _rangeStart = rangeStart;
              _rangeEnd = rangeEnd;
            });
            await _load();
          },
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    if (_loading && _data == null) {
      return Scaffold(
        appBar: AppBar(title: const Text('Raporlar')),
        body: const Center(child: CircularProgressIndicator()),
      );
    }

    if (_error != null && _data == null) {
      return Scaffold(
        appBar: AppBar(title: const Text('Raporlar')),
        body: Center(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Text('Raporlar yüklenemedi.'),
                const SizedBox(height: 12),
                FilledButton(onPressed: () => _load(), child: const Text('Tekrar dene')),
              ],
            ),
          ),
        ),
      );
    }

    final d = _data!;
    final delivered = Map<String, dynamic>.from((d['delivered'] as Map?) ?? {});
    final byStatus = Map<String, dynamic>.from((d['by_status'] as Map?) ?? {});
    final cb = Map<String, dynamic>.from((d['courier_delivered_by_payment'] as Map?) ?? {});
    final courierRows = (d['courier_breakdown'] as List?) ?? const [];
    final restRows = (d['restaurants_by_revenue'] as List?) ?? const [];
    final catRows = (d['categories_by_revenue'] as List?) ?? const [];
    final prodRows = (d['products_by_revenue'] as List?) ?? const [];
    final daily = (d['daily'] as List?) ?? const [];

    final orderTotal = _int(d['order_total_filtered']);
    final cancelled = _int(byStatus['cancelled']);

    final net = _dbl(delivered['net']);
    final shopCount = _int(delivered['delivered_shop_count']);
    final shopRev = _dbl(delivered['delivered_shop_revenue']);

    final onlineMap = Map<String, dynamic>.from((cb['online'] as Map?) ?? {});
    final cardMap = Map<String, dynamic>.from((cb['card'] as Map?) ?? {});
    final cashMap = Map<String, dynamic>.from((cb['cash'] as Map?) ?? {});
    final otherMap = Map<String, dynamic>.from((cb['other'] as Map?) ?? {});

    return Scaffold(
      appBar: AppBar(
        title: const Text('Raporlar'),
        actions: [
          if (_silentLoading)
            Padding(
              padding: const EdgeInsets.only(right: 12),
              child: Center(
                child: SizedBox(
                  width: 18,
                  height: 18,
                  child: CircularProgressIndicator(strokeWidth: 2, color: theme.colorScheme.primary),
                ),
              ),
            ),
          IconButton(
            tooltip: 'Filtre',
            icon: Stack(
              children: [
                const Icon(Icons.tune_rounded),
                if (_restaurantId != null || _useCustomDates)
                  Positioned(
                    right: 0,
                    top: 0,
                    child: Container(
                      width: 8,
                      height: 8,
                      decoration: BoxDecoration(color: theme.colorScheme.primary, shape: BoxShape.circle),
                    ),
                  ),
              ],
            ),
            onPressed: _showFilterSheet,
          ),
          IconButton(
            tooltip: 'Yenile',
            onPressed: _load,
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      body: AppContent(
        child: RefreshIndicator(
          onRefresh: () => _load(silent: true),
          child: ListView(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 32),
            children: [
              Text(
                _filterSubtitle(d),
                style: theme.textTheme.bodySmall?.copyWith(color: theme.textTheme.bodySmall?.color?.withValues(alpha: 0.85)),
              ),
              const SizedBox(height: 6),
              Text(
                'Toplam ve durum: oluşturma tarihi. Ciro kesintileri: teslim tarihi.',
                style: theme.textTheme.labelSmall?.copyWith(color: theme.hintColor),
              ),
              const SizedBox(height: 14),
              GlassCard(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Özet (web ile aynı veri)', style: theme.textTheme.titleMedium),
                    const SizedBox(height: 10),
                    _KpiRow(a: 'Toplam sipariş (filtreli)', b: '$orderTotal'),
                    _KpiRow(a: 'Teslim edilen ciro', b: _fmt2(_dbl(delivered['revenue']))),
                    _KpiRow(a: 'Müşteri teslimat ücreti', b: _fmt2(_dbl(delivered['delivery_fees']))),
                    _KpiRow(a: 'İndirim toplamı', b: _fmt2(_dbl(delivered['discounts']))),
                    _KpiRow(a: 'Platform paket ücreti', b: _fmt2(_dbl(delivered['platform_fees']))),
                    _KpiRow(a: 'İşletme paket ücreti', b: _fmt2(_dbl(delivered['restaurant_commission']))),
                    _KpiRow(a: 'Kurye teslim ücreti', b: _fmt2(_dbl(delivered['courier_payouts']))),
                    Divider(color: theme.dividerColor.withValues(alpha: 0.35)),
                    _KpiRow(a: 'Net', b: _fmt2(net), boldValue: true),
                  ],
                ),
              ),
              const SizedBox(height: 12),
              Text('Ödeme türü — kurye teslim cirosu', style: theme.textTheme.titleMedium),
              const SizedBox(height: 8),
              Wrap(
                spacing: 10,
                runSpacing: 10,
                children: [
                  _PayCard(
                    label: 'ONLINE ÖDEME',
                    bg: Colors.lightBlue.withValues(alpha: 0.12),
                    border: Colors.blue.shade300,
                    count: _int(onlineMap['count']),
                    revenue: _dbl(onlineMap['revenue']),
                  ),
                  _PayCard(
                    label: 'KAPIDA KART',
                    bg: Colors.deepPurple.withValues(alpha: 0.1),
                    border: Colors.deepPurple.shade300,
                    count: _int(cardMap['count']),
                    revenue: _dbl(cardMap['revenue']),
                  ),
                  _PayCard(
                    label: 'KAPIDA NAKİT',
                    bg: Colors.amber.withValues(alpha: 0.12),
                    border: Colors.amber.shade700,
                    count: _int(cashMap['count']),
                    revenue: _dbl(cashMap['revenue']),
                  ),
                  if (_int(otherMap['count']) > 0)
                    _PayCard(
                      label: 'DİĞER',
                      bg: Colors.blueGrey.withValues(alpha: 0.1),
                      border: Colors.blueGrey.shade300,
                      count: _int(otherMap['count']),
                      revenue: _dbl(otherMap['revenue']),
                    ),
                  _PayCard(
                    label: 'DÜKKAN TESLİM (CİRO)',
                    bg: Colors.green.withValues(alpha: 0.1),
                    border: Colors.green.shade600,
                    count: shopCount,
                    revenue: shopRev,
                  ),
                ],
              ),
              const SizedBox(height: 16),
              if (courierRows.isNotEmpty) ...[
                Text('Kurye bazlı kesinti', style: theme.textTheme.titleMedium),
                const SizedBox(height: 8),
                ...courierRows.map((row) => _CourierCard(row: Map<String, dynamic>.from(row as Map))),
              ],
              if (restRows.isNotEmpty) ...[
                const SizedBox(height: 14),
                Text('İşletme bazlı satış', style: theme.textTheme.titleMedium),
                const SizedBox(height: 6),
                _SimpleQtyTable(theme: theme, rows: restRows.map((r) => Map<String, dynamic>.from(r as Map)).toList(), nameKey: 'restaurant_name'),
              ],
              if (catRows.isNotEmpty) ...[
                const SizedBox(height: 14),
                Text('Kategori bazlı satış', style: theme.textTheme.titleMedium),
                const SizedBox(height: 6),
                _SimpleQtyTable(theme: theme, rows: catRows.map((r) => Map<String, dynamic>.from(r as Map)).toList(), nameKey: 'category_name'),
              ],
              if (prodRows.isNotEmpty) ...[
                const SizedBox(height: 14),
                Text('Ürün bazlı satış', style: theme.textTheme.titleMedium),
                const SizedBox(height: 6),
                _SimpleQtyTable(theme: theme, rows: prodRows.map((r) => Map<String, dynamic>.from(r as Map)).toList(), nameKey: 'product_name'),
              ],
              const SizedBox(height: 14),
              GlassCard(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Duruma göre (oluşturma)', style: theme.textTheme.titleMedium),
                    const SizedBox(height: 8),
                    ...byStatus.entries.map(
                      (e) => Padding(
                        padding: const EdgeInsets.only(bottom: 6),
                        child: Row(
                          children: [
                            Expanded(child: Text(_statusLabelTr(e.key))),
                            Text(
                              '${e.value}',
                              style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.bold),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 12),
              GlassCard(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Text('Genel kutucuklar', style: theme.textTheme.titleMedium),
                        const Spacer(),
                        Text('${daily.length}g seri', style: theme.textTheme.labelMedium),
                      ],
                    ),
                    const SizedBox(height: 10),
                    Row(
                      children: [
                        Expanded(
                          child: _MetricTile(label: 'Teslim sipariş', value: '${_int(delivered['count'])}', delta: null),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: _MetricTile(label: 'İptaller', value: '${cancelled > 0 ? cancelled : '—'}', delta: null),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    SizedBox(height: 120, child: _MiniBars(values: daily)),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        Expanded(child: _MoneyTile(label: 'Teslim ciro', value: _fmt2(_dbl(delivered['revenue'])))),
                        const SizedBox(width: 8),
                        Expanded(child: _MoneyTile(label: 'İndirim', value: _fmt2(_dbl(delivered['discounts'])))),
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  String _filterSubtitle(Map<String, dynamic> d) {
    final parts = <String>[];
    if (_restaurantId != null) {
      final rests = (d['restaurants'] as List?) ?? const [];
      String? nm;
      for (final x in rests) {
        final m = Map<String, dynamic>.from(x as Map);
        if (m['id'] == _restaurantId) nm = '${m['name']}';
      }
      parts.add(nm != null ? 'İşletme: $nm' : 'İşletme #${_restaurantId!}');
    } else {
      parts.add('İşletme: Tümü');
    }

    if (_useCustomDates && _rangeStart != null && _rangeEnd != null) {
      parts.add('Tarih: ${_isoDate(_rangeStart!)} → ${_isoDate(_rangeEnd!)}');
    } else {
      final map = {'today': 'Bugün', '7d': 'Son 7 gün', '30d': 'Son 30 gün', 'this_month': 'Bu ay', 'last_month': 'Geçen ay'};
      parts.add(map[_preset] ?? _preset);
    }
    return parts.join(' · ');
  }
}

String _statusLabelTr(String key) => switch (key) {
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
      _ => key,
    };

class _KpiRow extends StatelessWidget {
  const _KpiRow({required this.a, required this.b, this.boldValue = false});

  final String a;
  final String b;
  final bool boldValue;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(child: Text(a, style: theme.textTheme.bodySmall)),
          const SizedBox(width: 12),
          Text(b, style: boldValue ? theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w900) : theme.textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.w600)),
        ],
      ),
    );
  }
}

class _PayCard extends StatelessWidget {
  const _PayCard({
    required this.label,
    required this.bg,
    required this.border,
    required this.count,
    required this.revenue,
  });

  final String label;
  final Color bg;
  final Color border;
  final int count;
  final double revenue;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return SizedBox(
      width: 160,
      child: DecoratedBox(
        decoration: BoxDecoration(
          color: bg,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: border.withValues(alpha: 0.65)),
        ),
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(label, style: theme.textTheme.labelSmall?.copyWith(fontWeight: FontWeight.bold, letterSpacing: 0.2)),
              const SizedBox(height: 6),
              Text(_fmt2(revenue), style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w900)),
              const SizedBox(height: 6),
              Text('$count teslim', style: theme.textTheme.bodySmall?.copyWith(color: theme.textTheme.bodySmall?.color?.withValues(alpha: 0.85))),
            ],
          ),
        ),
      ),
    );
  }
}

class _CourierCard extends StatelessWidget {
  const _CourierCard({required this.row});

  final Map<String, dynamic> row;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final net = _dbl(row['net']);
    final name = '${row['courier_name'] ?? '—'}';
    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: ExpansionTile(
        title: Text(name, style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w800)),
        subtitle: Text('Net: ${_fmt2(net)}'),
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
            child: Column(
              children: [
                _CourierLine(k: 'Teslim', v: '${_int(row['delivered_count'])}'),
                _CourierLine(k: 'Ciro', v: _fmt2(_dbl(row['revenue']))),
                _CourierLine(k: 'İndirim', v: _fmt2(_dbl(row['discounts']))),
                _CourierLine(k: 'Müşteri teslimatı', v: _fmt2(_dbl(row['delivery_fees']))),
                _CourierLine(k: 'Platform', v: _fmt2(_dbl(row['platform_fees']))),
                _CourierLine(k: 'İşl. paket', v: _fmt2(_dbl(row['restaurant_commission']))),
                _CourierLine(k: 'Kurye ödemesi', v: _fmt2(_dbl(row['courier_payout']))),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _CourierLine extends StatelessWidget {
  const _CourierLine({required this.k, required this.v});

  final String k;
  final String v;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Padding(
      padding: const EdgeInsets.only(bottom: 6),
      child: Row(
        children: [
          Expanded(child: Text(k, style: theme.textTheme.bodySmall)),
          Text(v, style: theme.textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.w600)),
        ],
      ),
    );
  }
}

class _SimpleQtyTable extends StatelessWidget {
  const _SimpleQtyTable({required this.theme, required this.rows, required this.nameKey});

  final ThemeData theme;
  final List<Map<String, dynamic>> rows;
  final String nameKey;

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.symmetric(vertical: 4),
          child: Row(
            children: [
              Expanded(flex: 4, child: Text('Ad', style: theme.textTheme.labelSmall)),
              Expanded(child: Text('Adet', textAlign: TextAlign.right, style: theme.textTheme.labelSmall)),
              Expanded(child: Text('Ciro', textAlign: TextAlign.right, style: theme.textTheme.labelSmall)),
            ],
          ),
        ),
        for (final r in rows.take(15))
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 6),
            child: Row(
              children: [
                Expanded(flex: 4, child: Text('${r[nameKey]}', style: theme.textTheme.bodyMedium)),
                Expanded(child: Text('${_int(r['qty'])}', textAlign: TextAlign.right)),
                Expanded(child: Text(_fmt2(_dbl(r['revenue'])), textAlign: TextAlign.right)),
              ],
            ),
          ),
      ],
    );
  }
}

class _MetricTile extends StatelessWidget {
  const _MetricTile({required this.label, required this.value, required this.delta});

  final String label;
  final String value;
  final String? delta;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(14),
        color: theme.colorScheme.surface.withValues(alpha: 0.65),
        border: Border.all(color: theme.dividerColor.withValues(alpha: 0.6)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: theme.textTheme.bodySmall),
          const SizedBox(height: 6),
          Row(
            children: [
              Text(value, style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w800)),
              const Spacer(),
              if (delta != null)
                Text(
                  delta!,
                  style: theme.textTheme.labelSmall?.copyWith(
                    fontWeight: FontWeight.w800,
                    color: theme.colorScheme.primary,
                  ),
                ),
            ],
          ),
        ],
      ),
    );
  }
}

class _MoneyTile extends StatelessWidget {
  const _MoneyTile({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(14),
        color: theme.colorScheme.surface.withValues(alpha: 0.65),
        border: Border.all(color: theme.dividerColor.withValues(alpha: 0.6)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: theme.textTheme.bodySmall),
          const SizedBox(height: 6),
          Text(value, style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w900)),
        ],
      ),
    );
  }
}

class _MiniBars extends StatelessWidget {
  const _MiniBars({required this.values});

  final List values;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final nums = values
        .map((e) => e is Map ? (e['c'] as num?) : null)
        .map((e) => e?.toDouble() ?? 0.0)
        .toList(growable: false);
    final maxV = nums.isEmpty ? 1.0 : math.max(1.0, nums.reduce(math.max));

    return Row(
      crossAxisAlignment: CrossAxisAlignment.end,
      children: [
        for (var i = 0; i < nums.length; i++)
          Expanded(
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 2),
              child: AnimatedContainer(
                duration: const Duration(milliseconds: 220),
                curve: Curves.easeOut,
                height: 16 + 104 * (nums[i] / maxV),
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(8),
                  color: theme.colorScheme.primary.withValues(alpha: 0.78),
                ),
              ),
            ),
          ),
      ],
    );
  }
}
