import 'package:kurye_mobile/core/http/api_client.dart';

class FirmAdminApi {
  FirmAdminApi(this._api);

  final ApiClient _api;

  Future<Map<String, dynamic>> reportsSummary({
    String? preset,
    int? restaurantId,
    String? dateFrom,
    String? dateTo,
    int seriesDays = 14,
  }) async {
    final qp = <String, dynamic>{
      'series_days': seriesDays,
    };
    if (preset != null && preset.isNotEmpty) qp['preset'] = preset;
    if (restaurantId != null) qp['restaurant_id'] = restaurantId;
    if (dateFrom != null && dateFrom.isNotEmpty) qp['date_from'] = dateFrom;
    if (dateTo != null && dateTo.isNotEmpty) qp['date_to'] = dateTo;

    final r = await _api.dio.get('/api/v1/firm/reports/summary', queryParameters: qp);
    final data = r.data;
    if (data is! Map) throw Exception('Invalid reports response');
    return Map<String, dynamic>.from(data);
  }

  Future<Map<String, dynamic>> financeOverview({String preset = 'this_month'}) async {
    final r = await _api.dio.get('/api/v1/firm/finance/overview', queryParameters: {
      'preset': preset,
    });
    final data = r.data;
    if (data is! Map) throw Exception('Invalid finance response');
    return Map<String, dynamic>.from(data);
  }

  Future<Map<String, dynamic>> financeBalances({
    String? dateFrom,
    String? dateTo,
    int? restaurantId,
  }) async {
    final qp = <String, dynamic>{};
    if (dateFrom != null && dateFrom.isNotEmpty) qp['date_from'] = dateFrom;
    if (dateTo != null && dateTo.isNotEmpty) qp['date_to'] = dateTo;
    if (restaurantId != null) qp['restaurant_id'] = restaurantId;
    final r = await _api.dio.get('/api/v1/firm/finance/balances', queryParameters: qp);
    final data = r.data;
    if (data is! Map) throw Exception('Invalid finance balances response');
    return Map<String, dynamic>.from(data);
  }

  Future<Map<String, dynamic>> financeCourierCollections({
    String? dateFrom,
    String? dateTo,
    int? courierId,
  }) async {
    final qp = <String, dynamic>{};
    if (dateFrom != null && dateFrom.isNotEmpty) qp['date_from'] = dateFrom;
    if (dateTo != null && dateTo.isNotEmpty) qp['date_to'] = dateTo;
    if (courierId != null) qp['courier_id'] = courierId;
    final r = await _api.dio.get('/api/v1/firm/finance/courier-collections', queryParameters: qp);
    final data = r.data;
    if (data is! Map) throw Exception('Invalid courier collections response');
    return Map<String, dynamic>.from(data);
  }

  Future<Map<String, dynamic>> financeCourierCollectionDetail({
    required int courierId,
    String? dateFrom,
    String? dateTo,
  }) async {
    final qp = <String, dynamic>{};
    if (dateFrom != null && dateFrom.isNotEmpty) qp['date_from'] = dateFrom;
    if (dateTo != null && dateTo.isNotEmpty) qp['date_to'] = dateTo;
    final r = await _api.dio.get('/api/v1/firm/finance/courier-collections/$courierId/detail', queryParameters: qp);
    final data = r.data;
    if (data is! Map) throw Exception('Invalid courier collection detail response');
    return Map<String, dynamic>.from(data);
  }

  Future<Map<String, dynamic>> financeCourierPayouts({int page = 1, int? courierId}) async {
    final qp = <String, dynamic>{'page': page};
    if (courierId != null) qp['courier_id'] = courierId;
    final r = await _api.dio.get('/api/v1/firm/finance/courier-payouts', queryParameters: qp);
    final data = r.data;
    if (data is! Map) throw Exception('Invalid courier payouts response');
    return Map<String, dynamic>.from(data);
  }

  Future<Map<String, dynamic>> financeCourierPayoutDetail(int settlementId) async {
    final r = await _api.dio.get('/api/v1/firm/finance/courier-payouts/$settlementId');
    final data = r.data;
    if (data is! Map) throw Exception('Invalid courier payout detail response');
    return Map<String, dynamic>.from(data);
  }

  Future<Map<String, dynamic>> financeCourierPayoutCreate({
    required int courierId,
    required String dateFrom,
    required String dateTo,
    required String paymentMethod,
    String? paymentReference,
    String? notes,
    bool includeAllLedger = false,
    bool ordersAllTime = false,
    String? periodPreset,
  }) async {
    final r = await _api.dio.post('/api/v1/firm/finance/courier-payouts', data: {
      'courier_id': courierId,
      'date_from': dateFrom,
      'date_to': dateTo,
      'payment_method': paymentMethod,
      'payment_reference': paymentReference,
      'notes': notes,
      'include_all_ledger': includeAllLedger,
      'orders_all_time': ordersAllTime,
      'period_preset': periodPreset,
    });
    final data = r.data;
    if (data is! Map) throw Exception('Invalid courier payout create response');
    return Map<String, dynamic>.from(data);
  }

  Future<void> financeCourierPayoutVoid(int settlementId) async {
    await _api.dio.post('/api/v1/firm/finance/courier-payouts/$settlementId/void');
  }

  Future<Map<String, dynamic>> financeReconciliation({String? month}) async {
    final qp = <String, dynamic>{};
    if (month != null && month.isNotEmpty) qp['month'] = month;
    final r = await _api.dio.get('/api/v1/firm/finance/reconciliation', queryParameters: qp);
    final data = r.data;
    if (data is! Map) throw Exception('Invalid reconciliation response');
    return Map<String, dynamic>.from(data);
  }

  Future<Map<String, dynamic>> couriers({int page = 1}) async {
    final r = await _api.dio.get('/api/v1/firm/couriers', queryParameters: {'page': page});
    final data = r.data;
    if (data is! Map) throw Exception('Invalid couriers response');
    return Map<String, dynamic>.from(data);
  }

  Future<void> createCourier({
    required String name,
    required String email,
    required String password,
    String? phone,
    String? vehicleType,
    String status = 'active',
  }) async {
    await _api.dio.post('/api/v1/firm/couriers', data: {
      'name': name,
      'email': email,
      'password': password,
      'phone': phone,
      'vehicle_type': vehicleType,
      'status': status,
    });
  }

  Future<void> updateCourier({
    required int courierId,
    required String name,
    required String email,
    String? password,
    String? phone,
    String? vehicleType,
    required String status,
  }) async {
    await _api.dio.put('/api/v1/firm/couriers/$courierId', data: {
      'name': name,
      'email': email,
      'password': (password != null && password.isNotEmpty) ? password : null,
      'phone': phone,
      'vehicle_type': vehicleType,
      'status': status,
    });
  }

  Future<void> setCourierStatus({
    required int courierId,
    required String status,
  }) async {
    await _api.dio.patch('/api/v1/firm/couriers/$courierId/status', data: {
      'status': status,
    });
  }

  Future<Map<String, dynamic>> notifications({int page = 1}) async {
    final r = await _api.dio.get('/api/v1/firm/notifications', queryParameters: {'page': page});
    final data = r.data;
    if (data is! Map) throw Exception('Invalid notifications response');
    return Map<String, dynamic>.from(data);
  }

  Future<void> markNotificationRead(int notificationId) async {
    await _api.dio.post('/api/v1/firm/notifications/$notificationId/mark-read');
  }

  Future<void> markAllNotificationsRead() async {
    await _api.dio.post('/api/v1/firm/notifications/mark-all-read');
  }

  Future<Map<String, dynamic>> restaurants({int page = 1}) async {
    final r = await _api.dio.get('/api/v1/firm/restaurants', queryParameters: {'page': page});
    final data = r.data;
    if (data is! Map) throw Exception('Invalid restaurants response');
    return Map<String, dynamic>.from(data);
  }

  Future<void> createRestaurant({
    required String name,
    required String businessType,
    required String status,
    required String adminName,
    required String adminEmail,
    required String adminPassword,
    String? phone,
    String? address,
  }) async {
    await _api.dio.post('/api/v1/firm/restaurants', data: {
      'name': name,
      'business_type': businessType,
      'status': status,
      'phone': phone,
      'address': address,
      'admin_name': adminName,
      'admin_email': adminEmail,
      'admin_password': adminPassword,
    });
  }

  Future<void> updateRestaurant({
    required int restaurantId,
    required String name,
    required String businessType,
    required String status,
    String? phone,
    String? address,
  }) async {
    await _api.dio.put('/api/v1/firm/restaurants/$restaurantId', data: {
      'name': name,
      'business_type': businessType,
      'status': status,
      'phone': phone,
      'address': address,
    });
  }

  Future<void> setRestaurantStatus({
    required int restaurantId,
    required String status,
  }) async {
    await _api.dio.patch('/api/v1/firm/restaurants/$restaurantId/status', data: {
      'status': status,
    });
  }
}

