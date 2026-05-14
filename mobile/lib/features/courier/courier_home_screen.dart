import 'package:flutter/material.dart';
import 'package:kurye_mobile/core/app_scope.dart';
import 'package:kurye_mobile/core/ui/app_background.dart';
import 'package:kurye_mobile/core/ui/app_content.dart';
import 'package:kurye_mobile/core/ui/glass_card.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:kurye_mobile/features/courier/courier_api.dart';
import 'package:kurye_mobile/features/courier/courier_orders_screen.dart';
import 'package:kurye_mobile/features/courier/courier_stats_api.dart';
import 'package:kurye_mobile/screens/map_screen.dart';

class CourierHomeScreen extends StatefulWidget {
  const CourierHomeScreen({super.key, this.onJumpToTab});

  /// Alt menü sekmesine geç (0: ana, 1: kazanç, 2: profil).
  final void Function(int index)? onJumpToTab;

  @override
  State<CourierHomeScreen> createState() => _CourierHomeScreenState();
}

class _CourierHomeScreenState extends State<CourierHomeScreen> {
  static const _workingKey = 'courier_working';
  static const _storage = FlutterSecureStorage();

  bool _working = true;
  bool _loaded = false;

  @override
  void initState() {
    super.initState();
    _loadWorking();
  }

  Future<void> _loadWorking() async {
    try {
      final raw = await _storage.read(key: _workingKey);
      final next = raw == null ? _working : (raw == '1' || raw.toLowerCase() == 'true');
      if (!mounted) return;
      setState(() {
        _working = next;
        _loaded = true;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() => _loaded = true);
    }
  }

  Future<void> _setWorking(bool next) async {
    setState(() => _working = next);
    try {
      await _storage.write(key: _workingKey, value: next ? '1' : '0');
    } catch (_) {}
  }

  String _fmtMoney(num v) => '₺${v.toStringAsFixed(2)}';

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final me = AppScope.of(context).auth.me;
    final firstName = (me?.name ?? '').trim().split(RegExp(r'\s+')).firstWhere(
          (x) => x.isNotEmpty,
          orElse: () => '—',
        );

    return Scaffold(
      backgroundColor: Colors.transparent,
      appBar: AppBar(
        title: Text('Merhaba, $firstName'),
        actions: [
          IconButton(
            onPressed: () => ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Yakında.'))),
            icon: const Icon(Icons.notifications_none),
            tooltip: 'Bildirimler',
          ),
        ],
      ),
      body: AppBackground(
        child: AppContent(
          child: FutureBuilder<Map<String, dynamic>>(
            future: CourierStatsApi(AppScope.of(context).api).summary(),
            builder: (context, snap) {
              final data = snap.data;
              final ok = data != null && data['ok'] == true;
              final activeOrders = ok ? ((data['active_orders'] as int?) ?? 0) : null;
              final today = ok && (data['today'] is Map) ? (data['today'] as Map) : null;
              final deliveredCount = today?['delivered_count'] as int?;
              final payout = (today?['payout_total'] as num?)?.toDouble();

              return ListView(
                padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
                children: [
                  GlassCard(
                    padding: const EdgeInsets.all(14),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'ÇALIŞMA DURUMU',
                          style: theme.textTheme.labelLarge?.copyWith(
                            color: theme.textTheme.bodySmall?.color,
                            fontWeight: FontWeight.w900,
                            letterSpacing: 0.3,
                          ),
                        ),
                        const SizedBox(height: 10),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
                          decoration: BoxDecoration(
                            borderRadius: BorderRadius.circular(16),
                            gradient: LinearGradient(
                              colors: [
                                theme.colorScheme.primary.withValues(alpha: 0.85),
                                theme.colorScheme.primary.withValues(alpha: 0.55),
                              ],
                            ),
                          ),
                          child: Row(
                            children: [
                              Expanded(
                                child: Text(
                                  _working ? 'ÇALIŞIYOR' : 'ÇALIŞMIYOR',
                                  style: const TextStyle(
                                    fontWeight: FontWeight.w900,
                                    color: Colors.white,
                                  ),
                                ),
                              ),
                              Switch.adaptive(
                                value: _working,
                                activeThumbColor: Colors.white,
                                activeTrackColor: Colors.white.withValues(alpha: 0.28),
                                inactiveThumbColor: Colors.white.withValues(alpha: 0.75),
                                inactiveTrackColor: Colors.white.withValues(alpha: 0.18),
                                onChanged: _loaded ? (v) => _setWorking(v) : null,
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(height: 10),
                        Text(
                          activeOrders == null ? 'Aktif sipariş: —' : 'Aktif sipariş: $activeOrders',
                          style: theme.textTheme.bodySmall,
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 12),
                  Text('BUGÜNKÜ ÖZET', style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w900)),
                  const SizedBox(height: 8),
                  _StatsRow(
                    items: [
                      _StatItem(label: 'Sipariş', value: deliveredCount?.toString() ?? '—'),
                      _StatItem(label: 'Kazanç', value: payout == null ? '—' : _fmtMoney(payout)),
                      const _StatItem(label: 'Puan', value: '—'),
                    ],
                  ),
                  const SizedBox(height: 12),
                  GlassCard(
                    padding: const EdgeInsets.all(14),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('Hızlı işlemler', style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w900)),
                        const SizedBox(height: 10),
                        Wrap(
                          spacing: 10,
                          runSpacing: 10,
                          children: [
                            _QuickAction(
                              icon: Icons.map_outlined,
                              label: 'Harita',
                              onTap: () {
                                Navigator.of(context).push<void>(
                                  MaterialPageRoute<void>(builder: (_) => const MapScreen()),
                                );
                              },
                            ),
                            _QuickAction(icon: Icons.bar_chart, label: 'Kazanç', onTap: () => widget.onJumpToTab?.call(1)),
                            _QuickAction(icon: Icons.person_outline, label: 'Profil', onTap: () => widget.onJumpToTab?.call(2)),
                          ],
                        ),
                        if (snap.hasError) ...[
                          const SizedBox(height: 10),
                          Text(
                            'Özet alınamadı.',
                            style: theme.textTheme.bodySmall?.copyWith(color: theme.colorScheme.error),
                          ),
                        ],
                      ],
                    ),
                  ),
                  const SizedBox(height: 12),
                  Text('AKTİF SİPARİŞLER', style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w900)),
                  const SizedBox(height: 8),
                  CourierOrdersSection(api: CourierApi(AppScope.of(context).api)),
                ],
              );
            },
          ),
        ),
      ),
    );
  }
}

class _QuickAction extends StatelessWidget {
  const _QuickAction({required this.icon, required this.label, required this.onTap});

  final IconData icon;
  final String label;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return InkWell(
      borderRadius: BorderRadius.circular(14),
      onTap: onTap,
      child: Ink(
        width: 136,
        padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 10),
        decoration: BoxDecoration(
          color: theme.colorScheme.surfaceContainerHighest.withValues(alpha: theme.brightness == Brightness.dark ? 0.18 : 0.55),
          borderRadius: BorderRadius.circular(14),
        ),
        child: Row(
          children: [
            Icon(icon, color: theme.colorScheme.primary, size: 20),
            const SizedBox(width: 8),
            Expanded(
              child: Text(
                label,
                style: theme.textTheme.labelMedium?.copyWith(fontWeight: FontWeight.w800),
                overflow: TextOverflow.ellipsis,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _StatsRow extends StatelessWidget {
  const _StatsRow({required this.items});
  final List<_StatItem> items;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: items
          .map(
            (i) => Expanded(
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 4),
                child: Card(
                  child: Padding(
                    padding: const EdgeInsets.all(12),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(i.label, style: Theme.of(context).textTheme.labelMedium),
                        const SizedBox(height: 6),
                        Text(i.value, style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w900)),
                      ],
                    ),
                  ),
                ),
              ),
            ),
          )
          .toList(),
    );
  }
}

class _StatItem {
  const _StatItem({required this.label, required this.value});
  final String label;
  final String value;
}

