# Canlı API (https://kurye.tech) ile release APK
Set-Location $PSScriptRoot
Write-Host "Canli API: https://kurye.tech" -ForegroundColor Cyan
flutter build apk --release --dart-define=API_ENV=production
if ($LASTEXITCODE -eq 0) {
    Write-Host ""
    Write-Host "APK: build\app\outputs\flutter-apk\app-release.apk" -ForegroundColor Green
    Write-Host "Once local ile giris yaptiysaniz: Profil > Cikis, sonra canli hesapla girin." -ForegroundColor Yellow
}
