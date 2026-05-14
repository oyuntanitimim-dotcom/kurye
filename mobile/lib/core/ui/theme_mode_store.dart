import 'package:flutter/material.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

enum AppThemePreference {
  system,
  light,
  dark,
}

class ThemeModeStore extends ChangeNotifier {
  static const _key = 'theme_preference';

  ThemeModeStore({FlutterSecureStorage? storage}) : _storage = storage ?? const FlutterSecureStorage();

  final FlutterSecureStorage _storage;

  AppThemePreference _pref = AppThemePreference.system;

  AppThemePreference get preference => _pref;

  ThemeMode get themeMode => switch (_pref) {
        AppThemePreference.system => ThemeMode.system,
        AppThemePreference.light => ThemeMode.light,
        AppThemePreference.dark => ThemeMode.dark,
      };

  Future<void> load() async {
    final raw = await _storage.read(key: _key);
    _pref = switch (raw) {
      'light' => AppThemePreference.light,
      'dark' => AppThemePreference.dark,
      _ => AppThemePreference.system,
    };
    notifyListeners();
  }

  Future<void> setPreference(AppThemePreference next) async {
    if (_pref == next) return;
    _pref = next;
    await _storage.write(
      key: _key,
      value: switch (next) {
        AppThemePreference.light => 'light',
        AppThemePreference.dark => 'dark',
        AppThemePreference.system => 'system',
      },
    );
    notifyListeners();
  }
}

