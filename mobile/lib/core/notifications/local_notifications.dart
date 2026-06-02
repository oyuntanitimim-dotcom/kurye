import 'package:flutter/foundation.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';

class LocalNotifications {
  LocalNotifications(this._plugin, {this.enabled = true});

  final FlutterLocalNotificationsPlugin? _plugin;
  final bool enabled;

  /// Bildirim eklentisi başlatılamazsa (bazı MIUI sürümleri) sessiz devam.
  factory LocalNotifications.noop() => LocalNotifications(null, enabled: false);

  static Future<LocalNotifications> init() async {
    final plugin = FlutterLocalNotificationsPlugin();

    const android = AndroidInitializationSettings('@mipmap/ic_launcher');
    const ios = DarwinInitializationSettings();
    const init = InitializationSettings(android: android, iOS: ios);

    await plugin.initialize(init);

    // Web: local notifications plugin may be unavailable; keep it no-op.
    if (!kIsWeb && (defaultTargetPlatform == TargetPlatform.iOS || defaultTargetPlatform == TargetPlatform.macOS)) {
      await plugin
          .resolvePlatformSpecificImplementation<IOSFlutterLocalNotificationsPlugin>()
          ?.requestPermissions(alert: true, badge: true, sound: true);
      await plugin
          .resolvePlatformSpecificImplementation<MacOSFlutterLocalNotificationsPlugin>()
          ?.requestPermissions(alert: true, badge: true, sound: true);
    }

    // Android 13+: runtime permission.
    if (!kIsWeb && defaultTargetPlatform == TargetPlatform.android) {
      await plugin
          .resolvePlatformSpecificImplementation<AndroidFlutterLocalNotificationsPlugin>()
          ?.requestNotificationsPermission();
    }

    return LocalNotifications(plugin);
  }

  Future<void> incomingOrder({required int orderId, String? title}) async {
    if (!enabled || kIsWeb || _plugin == null) {
      return;
    }
    const androidDetails = AndroidNotificationDetails(
      'courier_orders',
      'Kurye sipariş uyarıları',
      channelDescription: 'Kurye panelinde size atanan siparişler için uyarı',
      importance: Importance.max,
      priority: Priority.high,
      playSound: true,
    );
    const iosDetails = DarwinNotificationDetails(presentAlert: true, presentSound: true);
    const details = NotificationDetails(android: androidDetails, iOS: iosDetails);

    await _plugin.show(
      orderId, // stable id
      title ?? 'Yeni sipariş',
      'Sipariş #$orderId size atandı.',
      details,
    );
  }

  Future<void> firmIncomingOrder({required int orderId, String? title}) async {
    if (!enabled || kIsWeb || _plugin == null) {
      return;
    }
    const androidDetails = AndroidNotificationDetails(
      'firm_orders',
      'Firma sipariş uyarıları',
      channelDescription: 'Firma panelinde kurye bekleyen siparişler için uyarı',
      importance: Importance.max,
      priority: Priority.high,
      playSound: true,
    );
    const iosDetails = DarwinNotificationDetails(presentAlert: true, presentSound: true);
    const details = NotificationDetails(android: androidDetails, iOS: iosDetails);

    await _plugin.show(
      1000000 + orderId, // avoid collision with courier channel ids
      title ?? 'Yeni sipariş',
      'Sipariş #$orderId kurye bekliyor.',
      details,
    );
  }
}

