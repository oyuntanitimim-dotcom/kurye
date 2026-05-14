import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart';
import 'package:go_router/go_router.dart';
import 'package:kurye_mobile/core/app_scope.dart';
import 'package:kurye_mobile/core/ui/app_background.dart';
import 'package:kurye_mobile/core/ui/app_content.dart';
import 'package:kurye_mobile/core/ui/glass_card.dart';
import 'package:kurye_mobile/core/ui/theme_mode_store.dart';

class ProfileScreen extends StatelessWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final scope = AppScope.of(context);
    final me = scope.auth.me;
    final theme = Theme.of(context);

    final chevron = Icon(Icons.chevron_right, size: 22, color: theme.textTheme.bodySmall?.color);

    return Scaffold(
      backgroundColor: Colors.transparent,
      appBar: AppBar(title: const Text('Profil')),
      body: AppBackground(
        child: AppContent(
          child: ListView(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
            children: [
            GlassCard(
              padding: const EdgeInsets.all(14),
              child: Row(
                children: [
                  CircleAvatar(
                    radius: 24,
                    backgroundColor: theme.colorScheme.primary.withValues(alpha: 0.12),
                    child: Icon(Icons.person, color: theme.colorScheme.primary),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          me?.name ?? '—',
                          style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w900),
                        ),
                        const SizedBox(height: 2),
                        Text(
                          (me?.role ?? '').toString(),
                          style: theme.textTheme.bodySmall,
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 12),
            if (kDebugMode) ...[
              GlassCard(
                padding: const EdgeInsets.all(14),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Debug', style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w900)),
                    const SizedBox(height: 10),
                    _KvRow(label: 'API', value: scope.configStore.config.apiBaseUrl),
                    _KvRow(label: 'User ID', value: (me?.id).toString()),
                    _KvRow(label: 'Courier ID', value: (me?.courierId).toString()),
                    _KvRow(label: 'Firm ID', value: (me?.firmId).toString()),
                  ],
                ),
              ),
              const SizedBox(height: 12),
            ],
            GlassCard(
              child: AnimatedBuilder(
                animation: scope.themeStore,
                builder: (context, _) {
                  return Column(
                    children: [
                      const ListTile(
                        leading: Icon(Icons.palette_outlined),
                        title: Text('Görünüm'),
                        subtitle: Text('Tema seçimi (kalıcı)'),
                      ),
                      const Divider(height: 1),
                      _ThemeRadioTile(
                        title: 'Sistem',
                        subtitle: 'Cihaz ayarına göre otomatik',
                        value: AppThemePreference.system,
                        groupValue: scope.themeStore.preference,
                        onChanged: (v) => scope.themeStore.setPreference(v),
                        leading: Icons.settings_suggest_outlined,
                      ),
                      _ThemeRadioTile(
                        title: 'Açık',
                        value: AppThemePreference.light,
                        groupValue: scope.themeStore.preference,
                        onChanged: (v) => scope.themeStore.setPreference(v),
                        leading: Icons.light_mode_outlined,
                      ),
                      _ThemeRadioTile(
                        title: 'Koyu',
                        value: AppThemePreference.dark,
                        groupValue: scope.themeStore.preference,
                        onChanged: (v) => scope.themeStore.setPreference(v),
                        leading: Icons.dark_mode_outlined,
                      ),
                    ],
                  );
                },
              ),
            ),
            const SizedBox(height: 12),
            GlassCard(
              child: Column(
                children: [
                  ListTile(
                    leading: Icon(Icons.settings_outlined, color: theme.colorScheme.primary),
                    title: const Text('Ayarlar'),
                    subtitle: const Text('Yakında'),
                    trailing: chevron,
                    onTap: () {
                      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Yakında.')));
                    },
                  ),
                  Divider(height: 1, color: theme.dividerColor.withValues(alpha: 0.4)),
                  ListTile(
                    leading: Icon(Icons.support_agent, color: theme.colorScheme.primary),
                    title: const Text('Yardım & Destek'),
                    subtitle: const Text('Yakında'),
                    trailing: chevron,
                    onTap: () {
                      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Yakında.')));
                    },
                  ),
                ],
              ),
            ),
            const SizedBox(height: 12),
            FilledButton.tonalIcon(
              onPressed: () async {
                await scope.auth.clear();
                if (!context.mounted) return;
                context.go('/login');
              },
              icon: const Icon(Icons.logout),
              label: const Text('Çıkış yap'),
            ),
            ],
          ),
        ),
      ),
    );
  }
}

class _KvRow extends StatelessWidget {
  const _KvRow({required this.label, required this.value});
  final String label;
  final String? value;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Padding(
      padding: const EdgeInsets.only(bottom: 6),
      child: Row(
        children: [
          SizedBox(
            width: 90,
            child: Text(
              label,
              style: theme.textTheme.bodySmall?.copyWith(color: theme.colorScheme.onSurfaceVariant),
            ),
          ),
          Expanded(
            child: Text(
              value ?? '—',
              style: theme.textTheme.bodySmall?.copyWith(fontWeight: FontWeight.w700),
              overflow: TextOverflow.ellipsis,
            ),
          ),
        ],
      ),
    );
  }
}

class _ThemeRadioTile extends StatelessWidget {
  const _ThemeRadioTile({
    required this.title,
    required this.value,
    required this.groupValue,
    required this.onChanged,
    required this.leading,
    this.subtitle,
  });

  final String title;
  final String? subtitle;
  final AppThemePreference value;
  final AppThemePreference groupValue;
  final Future<void> Function(AppThemePreference value) onChanged;
  final IconData leading;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return RadioListTile<AppThemePreference>(
      value: value,
      groupValue: groupValue,
      onChanged: (v) {
        if (v == null) return;
        onChanged(v);
      },
      secondary: Icon(leading, color: theme.colorScheme.primary),
      title: Text(title, style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w800)),
      subtitle: subtitle == null ? null : Text(subtitle!, style: theme.textTheme.bodySmall),
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 2),
    );
  }
}

