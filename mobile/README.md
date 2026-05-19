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
| **Canlı (varsayılan)** | `flutter run` veya `.\build-canli.ps1` |
| **Local Laravel** | `.\run-local.ps1` veya `.\build-local.ps1` |
| **Özel URL** | `--dart-define=API_BASE_URL=https://...` |

Varsayılan API: **https://kurye.tech** (giriş ekranında kontrol edin).

### BlueStacks

1. BlueStacks’i aç → **Ayarlar → Gelişmiş → Android Debug Bridge (ADB)** = Açık  
2. Canlı ile çalıştır: `.\run-bluestacks.ps1`  
3. Sadece APK kur: `.\build-canli.ps1` sonra `.\install-bluestacks.ps1`  

ADB bağlanmazsa: `adb connect 127.0.0.1:5555` veya `5556`

Canlıya geçince eski local oturumu karışmasın diye uygulamadan **çıkış yapıp** canlı hesapla tekrar giriş yapın.

`lib/core/config/app_config.dart` — `API_ENV=production` veya `API_BASE_URL` ile derleme zamanında ayarlanır.

