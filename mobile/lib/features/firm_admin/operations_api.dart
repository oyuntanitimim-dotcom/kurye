import 'package:dio/dio.dart';
import 'package:kurye_mobile/core/http/api_client.dart';

class OperationsApi {
  OperationsApi(this._api);

  final ApiClient _api;

  Future<Map<String, dynamic>> snapshot() async {
    // Web'de tarayıcı GET önbelleği aynı cevabı tekrar edebilir; konum güncellenmez görünür.
    final r = await _api.dio.get(
      '/api/v1/firm/operations/snapshot',
      queryParameters: <String, dynamic>{
        '_': DateTime.now().millisecondsSinceEpoch,
        'order_limit': 60,
        'include_address': false,
      },
      options: Options(headers: <String, String>{
        'Cache-Control': 'no-cache',
        'Pragma': 'no-cache',
      }),
    );
    final data = r.data;
    if (data is! Map) throw Exception('Invalid snapshot response');
    return Map<String, dynamic>.from(data);
  }

  Future<void> assignCourier({required int orderId, required int courierId}) async {
    await _api.dio.post('/api/v1/firm/orders/$orderId/assign-courier', data: {
      'courier_id': courierId,
    });
  }

  Future<bool> setAutoAssignBestAfterEta(bool value) async {
    final r = await _api.dio.patch('/api/v1/firm/operations/settings', data: {
      'auto_assign_best_after_eta': value,
    });
    final data = r.data;
    if (data is Map && data['ok'] == true) {
      final settings = data['settings'];
      if (settings is Map && settings['auto_assign_best_after_eta'] is bool) {
        return settings['auto_assign_best_after_eta'] as bool;
      }
      return value;
    }
    throw Exception('settings_update_failed');
  }
}

