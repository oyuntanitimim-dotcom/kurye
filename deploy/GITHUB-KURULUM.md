# GitHub → kurye.tech otomatik deploy

`main` dalına her `git push` sonrası GitHub Actions:
1. `composer install` + `npm run build` (CI)
2. Dosyaları sunucuya `rsync` ile gönderir (`vendor` + `public/build` dahil)
3. Sunucuda `migrate`, `config:cache`, izinler

`.env` **repoda yok** — sunucudaki `.env` korunur (rsync exclude).

---

## Bir kez: deploy SSH anahtarı

### PC (PowerShell)

```powershell
ssh-keygen -t ed25519 -C "github-deploy-kurye" -f $env:USERPROFILE\.ssh\github_deploy_kurye -N '""'
Get-Content $env:USERPROFILE\.ssh\github_deploy_kurye.pub
```

### Sunucu

cPanel → SSH Keys → public key yapıştır → Authorize  
veya `~/.ssh/authorized_keys` içine ekle.

Test:

```powershell
ssh -i $env:USERPROFILE\.ssh\github_deploy_kurye kurye@kurye.tech "echo OK"
```

---

## Bir kez: GitHub Secrets

Repo: https://github.com/oyuntanitimim-dotcom/kurye  
**Settings → Secrets and variables → Actions → New repository secret**

| Secret | Değer |
|--------|--------|
| `DEPLOY_SSH_KEY` | `github_deploy_kurye` dosyasının **tam içeriği** (private key) |
| `DEPLOY_SSH_HOST` | `kurye.tech` veya sunucu IP |
| `DEPLOY_SSH_USER` | `kurye` |
| `DEPLOY_PATH` | `/home/kurye/public_html` |

İsteğe bağlı: **Settings → Environments → production** oluşturup onay kuralı ekleyebilirsiniz.

---

## Günlük kullanım (local)

```powershell
cd C:\xampp\htdocs\kurye
# kod değişikliği...
git add .
git commit -m "Açıklama"
git push origin main
```

GitHub → **Actions** sekmesinden deploy logunu izleyin (~2–5 dk).

---

## Notlar

- **mobile/** canlıya gitmez (rsync exclude).
- Sunucuda **composer yok** — vendor CI’da üretilir.
- İlk deploy öncesi sunucuda `.env` ve MySQL import hazır olmalı.
- `storage/app` içi yüklemeler rsync ile silinmez (exclude).
