# Mobil uygulama (Flutter) çalıştırma notu

Bu repo içinde mobil uygulama `mobile/` klasöründedir.

## Ön koşul

- Flutter SDK (stable)
- Android Studio (Android emülatörü) / iOS için Xcode (macOS)

## Backend’i çalıştır

Bu proje Laravel olduğu için önce API’yi ayağa kaldır:

```bash
cd c:\xampp\htdocs\kurye
php artisan serve --host=127.0.0.1 --port=8000
```

## Mobil uygulamada API base URL ayarı

`mobile/lib/core/config/app_config.dart` içinden:

- `apiBaseUrl`: lokal geliştirmede genelde `http://127.0.0.1:8000`

> Not: Android emülatör kullanıyorsan host makineye erişim için bazen `http://10.0.2.2:8000` gerekir.

## Android (emülatör/cihaz) çalıştırma

```bash
cd c:\xampp\htdocs\kurye\mobile
flutter pub get
flutter run
```

## Web (Chrome) çalıştırma (opsiyonel)

```bash
cd c:\xampp\htdocs\kurye\mobile
flutter pub get
flutter run -d chrome
```

Eğer Windows’ta Chrome için “Temp altında Cookie/Preferences yazılamıyor / erişim engellendi (errno=5)” hatası alırsan (bizde oldu), Chrome profil klasörünü projeye sabitleyerek çalıştır:

```bash
cd c:\xampp\htdocs\kurye\mobile
mkdir .chrome-profile
flutter run -d chrome --web-browser-flag="--user-data-dir=%cd%\.chrome-profile"
```

Bu projede base URL otomatik seçilir:

- Web/Chrome: `http://127.0.0.1:8000`
- Android emülatör/BlueStacks: `http://10.0.2.2:8000`

İstersen manuel override edebilirsin:

```bash
flutter run -d chrome --dart-define=API_BASE_URL=http://127.0.0.1:8000
```

Eğer Windows’ta Chrome için “Temp altında Cookie/Preferences yazılamıyor / erişim engellendi (errno=5)” gibi hata alırsan:

- Cursor/Terminal’i **normal kullanıcı** ile aç (Admin/korumalı klasör bazen sorun çıkarır)
- Antivirus / Controlled folder access engeli varsa Flutter’ın Temp dizinine yazmasını engelliyor olabilir
- Çözüm için Flutter’ı kapatıp tekrar dene; Temp altında kilitli kalan `flutter_tools_*` klasörleri bazen bu hatayı tetikler

## Referans

Daha detaylı kısa doküman: `mobile/README.md`

