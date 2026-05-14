import 'package:flutter/material.dart';
import 'package:kurye_mobile/core/app_scope.dart';
import 'package:kurye_mobile/core/ui/app_background.dart';
import 'package:kurye_mobile/core/ui/app_content.dart';
import 'package:kurye_mobile/core/ui/glass_card.dart';
import 'package:kurye_mobile/features/courier/courier_stats_api.dart';

class CourierEarningsScreen extends StatefulWidget {
  const CourierEarningsScreen({super.key});

  @override
  State<CourierEarningsScreen> createState() => _CourierEarningsScreenState();
}

class _CourierEarningsScreenState extends State<CourierEarningsScreen> {
  Future<Map<String, dynamic>>? _future;
  String _period = 'today'; // today | week | month

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    _future ??= _load();
  }

  Future<Map<String, dynamic>> _load() async {
    final api = CourierStatsApi(AppScope.of(context).api);
    final e = await api.earnings(period: _period);
    final p = await api.payoutBalance();
    return {'earnings': e, 'payout': p};
  }

  String _fmtMoney(num? v) => v == null ? '—' : '₺${v.toStringAsFixed(2)}';

  String _paymentMethodTr(String? m) {
    switch (m) {
      case 'cash':
        return 'Nakit';
      case 'bank_transfer':
        return 'Havale';
      case 'eft':
        return 'EFT';
      case 'check':
        return 'Çek';
      case 'other':
        return 'Diğer';
      default:
        return (m == null || m.isEmpty) ? '—' : m;
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      backgroundColor: Colors.transparent,
      appBar: AppBar(title: const Text('Kazanç')),
      body: AppBackground(
        child: AppContent(
          child: FutureBuilder<Map<String, dynamic>>(
            future: _future,
            builder: (context, snap) {
              if (snap.connectionState != ConnectionState.done) {
                return const Center(child: CircularProgressIndicator());
              }
              if (snap.hasError) {
                return Center(
                  child: Padding(
                    padding: const EdgeInsets.all(24),
                    child: Text(
                      'Kazanç bilgileri yüklenemedi.',
                      style: theme.textTheme.bodyLarge?.copyWith(color: theme.colorScheme.error),
                      textAlign: TextAlign.center,
                    ),
                  ),
                );
              }
              final data = snap.data!;
              final e = data['earnings'] as Map<String, dynamic>?;
              final p = data['payout'] as Map<String, dynamic>?;
              final eOk = e != null && e['ok'] == true;
              final pOk = p != null && p['ok'] == true;

              final payout = eOk ? (e['payout_total'] as num?)?.toDouble() : null;
              final revenue = eOk ? (e['revenue_total'] as num?)?.toDouble() : null;
              final delivered = eOk ? (e['delivered_count'] as int?) : null;
              final label = eOk ? ((e['period_label'] ?? '—').toString()) : '—';
              final changePct = eOk ? (e['change_pct'] as num?)?.toDouble() : null;

              final recNet = pOk ? (p['receivable_net'] as num?)?.toDouble() : null;
              final recOrd = pOk ? (p['receivable_from_unpaid_orders'] as num?)?.toDouble() : null;
              final ledD = pOk ? (p['open_ledger_deductions'] as num?)?.toDouble() : null;
              final ledC = pOk ? (p['open_ledger_credits'] as num?)?.toDouble() : null;
              final totalPaid = pOk ? (p['total_paid_settlements'] as num?)?.toDouble() : null;
              final recent = pOk ? (p['recent_payments'] as List<dynamic>?) : null;

              return RefreshIndicator(
                onRefresh: () async {
                  setState(() {
                    _future = _load();
                  });
                  await _future;
                },
                child: ListView(
                  padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
                  children: [
                  GlassCard(
                    padding: const EdgeInsets.all(14),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('Kazancım', style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w900)),
                        const SizedBox(height: 10),
                        _PeriodSegments(
                          value: _period,
                          onChanged: (v) {
                            setState(() {
                              _period = v;
                              _future = _load();
                            });
                          },
                        ),
                        const SizedBox(height: 12),
                        Row(
                          children: [
                            Expanded(
                              child: Text(
                                label,
                                style: theme.textTheme.labelLarge?.copyWith(
                                  color: theme.textTheme.bodySmall?.color,
                                  fontWeight: FontWeight.w900,
                                ),
                              ),
                            ),
                            if (changePct != null)
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                                decoration: BoxDecoration(
                                  color: theme.colorScheme.primary.withValues(alpha: 0.18),
                                  borderRadius: BorderRadius.circular(999),
                                ),
                                child: Text(
                                  '${changePct >= 0 ? '+' : ''}${changePct.toStringAsFixed(0)}%',
                                  style: theme.textTheme.labelMedium?.copyWith(
                                    fontWeight: FontWeight.w900,
                                    color: theme.colorScheme.primary,
                                  ),
                                ),
                              ),
                          ],
                        ),
                        const SizedBox(height: 8),
                        Text(
                          _fmtMoney(payout),
                          style: theme.textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.w900),
                        ),
                        const SizedBox(height: 10),
                        SizedBox(
                          height: 110,
                          child: _EarningsBars(
                            color: theme.colorScheme.primary,
                            seed: (payout ?? 0).toDouble(),
                          ),
                        ),
                        const SizedBox(height: 12),
                        Row(
                          children: [
                            Expanded(child: _MiniStat(label: 'Sipariş', value: delivered?.toString() ?? '—')),
                            const SizedBox(width: 8),
                            Expanded(child: _MiniStat(label: 'Ciro', value: revenue == null ? '—' : _fmtMoney(revenue))),
                            const SizedBox(width: 8),
                            const Expanded(child: _MiniStat(label: 'Puan', value: '—')),
                          ],
                        ),
                        const SizedBox(height: 8),
                        Text(
                          'Ciro: teslim edilen siparişler, ücret: sistemdeki teslim ücreti.',
                          style: theme.textTheme.bodySmall,
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 12),
                  GlassCard(
                    child: Column(
                      children: [
                        _EarningsMenuTile(
                          icon: Icons.person_outline,
                          label: 'Profil bilgileri',
                          onTap: () => AppScope.of(context).shellNav.setTab(3),
                        ),
                        _menuDivider(context),
                        _EarningsMenuTile(
                          icon: Icons.local_shipping_outlined,
                          label: 'Araç bilgileri',
                          onTap: () => ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Yakında.'))),
                        ),
                        _menuDivider(context),
                        _EarningsMenuTile(
                          icon: Icons.notifications_outlined,
                          label: 'Bildirim ayarları',
                          onTap: () => ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Yakında.'))),
                        ),
                        _menuDivider(context),
                        _EarningsMenuTile(
                          icon: Icons.help_outline,
                          label: 'Yardım & destek',
                          onTap: () => ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Yakında.'))),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 12),
                  GlassCard(
                    padding: const EdgeInsets.all(14),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('Ödeme & alacak', style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w900)),
                        const SizedBox(height: 10),
                        if (pOk) ...[
                          _PayoutLine(
                            label: 'Bekleyen (net, tahsilat öncesi)',
                            value: _fmtMoney(recNet),
                            valueStyle: theme.textTheme.titleLarge?.copyWith(
                              fontWeight: FontWeight.w900,
                              color: recNet != null && recNet > 0 ? theme.colorScheme.primary : null,
                            ),
                            caption: 'Henüz kapanış yapılmamış sipariş hakedişi, açık avans/gider carisi düşülerek.',
                          ),
                          if ((recOrd ?? 0) > 0.005 || (ledD ?? 0) > 0.005 || (ledC ?? 0) > 0.005) ...[
                            const SizedBox(height: 8),
                            if ((recOrd ?? 0) > 0.005)
                              _PayoutLine(
                                label: 'Bekleyen sipariş hakedişi (ödenmemiş)',
                                value: _fmtMoney(recOrd),
                                dense: true,
                              ),
                            if ((ledD ?? 0) > 0.005) ...[
                              const SizedBox(height: 4),
                              _PayoutLine(
                                label: 'Açık cari (avans/ kesinti)',
                                value: '− ${_fmtMoney(ledD)}',
                                dense: true,
                              ),
                            ],
                            if ((ledC ?? 0) > 0.005) ...[
                              const SizedBox(height: 4),
                              _PayoutLine(
                                label: 'Açık cari (prim/ kredi)',
                                value: '+ ${_fmtMoney(ledC)}',
                                dense: true,
                              ),
                            ],
                          ],
                          const SizedBox(height: 12),
                          _PayoutLine(
                            label: 'Toplam size ödenen (firma kapanış kayıtları)',
                            value: _fmtMoney(totalPaid),
                            valueStyle: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w900),
                            caption: 'Onaylanmış ödeme / kapanış toplamı. İptal edilen hareketler hariç.',
                          ),
                        ] else
                          Text('Özet alınamadı.', style: theme.textTheme.bodySmall),
                      ],
                    ),
                  ),
                  const SizedBox(height: 12),
                  GlassCard(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Padding(
                          padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
                          child: Text(
                            'Son ödemeler (firma kapanışı)',
                            style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w900),
                          ),
                        ),
                        if (recent == null || recent.isEmpty)
                          Padding(
                            padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
                            child: Text('Henüz kapanış kaydı yok.', style: theme.textTheme.bodySmall),
                          )
                        else
                          ..._recentPaymentTiles(
                            context,
                            recent,
                            (n) => _fmtMoney(n),
                            (m) => _paymentMethodTr(m),
                          ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    'Bilgiler yönetim panelindeki hakediş ve kapanış kayıtları ile uyumludur.',
                    style: theme.textTheme.bodySmall,
                  ),
                  ],
                ),
              );
            },
          ),
        ),
      ),
    );
  }
}

Widget _menuDivider(BuildContext context) {
  return Divider(
    height: 1,
    color: Theme.of(context).dividerColor.withValues(alpha: 0.4),
  );
}

class _EarningsMenuTile extends StatelessWidget {
  const _EarningsMenuTile({
    required this.icon,
    required this.label,
    required this.onTap,
  });

  final IconData icon;
  final String label;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return ListTile(
      onTap: onTap,
      leading: Icon(icon, color: theme.colorScheme.primary),
      title: Text(label, style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w800)),
      trailing: Icon(
        Icons.chevron_right,
        size: 22,
        color: theme.textTheme.bodySmall?.color,
      ),
    );
  }
}

class _EarningsBars extends StatelessWidget {
  const _EarningsBars({required this.color, required this.seed});
  final Color color;
  final double seed;

  List<double> _values() {
    // deterministic pseudo-values (0..1), looks like chart without backend series
    final base = (seed.abs() % 1000) / 1000.0;
    final v = <double>[];
    for (var i = 0; i < 18; i++) {
      final t = (i / 17.0);
      final w = (0.35 + 0.55 * (t * (1 - t)) * 3.2);
      final s = (0.25 + 0.65 * ((base * 13.7 + i * 0.83) % 1.0));
      v.add((w * s).clamp(0.08, 1.0));
    }
    return v;
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    final vals = _values();
    return ClipRRect(
      borderRadius: BorderRadius.circular(16),
      child: CustomPaint(
        painter: _BarsPainter(
          values: vals,
          color: color,
          bg: isDark ? Colors.white.withValues(alpha: 0.06) : theme.colorScheme.surfaceContainerHighest.withValues(alpha: 0.40),
        ),
      ),
    );
  }
}

class _BarsPainter extends CustomPainter {
  _BarsPainter({required this.values, required this.color, required this.bg});
  final List<double> values;
  final Color color;
  final Color bg;

  @override
  void paint(Canvas canvas, Size size) {
    final rrect = RRect.fromRectAndRadius(Offset.zero & size, const Radius.circular(16));
    canvas.drawRRect(rrect, Paint()..color = bg);

    final barW = size.width / (values.length * 1.6);
    final gap = barW * 0.6;
    final maxH = size.height * 0.88;
    var x = barW;

    final p = Paint()..color = color.withValues(alpha: 0.95);
    for (final v in values) {
      final h = (v * maxH).clamp(6.0, maxH);
      final rect = Rect.fromLTWH(x, size.height - h - 10, barW, h);
      canvas.drawRRect(RRect.fromRectAndRadius(rect, const Radius.circular(6)), p);
      x += barW + gap;
      if (x > size.width) break;
    }
  }

  @override
  bool shouldRepaint(covariant _BarsPainter oldDelegate) {
    return oldDelegate.values != values || oldDelegate.color != color || oldDelegate.bg != bg;
  }
}

class _PeriodSegments extends StatelessWidget {
  const _PeriodSegments({required this.value, required this.onChanged});

  final String value;
  final ValueChanged<String> onChanged;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return SegmentedButton<String>(
      segments: const [
        ButtonSegment(value: 'today', label: Text('Günlük')),
        ButtonSegment(value: 'week', label: Text('Haftalık')),
        ButtonSegment(value: 'month', label: Text('Aylık')),
      ],
      selected: {value},
      showSelectedIcon: false,
      onSelectionChanged: (s) => onChanged(s.first),
      style: ButtonStyle(
        backgroundColor: WidgetStateProperty.resolveWith((states) {
          final isSelected = states.contains(WidgetState.selected);
          if (isSelected) return theme.colorScheme.primary.withValues(alpha: 0.22);
          return theme.colorScheme.surfaceContainerHighest.withValues(alpha: theme.brightness == Brightness.dark ? 0.14 : 0.35);
        }),
        foregroundColor: WidgetStateProperty.resolveWith((states) {
          final isSelected = states.contains(WidgetState.selected);
          return isSelected ? theme.colorScheme.primary : theme.textTheme.bodySmall?.color;
        }),
        textStyle: WidgetStatePropertyAll(theme.textTheme.labelLarge?.copyWith(fontWeight: FontWeight.w800)),
        shape: WidgetStatePropertyAll(RoundedRectangleBorder(borderRadius: BorderRadius.circular(14))),
        padding: const WidgetStatePropertyAll(EdgeInsets.symmetric(horizontal: 12, vertical: 10)),
      ),
    );
  }
}

class _PayoutLine extends StatelessWidget {
  const _PayoutLine({
    required this.label,
    required this.value,
    this.caption,
    this.valueStyle,
    this.dense = false,
  });

  final String label;
  final String value;
  final String? caption;
  final TextStyle? valueStyle;
  final bool dense;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: (dense ? theme.textTheme.labelSmall : theme.textTheme.labelMedium)?.copyWith(
            color: scheme.onSurfaceVariant,
            fontWeight: FontWeight.w600,
          ),
        ),
        const SizedBox(height: 2),
        Text(
          value,
          style: valueStyle ?? theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w800),
        ),
        if (caption != null) ...[
          const SizedBox(height: 2),
          Text(
            caption!,
            style: theme.textTheme.labelSmall?.copyWith(
              color: scheme.onSurfaceVariant,
              height: 1.2,
            ),
          ),
        ],
      ],
    );
  }
}

class _MiniStat extends StatelessWidget {
  const _MiniStat({required this.label, required this.value});
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: theme.colorScheme.surfaceContainerHighest.withValues(alpha: 0.55),
        borderRadius: BorderRadius.circular(14),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: theme.textTheme.labelMedium),
          const SizedBox(height: 6),
          Text(value, style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w900)),
        ],
      ),
    );
  }
}

List<Widget> _recentPaymentTiles(
  BuildContext context,
  List<dynamic> recent,
  String Function(num? v) fmtMoney,
  String Function(String? m) methodTr,
) {
  final out = <Widget>[];
  for (var i = 0; i < recent.length; i++) {
    if (i > 0) {
      out.add(const Divider(height: 1));
    }
    final item = Map<String, dynamic>.from(recent[i] as Map);
    final net = (item['net_paid'] as num?)?.toDouble();
    final at = (item['created_at'] as String?) ?? '';
    final dateShort = at.length >= 10 ? at.substring(0, 10) : at;
    final ps = (item['period_start'] as String?) ?? '';
    final pe = (item['period_end'] as String?) ?? '';
    out.add(
      ListTile(
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
        title: Text(
          fmtMoney(net),
          style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 16),
        ),
        subtitle: Text(
          [
            if (dateShort.isNotEmpty) dateShort,
            if (ps.isNotEmpty && pe.isNotEmpty) 'Dönem: $ps — $pe',
            methodTr(item['payment_method'] as String?),
          ].where((s) => s.isNotEmpty).join(' · '),
          maxLines: 2,
          overflow: TextOverflow.ellipsis,
          style: Theme.of(context).textTheme.bodySmall,
        ),
      ),
    );
  }
  return out;
}
