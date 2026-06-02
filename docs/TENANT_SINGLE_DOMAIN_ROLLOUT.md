# Tek-domain tenant (kurye şirketi) rollout notları

Bu doküman, tek domain (örn. `kurye.tech`) altında birden çok kurye şirketi (tenant) ile çalışan sistem için minimum güvenli rollout / test adımlarını içerir.

## Temel prensipler
- **Tenant**: `firms` tablosundaki kurye şirketidir.
- **Ayrım anahtarı**: çoğu tabloda zaten `firm_id` kullanılır.
- **Güvenlik**: hem controller seviyesinde hem middleware ile “firmalar arası erişim” engellenir (defense-in-depth).
- **Silmek yerine pasifleme**: firma pasife alınır, kullanıcıların erişimi otomatik kapanır.

## Deploy sonrası zorunlu komutlar
Canlıda (uygulama dizininde):

```bash
php artisan migrate --force
php artisan optimize:clear
```

## Staging / canary test senaryosu (minimum)
1. **Giriş**:
   - Firma yöneticisi hesabı ile giriş → kendi paneline yönlenir.
   - Restoran hesabı ile giriş → restoran paneline yönlenir.
   - Kurye hesabı ile giriş → kurye paneline yönlenir.
2. **Tenant context doğrulama**:
   - Firma panelinde operasyon snapshot / raporlar → sadece kendi firması verisini görür.
   - Restoran panelinde sipariş listesi → sadece kendi restoranı.
3. **Çapraz erişim denemesi**:
   - URL’de farklı `order/courier/restaurant` id’leri denenince 403.
4. **Pasif firma testi**:
   - İlgili firmayı `inactive` yap.
   - O firmaya bağlı kullanıcılar: web’de logout + login ekranında hata, API’de 403.
5. **Performans / indeks**:
   - `users(firm_id, role_id, status)` indeksinin oluştuğunu doğrula (migration log).

## Rollback stratejisi (minimum)
- Kod rollback → önceki commit’e dön.
- DB migration’ları geri almak zorunda değilseniz, indeks migration’ı zararsızdır (kalabilir).
- Kritik durumda `optimize:clear` ile cache’leri temizleyin.

