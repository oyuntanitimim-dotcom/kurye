## Kurye SaaS Mobile (Flutter)

Tek uygulama: **Kurye şirketi admin**, **Restoran (firma)** ve **Kurye** rolleri.

### Gereksinimler

- Flutter SDK (stable)
- Android Studio / Xcode (iOS için macOS gerekir)

### Kurulum

```bash
cd mobile
flutter pub get
flutter run
```

### Konfigürasyon (API adresi)

| Ortam | Komut |
|--------|--------|
| **Local** (varsayılan) | `flutter run` veya `.\build-local.ps1` |
| **Canlı** (`https://kurye.tech`) | `.\run-canli.ps1` veya `.\build-canli.ps1` |
| **Özel URL** | `flutter run --dart-define=API_BASE_URL=https://...` |

Canlıya geçince eski local oturumu karışmasın diye uygulamadan **çıkış yapıp** canlı hesapla tekrar giriş yapın.

`lib/core/config/app_config.dart` — `API_ENV=production` veya `API_BASE_URL` ile derleme zamanında ayarlanır.

