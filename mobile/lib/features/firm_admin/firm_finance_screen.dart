import 'package:flutter/material.dart';
import 'package:dio/dio.dart';
import 'package:kurye_mobile/core/app_scope.dart';
import 'package:kurye_mobile/core/ui/app_content.dart';
import 'package:kurye_mobile/core/ui/glass_card.dart';
import 'package:kurye_mobile/features/firm_admin/firm_admin_api.dart';

class FirmFinanceScreen extends StatefulWidget {
  const FirmFinanceScreen({super.key});

  @override
  State<FirmFinanceScreen> createState() => _FirmFinanceScreenState();
}

class _FirmFinanceScreenState extends State<FirmFinanceScreen> {
  String _preset = 'this_month';
  late Future<Map<String, dynamic>> _overviewFuture;
  late Future<Map<String, dynamic>> _balancesFuture;
  late Future<Map<String, dynamic>> _collectionsFuture;
  late Future<Map<String, dynamic>> _payoutsFuture;
  late Future<Map<String, dynamic>> _reconciliationFuture;
  late Future<Map<String, dynamic>> _restaurantsFuture;
  late Future<Map<String, dynamic>> _couriersFuture;
  bool _bootstrapped = false;
  final List<bool> _expanded = [true, false, false, false, false];
  int? _balancesRestaurantId;
  DateTimeRange? _balancesRange;
  int? _collectionsCourierId;
  DateTimeRange? _collectionsRange;
  int? _payoutCourierId;
  String _reconciliationMonth = '';

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (_bootstrapped) return;
    _bootstrapped = true;
    final now = DateTime.now();
    _reconciliationMonth = '${now.year.toString().padLeft(4, '0')}-${now.month.toString().padLeft(2, '0')}';
    _reloadAll();
  }

  FirmAdminApi _api() => FirmAdminApi(AppScope.of(context).api);

  void _reloadAll() {
    final api = _api();
    _overviewFuture = api.financeOverview(preset: _preset);
    _balancesFuture = api.financeBalances(
      dateFrom: _balancesRange != null ? _fmtDate(_balancesRange!.start) : null,
      dateTo: _balancesRange != null ? _fmtDate(_balancesRange!.end) : null,
      restaurantId: _balancesRestaurantId,
    );
    _collectionsFuture = api.financeCourierCollections(
      dateFrom: _collectionsRange != null ? _fmtDate(_collectionsRange!.start) : null,
      dateTo: _collectionsRange != null ? _fmtDate(_collectionsRange!.end) : null,
      courierId: _collectionsCourierId,
    );
    _payoutsFuture = api.financeCourierPayouts(courierId: _payoutCourierId);
    _reconciliationFuture = api.financeReconciliation(month: _reconciliationMonth);
    _restaurantsFuture = api.restaurants(page: 1);
    _couriersFuture = api.couriers(page: 1);
  }

  Future<void> _refresh() async {
    setState(_reloadAll);
    await Future.wait([
      _overviewFuture,
      _balancesFuture,
      _collectionsFuture,
      _payoutsFuture,
      _reconciliationFuture,
    ]);
  }

  String _fmtMoney(num v) => '${v.toDouble().toStringAsFixed(2)} ₺';

  String _fmtDate(DateTime d) =>
      '${d.year.toString().padLeft(4, '0')}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';

  Future<DateTimeRange?> _pickRange() async {
    final now = DateTime.now();
    return showDateRangePicker(
      context: context,
      firstDate: DateTime(now.year - 2),
      lastDate: DateTime(now.year + 1),
      initialDateRange: DateTimeRange(start: now.subtract(const Duration(days: 7)), end: now),
      locale: const Locale('tr', 'TR'),
    );
  }

  Future<void> _openCreatePayout() async {
    final formKey = GlobalKey<FormState>();
    int? courierId;
    var method = 'cash';
    var preset = 'week';
    final ref = TextEditingController();
    final notes = TextEditingController();
    DateTimeRange? range;
    var includeAllLedger = false;
    var ordersAllTime = false;
    var saving = false;

    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      showDragHandle: true,
      builder: (ctx) {
        return StatefulBuilder(builder: (ctx, setSheet) {
          final inset = MediaQuery.of(ctx).viewInsets.bottom;
          Future<void> pickRange() async {
            final now = DateTime.now();
            final r = await showDateRangePicker(
              context: ctx,
              firstDate: DateTime(now.year - 2),
              lastDate: DateTime(now.year + 1),
              initialDateRange: DateTimeRange(start: now.subtract(const Duration(days: 7)), end: now),
              locale: const Locale('tr', 'TR'),
            );
            if (r == null) return;
            setSheet(() => range = r);
          }

          Future<void> submit() async {
            if (!formKey.currentState!.validate()) return;
            final now = DateTime.now();
            if (!ordersAllTime && range == null && preset == 'custom') {
              ScaffoldMessenger.of(ctx).showSnackBar(const SnackBar(content: Text('Tarih aralığı seçin.')));
              return;
            }

            DateTime fromDate;
            DateTime toDate;
            if (ordersAllTime || preset == 'all') {
              fromDate = DateTime(2000, 1, 1);
              toDate = now;
            } else if (preset == 'today') {
              fromDate = DateTime(now.year, now.month, now.day);
              toDate = now;
            } else if (preset == 'week') {
              fromDate = now.subtract(const Duration(days: 6));
              toDate = now;
            } else if (preset == 'month') {
              fromDate = DateTime(now.year, now.month, 1);
              toDate = now;
            } else {
              fromDate = range!.start;
              toDate = range!.end;
            }

            setSheet(() => saving = true);
            try {
              final created = await _api().financeCourierPayoutCreate(
                courierId: courierId!,
                dateFrom: _fmtDate(fromDate),
                dateTo: _fmtDate(toDate),
                paymentMethod: method,
                paymentReference: ref.text.trim().isEmpty ? null : ref.text.trim(),
                notes: notes.text.trim().isEmpty ? null : notes.text.trim(),
                includeAllLedger: includeAllLedger,
                ordersAllTime: ordersAllTime || preset == 'all',
                periodPreset: ordersAllTime ? 'all' : preset,
              );
              final createdId = created['settlement_id'] is num ? (created['settlement_id'] as num).toInt() : null;
              if (!ctx.mounted) return;
              Navigator.pop(ctx);
              await _refresh();
              if (createdId != null) {
                await _showPayoutDetail(createdId);
              }
            } on DioException catch (e) {
              final data = e.response?.data;
              final msg = data is Map && data['message'] != null ? data['message'].toString() : 'Ödeme oluşturulamadı.';
              if (!ctx.mounted) return;
              ScaffoldMessenger.of(ctx).showSnackBar(SnackBar(content: Text(msg)));
            } finally {
              if (ctx.mounted) setSheet(() => saving = false);
            }
          }

          return Padding(
            padding: EdgeInsets.fromLTRB(16, 0, 16, inset + 16),
            child: FutureBuilder<Map<String, dynamic>>(
              future: _api().couriers(page: 1),
              builder: (context, snap) {
                final couriers = ((snap.data?['data'] as List?) ?? const []).map((e) => Map<String, dynamic>.from(e as Map)).toList();
                return Form(
                  key: formKey,
                  child: SingleChildScrollView(
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text('Yeni ödeme / kapat', style: Theme.of(ctx).textTheme.titleMedium),
                        const SizedBox(height: 12),
                        DropdownButtonFormField<int>(
                          initialValue: courierId,
                          decoration: const InputDecoration(labelText: 'Kurye'),
                          items: couriers
                              .map((m) => DropdownMenuItem<int>(value: (m['id'] as num).toInt(), child: Text('${m['name']}')))
                              .toList(),
                          onChanged: (v) => setSheet(() => courierId = v),
                          validator: (v) => v == null ? 'Kurye seçin' : null,
                        ),
                        const SizedBox(height: 10),
                        SwitchListTile(
                          value: ordersAllTime,
                          onChanged: (v) => setSheet(() => ordersAllTime = v),
                          title: const Text('Siparişler: Tüm zamanlar'),
                        ),
                        if (!ordersAllTime) ...[
                          DropdownButtonFormField<String>(
                            initialValue: preset,
                            decoration: const InputDecoration(labelText: 'Dönem'),
                            items: const [
                              DropdownMenuItem(value: 'today', child: Text('Bugün')),
                              DropdownMenuItem(value: 'week', child: Text('Bu hafta')),
                              DropdownMenuItem(value: 'month', child: Text('Bu ay')),
                              DropdownMenuItem(value: 'all', child: Text('Tümü')),
                              DropdownMenuItem(value: 'custom', child: Text('Özel tarih')),
                            ],
                            onChanged: (v) => setSheet(() => preset = v ?? 'week'),
                          ),
                          const SizedBox(height: 8),
                        ],
                        if (!ordersAllTime && preset == 'custom')
                          OutlinedButton(
                            onPressed: pickRange,
                            child: Text(range == null ? 'Tarih aralığı seç' : '${_fmtDate(range!.start)} - ${_fmtDate(range!.end)}'),
                          ),
                        SwitchListTile(
                          value: includeAllLedger,
                          onChanged: (v) => setSheet(() => includeAllLedger = v),
                          title: const Text('Tüm açık cari kalemleri dahil et'),
                        ),
                        DropdownButtonFormField<String>(
                          initialValue: method,
                          decoration: const InputDecoration(labelText: 'Ödeme yöntemi'),
                          items: const [
                            DropdownMenuItem(value: 'cash', child: Text('Nakit')),
                            DropdownMenuItem(value: 'bank_transfer', child: Text('Havale')),
                            DropdownMenuItem(value: 'eft', child: Text('EFT')),
                            DropdownMenuItem(value: 'check', child: Text('Çek')),
                            DropdownMenuItem(value: 'other', child: Text('Diğer')),
                          ],
                          onChanged: (v) => setSheet(() => method = v ?? 'cash'),
                        ),
                        const SizedBox(height: 8),
                        TextFormField(controller: ref, decoration: const InputDecoration(labelText: 'Referans (opsiyonel)')),
                        const SizedBox(height: 8),
                        TextFormField(controller: notes, decoration: const InputDecoration(labelText: 'Not (opsiyonel)')),
                        const SizedBox(height: 12),
                        SizedBox(
                          width: double.infinity,
                          child: FilledButton(
                            onPressed: saving ? null : submit,
                            child: Text(saving ? 'Kaydediliyor...' : 'Ödeme oluştur'),
                          ),
                        ),
                      ],
                    ),
                  ),
                );
              },
            ),
          );
        });
      },
    );
  }

  Future<void> _voidPayout(int settlementId) async {
    await _api().financeCourierPayoutVoid(settlementId);
    await _refresh();
  }

  Future<void> _showPayoutDetail(int settlementId) async {
    final d = await _api().financeCourierPayoutDetail(settlementId);
    if (!mounted) return;
    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      showDragHandle: true,
      builder: (ctx) {
        final orders = (d['orders'] as List?) ?? const [];
        return Padding(
          padding: const EdgeInsets.all(16),
          child: ListView(
            shrinkWrap: true,
            children: [
              Text('Ödeme #${d['id']}', style: Theme.of(ctx).textTheme.titleLarge),
              Text('Kurye: ${d['courier'] is Map ? d['courier']['name'] : '—'}'),
              Text('Net: ${_fmtMoney((d['net_paid'] as num?) ?? 0)}'),
              const SizedBox(height: 8),
              Text('Siparişler (${orders.length})'),
              ...orders.map((e) {
                final m = Map<String, dynamic>.from(e as Map);
                return ListTile(
                  dense: true,
                  contentPadding: EdgeInsets.zero,
                  title: Text('#${m['id']}'),
                  trailing: Text(_fmtMoney((m['courier_payout_amount'] as num?) ?? 0)),
                );
              }),
            ],
          ),
        );
      },
    );
  }

  Future<void> _showCourierCollectionDetail(int courierId) async {
    final d = await _api().financeCourierCollectionDetail(
      courierId: courierId,
      dateFrom: _collectionsRange != null ? _fmtDate(_collectionsRange!.start) : null,
      dateTo: _collectionsRange != null ? _fmtDate(_collectionsRange!.end) : null,
    );
    if (!mounted) return;
    final courier = Map<String, dynamic>.from((d['courier'] as Map?) ?? {});
    final orders = Map<String, dynamic>.from((d['orders'] as Map?) ?? {});
    final ledger = Map<String, dynamic>.from((d['ledger'] as Map?) ?? {});
    final settlements = Map<String, dynamic>.from((d['settlements'] as Map?) ?? {});
    final balance = Map<String, dynamic>.from((d['balance'] as Map?) ?? {});

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
              Text('Kurye detay: ${courier['name'] ?? '—'}', style: Theme.of(ctx).textTheme.titleLarge),
              const SizedBox(height: 8),
              _DetailRow(label: 'Teslim adet', value: '${orders['count'] ?? 0}'),
              _DetailRow(label: 'Sipariş cirosu', value: _fmtMoney((orders['revenue_total'] as num?) ?? 0)),
              _DetailRow(label: 'Hakediş (sipariş)', value: _fmtMoney((orders['payout_total'] as num?) ?? 0)),
              const Divider(),
              _DetailRow(label: 'Aldıkları (ödenen)', value: _fmtMoney((settlements['paid_total'] as num?) ?? 0)),
              _DetailRow(label: 'Verdikleri (avans)', value: _fmtMoney((ledger['advance_total'] as num?) ?? 0)),
              _DetailRow(label: 'Giderleri (kesinti)', value: _fmtMoney((ledger['expense_total'] as num?) ?? 0)),
              _DetailRow(label: 'Gelirleri (prim)', value: _fmtMoney((ledger['credit_total'] as num?) ?? 0)),
              const Divider(),
              _DetailRow(label: 'Açık avans', value: _fmtMoney((ledger['open_advance'] as num?) ?? 0)),
              _DetailRow(label: 'Açık kesinti', value: _fmtMoney((ledger['open_expense'] as num?) ?? 0)),
              _DetailRow(label: 'Açık prim', value: _fmtMoney((ledger['open_credit'] as num?) ?? 0)),
              const Divider(),
              _DetailRow(label: 'Kurye alacağı', value: _fmtMoney((balance['courier_receivable'] as num?) ?? 0), bold: true),
              _DetailRow(label: 'Kurye vereceği', value: _fmtMoney((balance['courier_payable'] as num?) ?? 0), bold: true),
              _DetailRow(label: 'Net bakiye', value: _fmtMoney((balance['net'] as num?) ?? 0), bold: true),
            ],
          ),
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Finans'),
        actions: [
          PopupMenuButton<String>(
            initialValue: _preset,
            onSelected: (v) {
              setState(() {
                _preset = v;
                _reloadAll();
              });
            },
            itemBuilder: (context) => const [
              PopupMenuItem(value: 'today', child: Text('Bugün')),
              PopupMenuItem(value: '7d', child: Text('Son 7 gün')),
              PopupMenuItem(value: '30d', child: Text('Son 30 gün')),
              PopupMenuItem(value: 'this_month', child: Text('Bu ay')),
              PopupMenuItem(value: 'last_month', child: Text('Geçen ay')),
            ],
          ),
          IconButton(onPressed: _refresh, icon: const Icon(Icons.refresh)),
        ],
      ),
      body: AppContent(
        child: RefreshIndicator(
          onRefresh: _refresh,
          child: ListView(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
            children: [
              _FinanceAccordion(
                title: 'Genel durum',
                expanded: _expanded[0],
                onToggle: (v) => setState(() => _expanded[0] = v),
                child: _OverviewModule(future: _overviewFuture, fmtMoney: _fmtMoney),
              ),
              const SizedBox(height: 10),
              _FinanceAccordion(
                title: 'Borç / alacak listesi',
                expanded: _expanded[1],
                onToggle: (v) => setState(() => _expanded[1] = v),
                child: Column(
                  children: [
                    FutureBuilder<Map<String, dynamic>>(
                      future: _restaurantsFuture,
                      builder: (context, snap) {
                        final items = ((snap.data?['data'] as List?) ?? const [])
                            .map((e) => Map<String, dynamic>.from(e as Map))
                            .toList();
                        return Row(
                          children: [
                            Expanded(
                              child: DropdownButtonFormField<int?>(
                                initialValue: _balancesRestaurantId,
                                decoration: const InputDecoration(labelText: 'İşletme'),
                                items: [
                                  const DropdownMenuItem<int?>(value: null, child: Text('Tümü')),
                                  ...items.map((m) => DropdownMenuItem<int?>(
                                        value: (m['id'] as num).toInt(),
                                        child: Text('${m['name']}'),
                                      )),
                                ],
                                onChanged: (v) => _balancesRestaurantId = v,
                              ),
                            ),
                            const SizedBox(width: 8),
                            OutlinedButton(
                              onPressed: () async {
                                final r = await _pickRange();
                                if (r == null) return;
                                setState(() => _balancesRange = r);
                              },
                              child: Text(_balancesRange == null
                                  ? 'Tarih'
                                  : '${_fmtDate(_balancesRange!.start)} - ${_fmtDate(_balancesRange!.end)}'),
                            ),
                            const SizedBox(width: 8),
                            FilledButton(
                              onPressed: () => setState(() {
                                _balancesFuture = _api().financeBalances(
                                  dateFrom: _balancesRange != null ? _fmtDate(_balancesRange!.start) : null,
                                  dateTo: _balancesRange != null ? _fmtDate(_balancesRange!.end) : null,
                                  restaurantId: _balancesRestaurantId,
                                );
                              }),
                              child: const Text('Uygula'),
                            ),
                            const SizedBox(width: 6),
                            TextButton(
                              onPressed: () => setState(() {
                                _balancesRestaurantId = null;
                                _balancesRange = null;
                                _balancesFuture = _api().financeBalances();
                              }),
                              child: const Text('Sıfırla'),
                            ),
                          ],
                        );
                      },
                    ),
                    const SizedBox(height: 10),
                    _BalancesModule(future: _balancesFuture, fmtMoney: _fmtMoney),
                  ],
                ),
              ),
              const SizedBox(height: 10),
              _FinanceAccordion(
                title: 'Kurye ücret özeti',
                expanded: _expanded[2],
                onToggle: (v) => setState(() => _expanded[2] = v),
                child: Column(
                  children: [
                    FutureBuilder<Map<String, dynamic>>(
                      future: _couriersFuture,
                      builder: (context, snap) {
                        final items = ((snap.data?['data'] as List?) ?? const [])
                            .map((e) => Map<String, dynamic>.from(e as Map))
                            .toList();
                        return Row(
                          children: [
                            Expanded(
                              child: DropdownButtonFormField<int?>(
                                initialValue: _collectionsCourierId,
                                decoration: const InputDecoration(labelText: 'Kurye'),
                                items: [
                                  const DropdownMenuItem<int?>(value: null, child: Text('Tümü')),
                                  ...items.map((m) => DropdownMenuItem<int?>(
                                        value: (m['id'] as num).toInt(),
                                        child: Text('${m['name']}'),
                                      )),
                                ],
                                onChanged: (v) => _collectionsCourierId = v,
                              ),
                            ),
                            const SizedBox(width: 8),
                            OutlinedButton(
                              onPressed: () async {
                                final r = await _pickRange();
                                if (r == null) return;
                                setState(() => _collectionsRange = r);
                              },
                              child: Text(_collectionsRange == null
                                  ? 'Tarih'
                                  : '${_fmtDate(_collectionsRange!.start)} - ${_fmtDate(_collectionsRange!.end)}'),
                            ),
                            const SizedBox(width: 8),
                            FilledButton(
                              onPressed: () => setState(() {
                                _collectionsFuture = _api().financeCourierCollections(
                                  dateFrom: _collectionsRange != null ? _fmtDate(_collectionsRange!.start) : null,
                                  dateTo: _collectionsRange != null ? _fmtDate(_collectionsRange!.end) : null,
                                  courierId: _collectionsCourierId,
                                );
                              }),
                              child: const Text('Uygula'),
                            ),
                            const SizedBox(width: 6),
                            TextButton(
                              onPressed: () => setState(() {
                                _collectionsCourierId = null;
                                _collectionsRange = null;
                                _collectionsFuture = _api().financeCourierCollections();
                              }),
                              child: const Text('Sıfırla'),
                            ),
                          ],
                        );
                      },
                    ),
                    const SizedBox(height: 10),
                    _CollectionsModule(
                      future: _collectionsFuture,
                      fmtMoney: _fmtMoney,
                      onDetail: _showCourierCollectionDetail,
                    ),
                    const SizedBox(height: 8),
                    Align(
                      alignment: Alignment.centerLeft,
                      child: Text('Satırdaki göz ikonundan kurye detay popup açılır.',
                          style: Theme.of(context).textTheme.bodySmall),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 10),
              _FinanceAccordion(
                title: 'Kurye ödemeleri',
                expanded: _expanded[3],
                onToggle: (v) => setState(() => _expanded[3] = v),
                child: _PayoutsModule(
                  future: _payoutsFuture,
                  fmtMoney: _fmtMoney,
                  onCreate: _openCreatePayout,
                  onDetail: _showPayoutDetail,
                  onVoid: _voidPayout,
                  filterBar: FutureBuilder<Map<String, dynamic>>(
                    future: _couriersFuture,
                    builder: (context, snap) {
                      final items = ((snap.data?['data'] as List?) ?? const [])
                          .map((e) => Map<String, dynamic>.from(e as Map))
                          .toList();
                      return Row(
                        children: [
                          Expanded(
                            child: DropdownButtonFormField<int?>(
                              initialValue: _payoutCourierId,
                              decoration: const InputDecoration(labelText: 'Kurye'),
                              items: [
                                const DropdownMenuItem<int?>(value: null, child: Text('Tümü')),
                                ...items.map((m) => DropdownMenuItem<int?>(
                                      value: (m['id'] as num).toInt(),
                                      child: Text('${m['name']}'),
                                    )),
                              ],
                              onChanged: (v) => _payoutCourierId = v,
                            ),
                          ),
                          const SizedBox(width: 8),
                          FilledButton(
                            onPressed: () => setState(() {
                              _payoutsFuture = _api().financeCourierPayouts(courierId: _payoutCourierId);
                            }),
                            child: const Text('Filtrele'),
                          ),
                          const SizedBox(width: 6),
                          TextButton(
                            onPressed: () => setState(() {
                              _payoutCourierId = null;
                              _payoutsFuture = _api().financeCourierPayouts();
                            }),
                            child: const Text('Sıfırla'),
                          ),
                        ],
                      );
                    },
                  ),
                ),
              ),
              const SizedBox(height: 10),
              _FinanceAccordion(
                title: 'Mutabakat',
                expanded: _expanded[4],
                onToggle: (v) => setState(() => _expanded[4] = v),
                child: Column(
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: TextFormField(
                            initialValue: _reconciliationMonth,
                            decoration: const InputDecoration(labelText: 'Ay (YYYY-MM)'),
                            onChanged: (v) => _reconciliationMonth = v,
                          ),
                        ),
                        const SizedBox(width: 8),
                        FilledButton(
                          onPressed: () => setState(() {
                            _reconciliationFuture = _api().financeReconciliation(month: _reconciliationMonth);
                          }),
                          child: const Text('Göster'),
                        ),
                        const SizedBox(width: 6),
                        TextButton(
                          onPressed: () => setState(() {
                            final now = DateTime.now();
                            _reconciliationMonth =
                                '${now.year.toString().padLeft(4, '0')}-${now.month.toString().padLeft(2, '0')}';
                            _reconciliationFuture = _api().financeReconciliation(month: _reconciliationMonth);
                          }),
                          child: const Text('Sıfırla'),
                        ),
                      ],
                    ),
                    const SizedBox(height: 10),
                    _ReconciliationModule(future: _reconciliationFuture, fmtMoney: _fmtMoney),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _FinanceAccordion extends StatelessWidget {
  const _FinanceAccordion({
    required this.title,
    required this.expanded,
    required this.onToggle,
    required this.child,
  });

  final String title;
  final bool expanded;
  final ValueChanged<bool> onToggle;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    return GlassCard(
      padding: EdgeInsets.zero,
      child: ExpansionTile(
        initiallyExpanded: expanded,
        onExpansionChanged: onToggle,
        title: Text(title, style: Theme.of(context).textTheme.titleMedium),
        childrenPadding: const EdgeInsets.fromLTRB(14, 0, 14, 14),
        children: [child],
      ),
    );
  }
}

class _OverviewModule extends StatelessWidget {
  const _OverviewModule({required this.future, required this.fmtMoney});

  final Future<Map<String, dynamic>> future;
  final String Function(num) fmtMoney;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return FutureBuilder<Map<String, dynamic>>(
      future: future,
      builder: (context, snap) {
        if (!snap.hasData) return const Center(child: CircularProgressIndicator());
        final d = snap.data ?? const {};
        final delivered = (d['delivered_count'] as num?) ?? 0;
        final revenue = (d['revenue_total'] as num?) ?? 0;
        final platformFees = (d['platform_fees_total'] as num?) ?? 0;
        final restaurantCommission = (d['restaurant_commission_total'] as num?) ?? 0;
        final courierPayout = (d['courier_payout_total'] as num?) ?? 0;

        return GlassCard(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('Genel durum', style: theme.textTheme.titleMedium),
              const SizedBox(height: 10),
              Row(
                children: [
                  Expanded(child: _Tile(label: 'Teslim', value: '${delivered.toInt()}')),
                  const SizedBox(width: 10),
                  Expanded(child: _Tile(label: 'Ciro', value: fmtMoney(revenue))),
                ],
              ),
              const SizedBox(height: 10),
              Row(
                children: [
                  Expanded(child: _Tile(label: 'Platform', value: fmtMoney(platformFees))),
                  const SizedBox(width: 10),
                  Expanded(child: _Tile(label: 'İşletme', value: fmtMoney(restaurantCommission))),
                ],
              ),
              const SizedBox(height: 10),
              _Tile(label: 'Kurye ödemeleri', value: fmtMoney(courierPayout)),
            ],
          ),
        );
      },
    );
  }
}

class _BalancesModule extends StatelessWidget {
  const _BalancesModule({required this.future, required this.fmtMoney});
  final Future<Map<String, dynamic>> future;
  final String Function(num) fmtMoney;
  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return FutureBuilder<Map<String, dynamic>>(
      future: future,
      builder: (context, snap) {
        if (!snap.hasData) return const Center(child: CircularProgressIndicator());
        final d = snap.data ?? const {};
        final rows = (d['rows'] as List?) ?? const [];
        return GlassCard(
          padding: const EdgeInsets.all(16),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text('Borç / alacak listesi', style: theme.textTheme.titleMedium),
            const SizedBox(height: 8),
            Text('Platform: ${fmtMoney((d['platform_fees_total'] as num?) ?? 0)}'),
            Text('İşletme paket: ${fmtMoney((d['restaurant_commission_total'] as num?) ?? 0)}'),
            const Divider(),
            ...rows.map((e) {
              final m = Map<String, dynamic>.from(e as Map);
              return ListTile(
                dense: true,
                contentPadding: EdgeInsets.zero,
                title: Text('${m['restaurant_name'] ?? '—'}'),
                subtitle: Text('${m['order_count'] ?? 0} sipariş'),
                trailing: Text(fmtMoney((m['commission_total'] as num?) ?? 0)),
              );
            }),
          ]),
        );
      },
    );
  }
}

class _CollectionsModule extends StatelessWidget {
  const _CollectionsModule({
    required this.future,
    required this.fmtMoney,
    required this.onDetail,
  });
  final Future<Map<String, dynamic>> future;
  final String Function(num) fmtMoney;
  final Future<void> Function(int courierId) onDetail;
  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return FutureBuilder<Map<String, dynamic>>(
      future: future,
      builder: (context, snap) {
        if (!snap.hasData) return const Center(child: CircularProgressIndicator());
        final d = snap.data ?? const {};
        final rows = (d['rows'] as List?) ?? const [];
        return GlassCard(
          padding: const EdgeInsets.all(16),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text('Kurye ücret özeti', style: theme.textTheme.titleMedium),
            const SizedBox(height: 8),
            Text('Toplam kurye ücreti: ${fmtMoney((d['payout_grand'] as num?) ?? 0)}'),
            const Divider(),
            ...rows.map((e) {
              final m = Map<String, dynamic>.from(e as Map);
              final courierId = (m['courier_id'] as num?)?.toInt();
              return ListTile(
                dense: true,
                contentPadding: EdgeInsets.zero,
                title: Text('${m['courier_name'] ?? '—'}'),
                subtitle: Text('${m['order_count'] ?? 0} teslim'),
                trailing: Wrap(
                  spacing: 6,
                  children: [
                    if (courierId != null)
                      IconButton(
                        onPressed: () => onDetail(courierId),
                        icon: const Icon(Icons.visibility_outlined),
                        tooltip: 'Detay',
                      ),
                    Text(fmtMoney((m['payout_total'] as num?) ?? 0)),
                  ],
                ),
              );
            }),
          ]),
        );
      },
    );
  }
}

class _PayoutsModule extends StatelessWidget {
  const _PayoutsModule({
    required this.future,
    required this.fmtMoney,
    required this.onCreate,
    required this.onDetail,
    required this.onVoid,
    this.filterBar,
  });
  final Future<Map<String, dynamic>> future;
  final String Function(num) fmtMoney;
  final VoidCallback onCreate;
  final Future<void> Function(int settlementId) onDetail;
  final Future<void> Function(int settlementId) onVoid;
  final Widget? filterBar;
  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return FutureBuilder<Map<String, dynamic>>(
      future: future,
      builder: (context, snap) {
        if (!snap.hasData) return const Center(child: CircularProgressIndicator());
        final d = snap.data ?? const {};
        final rows = (d['data'] as List?) ?? const [];
        return GlassCard(
          padding: const EdgeInsets.all(16),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Row(
              children: [
                Text('Kurye ödemeleri', style: theme.textTheme.titleMedium),
                const Spacer(),
                FilledButton.icon(
                  onPressed: onCreate,
                  icon: const Icon(Icons.add, size: 18),
                  label: const Text('Yeni ödeme'),
                ),
              ],
            ),
            const SizedBox(height: 8),
            if (filterBar != null) ...[
              filterBar!,
              const SizedBox(height: 8),
            ],
            ...rows.map((e) {
              final m = Map<String, dynamic>.from(e as Map);
              final status = (m['status'] ?? '').toString();
              final id = (m['id'] as num).toInt();
              return ListTile(
                dense: true,
                contentPadding: EdgeInsets.zero,
                title: Text('#${m['id']}  ${m['courier_name'] ?? '—'}'),
                subtitle: Text('${m['period_start'] ?? ''} → ${m['period_end'] ?? ''}  ·  ${status == 'voided' ? 'İptal' : 'Geçerli'}'),
                trailing: Wrap(
                  spacing: 6,
                  children: [
                    IconButton(
                      onPressed: () => onDetail(id),
                      icon: const Icon(Icons.visibility_outlined),
                      tooltip: 'Detay',
                    ),
                    if (status != 'voided')
                      IconButton(
                        onPressed: () => onVoid(id),
                        icon: const Icon(Icons.block_outlined),
                        tooltip: 'İptal et',
                      ),
                    Text(fmtMoney((m['net_paid'] as num?) ?? 0)),
                  ],
                ),
              );
            }),
          ]),
        );
      },
    );
  }
}

class _ReconciliationModule extends StatelessWidget {
  const _ReconciliationModule({required this.future, required this.fmtMoney});
  final Future<Map<String, dynamic>> future;
  final String Function(num) fmtMoney;
  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return FutureBuilder<Map<String, dynamic>>(
      future: future,
      builder: (context, snap) {
        if (!snap.hasData) return const Center(child: CircularProgressIndicator());
        final d = snap.data ?? const {};
        final daily = (d['daily'] as List?) ?? const [];
        return GlassCard(
          padding: const EdgeInsets.all(16),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text('Mutabakat (${d['month'] ?? ''})', style: theme.textTheme.titleMedium),
            const SizedBox(height: 8),
            Text('Teslim: ${d['delivered_count'] ?? 0}'),
            Text('Ciro: ${fmtMoney((d['revenue_total'] as num?) ?? 0)}'),
            const Divider(),
            ...daily.map((e) {
              final m = Map<String, dynamic>.from(e as Map);
              return ListTile(
                dense: true,
                contentPadding: EdgeInsets.zero,
                title: Text('${m['date']}'),
                subtitle: Text('${m['count']} teslim'),
                trailing: Text(fmtMoney((m['revenue'] as num?) ?? 0)),
              );
            }),
          ]),
        );
      },
    );
  }
}

class _Tile extends StatelessWidget {
  const _Tile({required this.label, required this.value});

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

class _DetailRow extends StatelessWidget {
  const _DetailRow({required this.label, required this.value, this.bold = false});

  final String label;
  final String value;
  final bool bold;

  @override
  Widget build(BuildContext context) {
    final style = bold
        ? Theme.of(context).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w900)
        : Theme.of(context).textTheme.bodyMedium;
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 3),
      child: Row(
        children: [
          Expanded(child: Text(label)),
          Text(value, style: style),
        ],
      ),
    );
  }
}

