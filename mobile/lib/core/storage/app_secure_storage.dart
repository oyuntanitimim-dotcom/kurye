import 'package:flutter_secure_storage/flutter_secure_storage.dart';

/// Xiaomi / MIUI ve bazı OEM'lerde KeyStore + SharedPreferences uyumluluğu için ortak ayarlar.
const FlutterSecureStorage appSecureStorage = FlutterSecureStorage(
  aOptions: AndroidOptions(
    encryptedSharedPreferences: true,
    resetOnError: true,
  ),
  iOptions: IOSOptions(
    accessibility: KeychainAccessibility.first_unlock_this_device,
  ),
);
