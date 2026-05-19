import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart';
import 'package:kurye_mobile/core/auth/auth_store.dart';
import 'package:kurye_mobile/core/app_scope.dart';
import 'package:kurye_mobile/core/config/app_config.dart';
import 'package:kurye_mobile/core/config/app_config_store.dart';
import 'package:kurye_mobile/core/http/api_client.dart';
import 'package:kurye_mobile/core/notifications/local_notifications.dart';
import 'package:kurye_mobile/core/ui/app_theme.dart';
import 'package:kurye_mobile/core/ui/shell_nav_store.dart';
import 'package:kurye_mobile/core/ui/theme_mode_store.dart';
import 'package:kurye_mobile/routes/app_router.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // Varsayılan: local (127.0.0.1 / 10.0.2.2:8000)
  // Canlı: `--dart-define=API_ENV=production` veya `.\build-canli.ps1`
  final config = AppConfig.fromEnvironment(isWeb: kIsWeb);
  final configStore = AppConfigStore(config);
  final authStore = AuthStore();
  await authStore.load();
  final themeStore = ThemeModeStore();
  await themeStore.load();
  final shellNav = ShellNavStore();
  final api = ApiClient(baseUrl: config.apiBaseUrl, authStore: authStore);
  final notifications = await LocalNotifications.init();

  runApp(AppRoot(
    configStore: configStore,
    authStore: authStore,
    themeStore: themeStore,
    api: api,
    notifications: notifications,
    shellNav: shellNav,
  ));
}

class AppRoot extends StatelessWidget {
  const AppRoot({
    super.key,
    required this.configStore,
    required this.authStore,
    required this.themeStore,
    required this.api,
    required this.notifications,
    required this.shellNav,
  });

  final AppConfigStore configStore;
  final AuthStore authStore;
  final ThemeModeStore themeStore;
  final ApiClient api;
  final LocalNotifications notifications;
  final ShellNavStore shellNav;

  @override
  Widget build(BuildContext context) {
    final router = buildRouter(authStore: authStore, api: api);

    return AppScope(
      configStore: configStore,
      auth: authStore,
      api: api,
      notifications: notifications,
      themeStore: themeStore,
      shellNav: shellNav,
      child: AnimatedBuilder(
        animation: themeStore,
        builder: (context, _) {
          return MaterialApp.router(
            title: 'Kurye',
            theme: buildLightAppTheme(),
            darkTheme: buildDarkAppTheme(),
            themeMode: themeStore.themeMode,
            routerConfig: router,
          );
        },
      ),
    );
  }
}

