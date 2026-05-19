# BlueStacks'e canli APK kur (kurye-canli.apk)
Set-Location $PSScriptRoot

$apk = "build\app\outputs\flutter-apk\kurye-canli.apk"
if (-not (Test-Path $apk)) {
    Write-Host "APK yok. Once: .\build-canli.ps1" -ForegroundColor Red
    exit 1
}

$adb = "$env:LOCALAPPDATA\Android\Sdk\platform-tools\adb.exe"
if (-not (Test-Path $adb)) { $adb = "adb" }

& $adb connect 127.0.0.1:5555 2>$null
& $adb connect 127.0.0.1:5556 2>$null
& $adb devices

Write-Host "Kuruluyor: $apk" -ForegroundColor Cyan
& $adb install -r $apk
if ($LASTEXITCODE -eq 0) {
    Write-Host "Tamam. Uygulamayi BlueStacks'te ac; giris ekraninda https://kurye.tech olmali." -ForegroundColor Green
} else {
    Write-Host "Kurulum basarisiz. BlueStacks acik mi? ADB acik mi?" -ForegroundColor Red
}
