import 'dart:async';

import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_foreground_task/flutter_foreground_task.dart';
import 'package:kurye_mobile/core/storage/app_secure_storage.dart';
import 'package:geolocator/geolocator.dart';

/// Foreground service ile arka planda konum gönderen servis.
///
/// Notlar:
/// - Android, arka planda periyodik GPS için foreground service ister.
/// - Token ve baseUrl secure storage'dan okunur.
class ForegroundLocationService {
  static const String _apiBaseUrlKey = 'api_base_url';
  static const String _authTokenKey = 'auth_token';

  static bool _initialized = false;

  /// Uygulama açılışında değil; konum paylaşımı açılırken çağrılır (MIUI siyah ekran riski).
  static Future<void> ensureInitialized() async {
    if (kIsWeb || _initialized) return;

    try {
      FlutterForegroundTask.init(
      androidNotificationOptions: AndroidNotificationOptions(
        channelId: 'kurye_location_channel',
        channelName: 'Konum Paylaşımı',
        channelDescription: 'Kurye konumu paylaşılırken arka planda çalışır.',
        channelImportance: NotificationChannelImportance.LOW,
        priority: NotificationPriority.LOW,
      ),
      iosNotificationOptions: const IOSNotificationOptions(),
      foregroundTaskOptions: ForegroundTaskOptions(
        eventAction: ForegroundTaskEventAction.repeat(10000), // 10 sn
        autoRunOnBoot: false,
        allowWakeLock: true,
        allowWifiLock: true,
      ),
      );
      _initialized = true;
    } catch (e, st) {
      debugPrint('ForegroundLocationService init failed: $e\n$st');
    }
  }

  static Future<void> persistApiBaseUrl(String baseUrl) async {
    if (kIsWeb) return;
    await appSecureStorage.write(key: _apiBaseUrlKey, value: baseUrl);
  }

  static Future<void> persistAuthToken(String token) async {
    if (kIsWeb) return;
    await appSecureStorage.write(key: _authTokenKey, value: token);
  }

  static Future<void> start() async {
    if (kIsWeb) return;
    await ensureInitialized();
    if (await FlutterForegroundTask.isRunningService) return;

    await FlutterForegroundTask.startService(
      notificationTitle: 'Konum paylaşımı açık',
      notificationText: 'Kurye konumu gönderiliyor…',
      serviceTypes: const [ForegroundServiceTypes.location],
      callback: startCallback,
    );
  }

  static Future<void> stop() async {
    if (kIsWeb) return;
    if (!await FlutterForegroundTask.isRunningService) return;
    await FlutterForegroundTask.stopService();
  }
}

@pragma('vm:entry-point')
void startCallback() {
  FlutterForegroundTask.setTaskHandler(_CourierLocationTaskHandler());
}

class _CourierLocationTaskHandler extends TaskHandler {
  static const storage = appSecureStorage;

  Dio? _dio;

  @override
  Future<void> onStart(DateTime timestamp, TaskStarter starter) async {
    // Token + baseUrl olmadan iş yapamayız.
    final baseUrl = await storage.read(key: ForegroundLocationService._apiBaseUrlKey);
    final token = await storage.read(key: ForegroundLocationService._authTokenKey);
    if (baseUrl == null || baseUrl.isEmpty || token == null || token.isEmpty) {
      await FlutterForegroundTask.updateService(
        notificationTitle: 'Konum paylaşımı beklemede',
        notificationText: 'Oturum yok (token bulunamadı).',
      );
      return;
    }

    _dio = Dio(BaseOptions(baseUrl: baseUrl))
      ..options.headers['Authorization'] = 'Bearer $token'
      ..options.headers['Accept'] = 'application/json';
  }

  @override
  void onRepeatEvent(DateTime timestamp) {
    unawaited(_repeatOnce(timestamp));
  }

  Future<void> _repeatOnce(DateTime timestamp) async {
    final dio = _dio;
    if (dio == null) return;

    try {
      final pos = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.best,
          timeLimit: Duration(seconds: 8),
        ),
      );
      await dio.post(
        '/api/v1/courier/location',
        data: {'latitude': pos.latitude, 'longitude': pos.longitude},
      );

      await FlutterForegroundTask.updateService(
        notificationTitle: 'Konum paylaşımı açık',
        notificationText: 'Son gönderim: ${timestamp.toLocal().toString().substring(11, 19)}',
      );
    } catch (_) {
      // Sessizce devam; OS konumu geçici olarak veremeyebilir.
    }
  }

  @override
  Future<void> onDestroy(DateTime timestamp, bool isTimeout) async {
    _dio = null;
  }

  @override
  void onNotificationButtonPressed(String id) {}

  @override
  void onNotificationPressed() {}
}

