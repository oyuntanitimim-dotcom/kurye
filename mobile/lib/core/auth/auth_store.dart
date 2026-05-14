import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

enum AppRole {
  courier,
  restaurant,
  firmAdmin,
  unknown,
}

class Me {
  const Me({
    required this.id,
    required this.name,
    required this.role,
    this.firmId,
    this.restaurantId,
    this.courierId,
  });

  final int id;
  final String name;
  final AppRole role;
  final int? firmId;
  final int? restaurantId;
  final int? courierId;

  static Me? fromJson(Map<String, dynamic> json) {
    final roleStr = (json['role'] ?? '').toString();
    final role = switch (roleStr) {
      'courier' => AppRole.courier,
      'restaurant' => AppRole.restaurant,
      'firm_admin' => AppRole.firmAdmin,
      _ => AppRole.unknown,
    };

    final id = int.tryParse(json['id'].toString());
    final name = (json['name'] ?? '').toString();
    if (id == null || name.isEmpty) return null;

    int? asInt(dynamic v) => v == null ? null : int.tryParse(v.toString());

    return Me(
      id: id,
      name: name,
      role: role,
      firmId: asInt(json['firm_id']),
      restaurantId: asInt(json['restaurant_id']),
      courierId: asInt(json['courier_id']),
    );
  }
}

class AuthStore extends ChangeNotifier {
  static const _tokenKey = 'auth_token';
  static const _meKey = 'auth_me';

  final _storage = const FlutterSecureStorage();

  String? _token;
  Me? _me;

  String? get token => _token;
  Me? get me => _me;

  Future<void> load() async {
    _token = await _storage.read(key: _tokenKey);
    final raw = await _storage.read(key: _meKey);
    if (raw != null && raw.isNotEmpty) {
      final decoded = jsonDecode(raw);
      if (decoded is Map<String, dynamic>) {
        _me = Me.fromJson(decoded);
      }
    }
    notifyListeners();
  }

  Future<void> setSession({required String token, required Map<String, dynamic> meJson}) async {
    _token = token;
    _me = Me.fromJson(meJson);
    await _storage.write(key: _tokenKey, value: token);
    await _storage.write(key: _meKey, value: jsonEncode(meJson));
    notifyListeners();
  }

  /// Token'ı geçici olarak yazıp UI'ı tetiklemeden /me çağrısı yapabilmek için.
  Future<void> setTokenOnly(String token) async {
    _token = token;
    await _storage.write(key: _tokenKey, value: token);
  }

  Future<void> clear() async {
    _token = null;
    _me = null;
    await _storage.delete(key: _tokenKey);
    await _storage.delete(key: _meKey);
    notifyListeners();
  }
}

