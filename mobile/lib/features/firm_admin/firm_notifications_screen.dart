import 'package:flutter/material.dart';
import 'package:kurye_mobile/core/app_scope.dart';
import 'package:kurye_mobile/core/ui/app_content.dart';
import 'package:kurye_mobile/core/ui/glass_card.dart';
import 'package:kurye_mobile/features/firm_admin/firm_admin_api.dart';

class FirmNotificationsScreen extends StatefulWidget {
  const FirmNotificationsScreen({super.key});

  @override
  State<FirmNotificationsScreen> createState() => _FirmNotificationsScreenState();
}

class _FirmNotificationsScreenState extends State<FirmNotificationsScreen> {
  late Future<Map<String, dynamic>> _future;
  bool _bootstrapped = false;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (_bootstrapped) return;
    _bootstrapped = true;
    _future = _load();
  }

  FirmAdminApi _api(BuildContext context) => FirmAdminApi(AppScope.of(context).api);

  Future<Map<String, dynamic>> _load() => _api(context).notifications(page: 1);

  Future<void> _refresh() async {
    setState(() => _future = _load());
    await _future;
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Scaffold(
      appBar: AppBar(
        title: const Text('Bildirimler'),
        actions: [
          IconButton(
            onPressed: () async {
              await _api(context).markAllNotificationsRead();
              if (!context.mounted) return;
              await _refresh();
            },
            icon: const Icon(Icons.done_all),
            tooltip: 'Hepsini okundu yap',
          ),
          IconButton(onPressed: _refresh, icon: const Icon(Icons.refresh)),
        ],
      ),
      body: AppContent(
        child: FutureBuilder<Map<String, dynamic>>(
          future: _future,
          builder: (context, snap) {
            if (snap.connectionState != ConnectionState.done) {
              return const Center(child: CircularProgressIndicator());
            }
            if (snap.hasError) {
              return const Center(child: Text('Bildirimler yüklenemedi.'));
            }

            final d = snap.data ?? const {};
            final items = (d['data'] as List?) ?? const [];
            if (items.isEmpty) {
              return const Center(child: Text('Bildirim yok.'));
            }

            return RefreshIndicator(
              onRefresh: _refresh,
              child: ListView.separated(
                padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
                itemCount: items.length,
                separatorBuilder: (_, __) => const SizedBox(height: 10),
                itemBuilder: (context, i) {
                  final m = items[i] as Map? ?? const {};
                  final id = int.tryParse((m['id'] ?? '').toString());
                  final title = (m['title'] ?? 'Bildirim').toString();
                  final msg = (m['message'] ?? '').toString();
                  final status = (m['status'] ?? '').toString();
                  final unread = status == 'unread';

                  return GlassCard(
                    padding: const EdgeInsets.all(14),
                    child: InkWell(
                      borderRadius: BorderRadius.circular(18),
                      onTap: id == null
                          ? null
                          : () async {
                              final api = _api(context);
                              await showModalBottomSheet(
                                context: context,
                                showDragHandle: true,
                                builder: (context) => Padding(
                                  padding: const EdgeInsets.fromLTRB(16, 0, 16, 24),
                                  child: Column(
                                    mainAxisSize: MainAxisSize.min,
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(title, style: theme.textTheme.titleMedium),
                                      const SizedBox(height: 10),
                                      Text(msg, style: theme.textTheme.bodyMedium),
                                    ],
                                  ),
                                ),
                              );
                              if (!mounted) return;
                              if (unread) {
                                await api.markNotificationRead(id);
                                if (!mounted) return;
                                await _refresh();
                              }
                            },
                      child: Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Container(
                            height: 42,
                            width: 42,
                            decoration: BoxDecoration(
                              color: unread
                                  ? theme.colorScheme.primary.withValues(alpha: 0.12)
                                  : theme.colorScheme.surface.withValues(alpha: 0.5),
                              borderRadius: BorderRadius.circular(14),
                              border: Border.all(color: theme.dividerColor.withValues(alpha: 0.6)),
                            ),
                            child: Icon(
                              unread ? Icons.notifications_active_outlined : Icons.notifications_none,
                              color: unread ? theme.colorScheme.primary : (theme.textTheme.bodySmall?.color),
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  children: [
                                    Expanded(
                                      child: Text(
                                        title,
                                        style: theme.textTheme.titleSmall?.copyWith(
                                          fontWeight: FontWeight.w900,
                                          color: unread ? theme.colorScheme.primary : null,
                                        ),
                                      ),
                                    ),
                                    if (unread)
                                      Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                                        decoration: BoxDecoration(
                                          borderRadius: BorderRadius.circular(999),
                                          color: theme.colorScheme.primary.withValues(alpha: 0.12),
                                        ),
                                        child: Text(
                                          'Yeni',
                                          style: theme.textTheme.labelSmall?.copyWith(
                                            fontWeight: FontWeight.w900,
                                            color: theme.colorScheme.primary,
                                          ),
                                        ),
                                      ),
                                  ],
                                ),
                                const SizedBox(height: 6),
                                Text(
                                  msg,
                                  maxLines: 2,
                                  overflow: TextOverflow.ellipsis,
                                  style: theme.textTheme.bodySmall,
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                  );
                },
              ),
            );
          },
        ),
      ),
    );
  }
}

