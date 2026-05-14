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

### Konfigürasyon

`lib/core/config/app_config.dart` içinde:

- `apiBaseUrl` (örn. `http://127.0.0.1:8000`)
- `tilesUrlTemplate` (örn. `https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png`)

