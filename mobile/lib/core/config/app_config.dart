class AppConfig {
  const AppConfig({
    required this.apiBaseUrl,
    required this.tilesUrlTemplate,
    required this.tilesAttribution,
  });

  final String apiBaseUrl;
  final String tilesUrlTemplate;
  final String tilesAttribution;

  static const String _defaultTilesUrlTemplate =
      'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
  static const String _defaultTilesAttribution = 'OpenStreetMap contributors';

  /// Öncelik: `--dart-define=API_BASE_URL=...` → aksi halde güvenli varsayılanlar.
  ///
  /// Web/Chrome: `http://127.0.0.1:8000`
  /// Android emülatör/BlueStacks: çoğunlukla `http://10.0.2.2:8000`
  factory AppConfig.fromEnvironment({
    required bool isWeb,
  }) {
    const envApi = String.fromEnvironment('API_BASE_URL');

    final apiBaseUrl = (envApi.trim().isNotEmpty)
        ? envApi.trim()
        : (isWeb ? 'http://127.0.0.1:8000' : 'http://10.0.2.2:8000');

    return AppConfig(
      apiBaseUrl: apiBaseUrl,
      tilesUrlTemplate: _defaultTilesUrlTemplate,
      tilesAttribution: _defaultTilesAttribution,
    );
  }

  /// Emülatör / aynı makine (PC’de Flutter run).
  factory AppConfig.localhost() => const AppConfig(
        apiBaseUrl: 'http://127.0.0.1:8000',
        tilesUrlTemplate: _defaultTilesUrlTemplate,
        tilesAttribution: _defaultTilesAttribution,
      );

  /// Aynı Wi‑Fi’deki gerçek telefon / BlueStacks → PC’deki Laravel.
  /// `php artisan serve --host=0.0.0.0 --port=8000` veya Apache/XAMPP erişilebilir olmalı.
  /// XAMPP’i sadece :80 ile kullanıyorsan URL’yi `http://192.168.1.121` yap.
  factory AppConfig.lanPc() => const AppConfig(
        apiBaseUrl: 'http://192.168.1.121:8000',
        tilesUrlTemplate: _defaultTilesUrlTemplate,
        tilesAttribution: _defaultTilesAttribution,
      );

  AppConfig copyWith({
    String? apiBaseUrl,
    String? tilesUrlTemplate,
    String? tilesAttribution,
  }) {
    return AppConfig(
      apiBaseUrl: apiBaseUrl ?? this.apiBaseUrl,
      tilesUrlTemplate: tilesUrlTemplate ?? this.tilesUrlTemplate,
      tilesAttribution: tilesAttribution ?? this.tilesAttribution,
    );
  }
}

