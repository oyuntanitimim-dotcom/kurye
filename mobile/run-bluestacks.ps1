# BlueStacks / benzeri Android emülatörde canlı API ile test
# 1) BlueStacks acik olsun
# 2) Ayarlar > Gelismis > Android Debug Bridge (ADB) = Acik

$ErrorActionPreference = "Stop"
Set-Location $PSScriptRoot

$adb = "$env:LOCALAPPDATA\Android\Sdk\platform-tools\adb.exe"
if (-not (Test-Path $adb)) {
    $adb = "adb"
}

Write-Host "ADB cihazlari..." -ForegroundColor Cyan
& $adb devices

$connected = & $adb devices | Select-String "device$"
if (-not $connected) {
    Write-Host ""
    Write-Host "BlueStacks gorunmuyor. Deneyin:" -ForegroundColor Yellow
    Write-Host "  & `"$adb`" connect 127.0.0.1:5555"
    Write-Host "  & `"$adb`" connect 127.0.0.1:5556"
    Write-Host ""
    & $adb connect 127.0.0.1:5555 2>$null
    & $adb connect 127.0.0.1:5556 2>$null
    & $adb devices
}

Write-Host ""
Write-Host "Canli API: https://kurye.tech" -ForegroundColor Green
Write-Host "flutter run baslatiliyor..." -ForegroundColor Cyan
flutter run
