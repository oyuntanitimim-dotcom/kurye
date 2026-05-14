import 'package:dio/dio.dart';
import 'package:kurye_mobile/core/http/api_client.dart';

class CourierApi {
  CourierApi(this._api);

  final ApiClient _api;

  Dio get _dio => _api.dio;

  Future<List<dynamic>> activeOrders() async {
    final r = await _dio.get('/api/v1/courier/orders/active');
    if (r.data is List) return List<dynamic>.from(r.data as List);
    return const [];
  }

  Future<void> updateOrderStatus({required int orderId, required String status}) async {
    await _dio.patch('/api/v1/courier/orders/$orderId', data: {'status': status});
  }

  /// Firma atamasını kabul → `courier_accepted`
  Future<void> acceptAssignment({required int orderId}) async {
    await _dio.post('/api/v1/courier/orders/$orderId/accept-assignment');
  }

  /// Firma atamasını reddet / devir → sipariş «hazır» havuzuna, firmaya uyarı
  Future<void> declineAssignment({required int orderId, required String reason}) async {
    await _dio.post('/api/v1/courier/orders/$orderId/decline-assignment', data: {'reason': reason});
  }

  Future<void> postLocation({required double lat, required double lng}) async {
    await _dio.post('/api/v1/courier/location', data: {'latitude': lat, 'longitude': lng});
  }
}

