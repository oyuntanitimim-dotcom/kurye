# Local Laravel (emulator: 10.0.2.2:8000) ile release APK
Set-Location $PSScriptRoot
Write-Host "Local API: emulator 10.0.2.2:8000 (php artisan serve gerekli)" -ForegroundColor Cyan
flutter build apk --release
if ($LASTEXITCODE -eq 0) {
    Write-Host ""
    Write-Host "APK: build\app\outputs\flutter-apk\app-release.apk" -ForegroundColor Green
}
