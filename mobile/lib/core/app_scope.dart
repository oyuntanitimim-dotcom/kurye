import 'package:flutter/widgets.dart';
import 'package:kurye_mobile/core/auth/auth_store.dart';
import 'package:kurye_mobile/core/config/app_config_store.dart';
import 'package:kurye_mobile/core/http/api_client.dart';
import 'package:kurye_mobile/core/notifications/local_notifications.dart';
import 'package:kurye_mobile/core/ui/shell_nav_store.dart';
import 'package:kurye_mobile/core/ui/theme_mode_store.dart';

class AppScope extends InheritedWidget {
  const AppScope({
    super.key,
    required this.configStore,
    required this.auth,
    required this.api,
    required this.notifications,
    required this.themeStore,
    required this.shellNav,
    required super.child,
  });

  final AppConfigStore configStore;
  final AuthStore auth;
  final ApiClient api;
  final LocalNotifications notifications;
  final ThemeModeStore themeStore;
  final ShellNavStore shellNav;

  static AppScope of(BuildContext context) {
    final scope = context.dependOnInheritedWidgetOfExactType<AppScope>();
    assert(scope != null, 'AppScope not found in context');
    return scope!;
  }

  @override
  bool updateShouldNotify(covariant AppScope oldWidget) {
    return configStore != oldWidget.configStore ||
        auth != oldWidget.auth ||
        api != oldWidget.api ||
        notifications != oldWidget.notifications ||
        themeStore != oldWidget.themeStore ||
        shellNav != oldWidget.shellNav;
  }
}

