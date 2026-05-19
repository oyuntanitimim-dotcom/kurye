# id_rsa parolaliysa bir kez calistirin; sonra gonder.ps1 sifre sormaz.
# Yonetici PowerShell gerekebilir: Start-Service ssh-agent

$ErrorActionPreference = "Stop"
$key = "C:\xampp\htdocs\kurye\id_rsa"

if (-not (Test-Path $key)) {
    Write-Host "Anahtar yok: $key" -ForegroundColor Red
    exit 1
}

$svc = Get-Service ssh-agent -ErrorAction SilentlyContinue
if ($svc) {
    if ($svc.Status -ne "Running") {
        Set-Service ssh-agent -StartupType Manual
        Start-Service ssh-agent
    }
    ssh-add $key
    Write-Host "Tamam. Simdi: cd C:\xampp\htdocs\kurye ; .\gonder.ps1 `"mesaj`"" -ForegroundColor Green
} else {
    Write-Host "ssh-agent yok. Alternatif: deploy\local.env icinde SSH_KEY satirini silin;" -ForegroundColor Yellow
    Write-Host "gonder.ps1 cPanel sifresini sorar." -ForegroundColor Yellow
}
