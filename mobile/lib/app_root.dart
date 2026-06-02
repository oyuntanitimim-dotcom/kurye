import 'package:flutter/material.dart';
import 'package:kurye_mobile/core/auth/auth_store.dart';
import 'package:kurye_mobile/core/app_scope.dart';
import 'package:kurye_mobile/core/config/app_config_store.dart';
import 'package:kurye_mobile/core/http/api_client.dart';
import 'package:kurye_mobile/core/notifications/local_notifications.dart';
import 'package:kurye_mobile/core/ui/app_theme.dart';
import 'package:kurye_mobile/core/ui/shell_nav_store.dart';
import 'package:kurye_mobile/core/ui/theme_mode_store.dart';
import 'package:kurye_mobile/routes/app_router.dart';

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
