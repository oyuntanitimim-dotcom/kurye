import 'package:kurye_mobile/core/http/api_client.dart';

class RestaurantOrdersApi {
  RestaurantOrdersApi(this._api);

  final ApiClient _api;

  Future<List<dynamic>> list() async {
    final r = await _api.dio.get('/api/v1/restaurant/orders');
    if (r.data is List) return List<dynamic>.from(r.data as List);
    return const [];
  }

  Future<Map<String, dynamic>> show(int orderId) async {
    final r = await _api.dio.get('/api/v1/restaurant/orders/$orderId');
    final data = r.data;
    if (data is! Map) throw Exception('Invalid order detail response');
    return Map<String, dynamic>.from(data);
  }

  Future<void> requestCourier(int orderId) async {
    await _api.dio.post('/api/v1/restaurant/orders/$orderId/request-courier');
  }

  Future<void> accept(int orderId) async {
    await _api.dio.post('/api/v1/restaurant/orders/$orderId/accept');
  }

  Future<void> preparing(int orderId) async {
    await _api.dio.post('/api/v1/restaurant/orders/$orderId/preparing');
  }

  Future<void> ready(int orderId) async {
    await _api.dio.post('/api/v1/restaurant/orders/$orderId/ready');
  }

  Future<void> cancel(int orderId) async {
    await _api.dio.post('/api/v1/restaurant/orders/$orderId/cancel');
  }

  Future<Map<String, dynamic>> customers({String? q, DateTime? dateFrom, DateTime? dateTo}) async {
    final qp = <String, dynamic>{};
    if ((q ?? '').trim().isNotEmpty) qp['q'] = q!.trim();
    if (dateFrom != null) qp['date_from'] = _fmt(dateFrom);
    if (dateTo != null) qp['date_to'] = _fmt(dateTo);
    final r = await _api.dio.get('/api/v1/restaurant/customers', queryParameters: qp);
    final data = r.data;
    if (data is! Map) throw Exception('Invalid customers response');
    return Map<String, dynamic>.from(data);
  }

  Future<Map<String, dynamic>> products({String? q, String status = 'all'}) async {
    final qp = <String, dynamic>{};
    if ((q ?? '').trim().isNotEmpty) qp['q'] = q!.trim();
    qp['status'] = status;
    final r = await _api.dio.get('/api/v1/restaurant/products', queryParameters: qp);
    final data = r.data;
    if (data is! Map) throw Exception('Invalid products response');
    return Map<String, dynamic>.from(data);
  }

  Future<Map<String, dynamic>> categories({String? q}) async {
    final qp = <String, dynamic>{};
    if ((q ?? '').trim().isNotEmpty) qp['q'] = q!.trim();
    final r = await _api.dio.get('/api/v1/restaurant/categories', queryParameters: qp);
    final data = r.data;
    if (data is! Map) throw Exception('Invalid categories response');
    return Map<String, dynamic>.from(data);
  }

  Future<Map<String, dynamic>> reportsSummary({DateTime? dateFrom, DateTime? dateTo}) async {
    final qp = <String, dynamic>{};
    if (dateFrom != null) qp['date_from'] = _fmt(dateFrom);
    if (dateTo != null) qp['date_to'] = _fmt(dateTo);
    final r = await _api.dio.get('/api/v1/restaurant/reports/summary', queryParameters: qp);
    final data = r.data;
    if (data is! Map) throw Exception('Invalid reports response');
    return Map<String, dynamic>.from(data);
  }

  String _fmt(DateTime d) {
    final m = d.month.toString().padLeft(2, '0');
    final day = d.day.toString().padLeft(2, '0');
    return '${d.year}-$m-$day';
  }
}

