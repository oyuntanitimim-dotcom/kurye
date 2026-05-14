import 'package:kurye_mobile/core/http/api_client.dart';

class CourierStatsApi {
  CourierStatsApi(this._api);

  final ApiClient _api;

  Future<Map<String, dynamic>> summary() async {
    final r = await _api.dio.get('/api/v1/courier/summary');
    final data = r.data;
    if (data is! Map) throw Exception('Invalid summary response');
    return Map<String, dynamic>.from(data);
  }

  Future<Map<String, dynamic>> earnings({String period = 'today'}) async {
    final r = await _api.dio.get('/api/v1/courier/earnings', queryParameters: {
      'period': period,
    });
    final data = r.data;
    if (data is! Map) throw Exception('Invalid earnings response');
    return Map<String, dynamic>.from(data);
  }

  /// Firma: ödenen toplam, bekleyen hakediş, son kapanışlar.
  Future<Map<String, dynamic>> payoutBalance() async {
    final r = await _api.dio.get('/api/v1/courier/payouts/balance');
    final data = r.data;
    if (data is! Map) throw Exception('Invalid payout balance response');
    return Map<String, dynamic>.from(data);
  }
}

