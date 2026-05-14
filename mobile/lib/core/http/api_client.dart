import 'package:dio/dio.dart';
import 'package:kurye_mobile/core/auth/auth_store.dart';

class ApiClient {
  ApiClient({required String baseUrl, required AuthStore authStore})
      : _authStore = authStore,
        _dio = Dio(BaseOptions(baseUrl: baseUrl)) {
    _dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) {
          final token = _authStore.token;
          if (token != null && token.isNotEmpty) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          options.headers['Accept'] = 'application/json';
          handler.next(options);
        },
      ),
    );
  }

  final Dio _dio;
  final AuthStore _authStore;

  Dio get dio => _dio;

  Future<Map<String, dynamic>> login({required String email, required String password}) async {
    final r = await _dio.post('/api/v1/auth/login', data: {
      'email': email,
      'password': password,
    });
    final data = r.data;
    if (data is! Map) throw Exception('Invalid login response');
    return Map<String, dynamic>.from(data);
  }

  Future<Map<String, dynamic>> me() async {
    final r = await _dio.get('/api/v1/me');
    final data = r.data;
    if (data is! Map) throw Exception('Invalid me response');
    return Map<String, dynamic>.from(data);
  }

  Future<Map<String, dynamic>> appConfig() async {
    final r = await _dio.get('/api/v1/app-config');
    final data = r.data;
    if (data is! Map) throw Exception('Invalid app-config response');
    return Map<String, dynamic>.from(data);
  }
}

