# Tek komut: local build + git push + canli guncelleme (kurye.tech)
# Kullanim:  .\gonder.ps1
#            .\gonder.ps1 "Panel duzeltmesi"
# Detay: deploy\CANLI-GUNLUK.txt

$ErrorActionPreference = "Stop"
$Root = $PSScriptRoot
Set-Location $Root

Write-Host "`n=== CANLI DEPLOY -> https://kurye.tech ===" -ForegroundColor Green
Write-Host "Mobil APK ayri: mobile\build-canli.ps1`n" -ForegroundColor DarkGray

$cfgPath = Join-Path $Root "deploy\local.env"
if (-not (Test-Path $cfgPath)) {
    Copy-Item (Join-Path $Root "deploy\local.env.example") $cfgPath
    Write-Host "deploy\local.env olusturuldu. SSH_KEY yolunu kontrol edin." -ForegroundColor Yellow
}
Get-Content $cfgPath | ForEach-Object {
    if ($_ -match '^\s*([^#=]+)=(.*)$') {
        Set-Variable -Name $matches[1].Trim() -Value $matches[2].Trim() -Scope Script
    }
}

$msg = if ($args.Count -gt 0) { $args -join " " } else { "Deploy: $(Get-Date -Format 'yyyy-MM-dd HH:mm')" }
$sshArgs = @()
if ($SSH_KEY -and (Test-Path $SSH_KEY)) {
    $sshArgs = @("-i", $SSH_KEY)
    Write-Host "SSH: anahtar dosyasi kullaniliyor." -ForegroundColor DarkGray
} else {
    Write-Host "SSH: sifre sorulacak (cPanel kurye sifresi)." -ForegroundColor DarkGray
}

Write-Host "`n==> 1/5 Build (composer + npm)" -ForegroundColor Cyan
composer install --no-dev --optimize-autoloader --no-interaction `
    --ignore-platform-req=ext-pcntl --ignore-platform-req=ext-posix | Out-Null
if (-not (Test-Path "node_modules")) { npm ci }
npm run build | Out-Null

Write-Host "==> 2/5 Git push" -ForegroundColor Cyan
git add -A
$status = git status --porcelain
if ($status) {
    git commit -m $msg
    git push origin main
} else {
    Write-Host "Git: degisiklik yok, push atlandi." -ForegroundColor DarkYellow
}

Write-Host "==> 3/5 Paket (tar)" -ForegroundColor Cyan
$tarPath = Join-Path $env:TEMP "kurye-deploy.tar.gz"
if (Test-Path $tarPath) { Remove-Item $tarPath -Force }

$excludeDirs = @("mobile", "node_modules", "tests", "backup", ".git", ".github", ".idea", ".vscode", "deploy\local.env")
$tarArgs = @("-czf", $tarPath)
foreach ($d in $excludeDirs) { $tarArgs += "--exclude=$d" }
$tarArgs += "--exclude=.env"
$tarArgs += "--exclude=*.zip"
$tarArgs += "--exclude=*.exe"
$tarArgs += "--exclude=storage/logs"
$tarArgs += "--exclude=storage/framework/cache"
$tarArgs += "--exclude=storage/framework/sessions"
$tarArgs += "--exclude=storage/framework/views"
$tarArgs += "--exclude=storage/app/public"
$tarArgs += "--exclude=bootstrap/cache/*.php"
$tarArgs += "-C", $Root, "."
& tar @tarArgs

Write-Host "==> 4/5 Sunucuya gonder (scp)" -ForegroundColor Cyan
$remoteTar = "/home/$SSH_USER/kurye-deploy.tar.gz"
& scp @sshArgs $tarPath "${SSH_USER}@${SSH_HOST}:$remoteTar"

Write-Host "==> 5/5 Sunucuda ac + post-deploy" -ForegroundColor Cyan
$remoteScript = @"
set -e
cd $REMOTE_PATH
tar -xzf $remoteTar
rm -f $remoteTar
bash deploy/post-deploy.sh
"@
# Windows CRLF -> LF: uzak bash satir sonundaki \r yuzunden komutlari bozuyordu.
$remoteScript = $remoteScript -replace "`r", ""
if (-not (& ssh @sshArgs "${SSH_USER}@${SSH_HOST}" $remoteScript)) {
    Write-Host "`nSSH basarisiz." -ForegroundColor Red
    Write-Host "- id_rsa parolali: once .\deploy\ssh-agent-bir-kez.ps1" -ForegroundColor Yellow
    Write-Host "- veya deploy\local.env icinde SSH_KEY satirini kaldirin (cPanel sifresi)" -ForegroundColor Yellow
    exit 1
}

Remove-Item $tarPath -Force -ErrorAction SilentlyContinue
Write-Host "`nTamam: https://kurye.tech/ ve GitHub guncellendi." -ForegroundColor Green
