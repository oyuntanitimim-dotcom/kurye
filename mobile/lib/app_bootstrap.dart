import 'dart:async' show unawaited;

import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:kurye_mobile/core/auth/auth_store.dart';
import 'package:kurye_mobile/core/config/app_config.dart';
import 'package:kurye_mobile/core/config/app_config_store.dart';
import 'package:kurye_mobile/core/http/api_client.dart';
import 'package:kurye_mobile/core/location/foreground_location_service.dart';
import 'package:kurye_mobile/core/notifications/local_notifications.dart';
import 'package:kurye_mobile/core/ui/shell_nav_store.dart';
import 'package:kurye_mobile/core/ui/theme_mode_store.dart';
import 'package:kurye_mobile/app_root.dart';

/// İlk kareyi hızlı çizer; ağır init main thread'i kilitlemez (MIUI siyah ekran).
class AppBootstrap extends StatefulWidget {
  const AppBootstrap({super.key});

  @override
  State<AppBootstrap> createState() => _AppBootstrapState();
}

class _AppBootstrapState extends State<AppBootstrap> {
  static const _initTimeout = Duration(seconds: 12);

  Object? _error;
  AppRoot? _app;

  @override
  void initState() {
    super.initState();
    unawaited(_boot());
  }

  Future<void> _boot() async {
    setState(() {
      _error = null;
      _app = null;
    });

    try {
      final config = AppConfig.fromEnvironment(isWeb: kIsWeb);
      final configStore = AppConfigStore(config);
      final authStore = AuthStore();
      final themeStore = ThemeModeStore();
      final shellNav = ShellNavStore();

      await _safe('auth', () => authStore.load());
      await _safe('theme', () => themeStore.load());

      final api = ApiClient(baseUrl: config.apiBaseUrl, authStore: authStore);

      LocalNotifications? notifications;
      await _safe('notifications', () async {
        notifications = await LocalNotifications.init();
      });

      await _safe('api_base_url', () => ForegroundLocationService.persistApiBaseUrl(config.apiBaseUrl));

      if (!mounted) return;
      setState(() {
        _app = AppRoot(
          configStore: configStore,
          authStore: authStore,
          themeStore: themeStore,
          api: api,
          notifications: notifications ?? LocalNotifications.noop(),
          shellNav: shellNav,
        );
      });
    } catch (e, st) {
      debugPrint('Bootstrap failed: $e\n$st');
      if (!mounted) return;
      setState(() => _error = e);
    }
  }

  Future<void> _safe(String label, Future<void> Function() fn) async {
    try {
      await fn().timeout(_initTimeout);
    } catch (e, st) {
      debugPrint('Bootstrap [$label] skipped: $e\n$st');
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_error != null) {
      return MaterialApp(
        home: Scaffold(
          body: SafeArea(
            child: Padding(
              padding: const EdgeInsets.all(24),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Icon(Icons.error_outline, size: 48, color: Colors.red),
                  const SizedBox(height: 16),
                  const Text(
                    'Uygulama başlatılamadı',
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 8),
                  Text(
                    _error.toString(),
                    textAlign: TextAlign.center,
                    style: const TextStyle(fontSize: 13, color: Colors.black54),
                  ),
                  const SizedBox(height: 24),
                  FilledButton(
                    onPressed: _boot,
                    child: const Text('Tekrar dene'),
                  ),
                ],
              ),
            ),
          ),
        ),
      );
    }

    if (_app == null) {
      return const MaterialApp(
        home: Scaffold(
          backgroundColor: Color(0xfff6f7fb),
          body: Center(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                CircularProgressIndicator(),
                SizedBox(height: 16),
                Text('Yükleniyor…'),
              ],
            ),
          ),
        ),
      );
    }

    return _app!;
  }
}
