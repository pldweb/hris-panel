# 🚀 Server & CI/CD Setup Guide — HRIS

Panduan lengkap yang perlu disiapkan sebelum CI/CD berjalan.

---

## 1. Persiapan VPS Server

### Install Docker & Docker Compose

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install Docker
curl -fsSL https://get.docker.com | sh

# Add user to docker group (replace 'deploy' with your username)
sudo usermod -aG docker deploy

# Verify
docker --version
docker compose version
```

### Buat User Deploy (recommended, jangan pakai root)

```bash
sudo adduser deploy
sudo usermod -aG docker deploy
sudo usermod -aG sudo deploy
```

### Setup SSH Key Authentication

Di **komputer lokal** kamu:

```bash
# Generate SSH key pair (jika belum punya)
ssh-keygen -t ed25519 -C "hris-deploy" -f ~/.ssh/hris_deploy

# Copy public key ke server
ssh-copy-id -i ~/.ssh/hris_deploy.pub deploy@YOUR_SERVER_IP

# Test koneksi
ssh -i ~/.ssh/hris_deploy deploy@YOUR_SERVER_IP
```

### Buat Directory di Server

```bash
# SSH ke server
ssh deploy@YOUR_SERVER_IP

# Buat project directories
mkdir -p ~/hris-api
mkdir -p ~/hris-fe
```

### Login ke GHCR di Server

```bash
# Generate Personal Access Token (PAT) di GitHub:
# Settings → Developer Settings → Personal Access Tokens → Tokens (classic)
# Scope: read:packages

echo "YOUR_GITHUB_PAT" | docker login ghcr.io -u YOUR_GITHUB_USERNAME --password-stdin
```

---

## 2. Copy Production Files ke Server

### Backend

```bash
# Copy docker-compose dan env ke server
scp docker-compose.prod.yml deploy@YOUR_SERVER_IP:~/hris-api/
scp .env.example deploy@YOUR_SERVER_IP:~/hris-api/.env

# SSH ke server dan edit .env
ssh deploy@YOUR_SERVER_IP
nano ~/hris-api/.env
# ↑ Edit semua values untuk production!
```

### Frontend

```bash
scp docker-compose.prod.yml deploy@YOUR_SERVER_IP:~/hris-fe/

# SSH ke server
ssh deploy@YOUR_SERVER_IP
nano ~/hris-fe/docker-compose.prod.yml
# ↑ Edit DOCKER_IMAGE sesuai nama repo GitHub kamu
```

---

## 3. Setup GitHub Secrets

Untuk **SETIAP repository** (hris-api-main dan hris-fe-main), set secrets di:
**Repository → Settings → Secrets and variables → Actions → New repository secret**

### Secrets yang WAJIB (kedua repo)

| Secret Name | Value | Contoh |
|---|---|---|
| `SERVER_HOST` | IP address VPS | `103.xxx.xxx.xxx` |
| `SERVER_USER` | SSH username | `deploy` |
| `SERVER_SSH_KEY` | Private key SSH (isi file `~/.ssh/hris_deploy`) | `-----BEGIN OPENSSH PRIVATE KEY----- ...` |
| `SERVER_PORT` | SSH port | `22` |

### Secrets tambahan BACKEND

| Secret Name | Value | Contoh |
|---|---|---|
| `PROJECT_PATH_BACKEND` | Path project di server | `~/hris-api` |

### Secrets tambahan FRONTEND

| Secret Name | Value | Contoh |
|---|---|---|
| `PROJECT_PATH_FRONTEND` | Path project di server | `~/hris-fe` |
| `VITE_API_BASE_URL` | URL API production | `https://api.yourdomain.com/api/v1` |

---

## 4. Setup .env Production (Backend)

Buat file `.env` di server `~/hris-api/.env`:

```env
APP_NAME=HRIS
APP_ENV=production
APP_KEY=base64:GENERATE_THIS
APP_DEBUG=false
APP_URL=https://api.yourdomain.com

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=hris_api
DB_USERNAME=admin
DB_PASSWORD=STRONG_PASSWORD_HERE
DB_ROOT_PASSWORD=STRONG_ROOT_PASSWORD_HERE

REDIS_HOST=redis
REDIS_PORT=6379

CACHE_STORE=redis
QUEUE_CONNECTION=database
SESSION_DRIVER=database

SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://meilisearch:7700
MEILISEARCH_KEY=STRONG_MEILI_KEY_HERE
```

> **Generate APP_KEY:**
> ```bash
> # Di lokal, jalankan:
> php artisan key:generate --show
> # Copy hasil-nya ke .env di server
> ```

---

## 5. Update Nama Image Docker

Edit file berikut sesuai nama GitHub org/username kamu:

### Backend `docker-compose.prod.yml`
```yaml
# Ganti 'your-org' dengan GitHub username/org kamu
image: ghcr.io/YOUR_GITHUB_USERNAME/hris-api-main:latest
```

### Frontend `docker-compose.prod.yml`
```yaml
image: ghcr.io/YOUR_GITHUB_USERNAME/hris-fe-main:latest
```

---

## 6. Cara Deploy

### Deploy Otomatis (CI/CD)

```bash
# Push ke branch main → auto deploy
git push origin main

# Atau buat tag → auto deploy
git tag v1.0.0
git push origin v1.0.0
```

### Deploy Manual (jika perlu)

```bash
# SSH ke server
ssh deploy@YOUR_SERVER_IP

# Backend
cd ~/hris-api
docker compose -f docker-compose.prod.yml pull
docker compose -f docker-compose.prod.yml up -d

# Frontend
cd ~/hris-fe
docker compose -f docker-compose.prod.yml pull
docker compose -f docker-compose.prod.yml up -d
```

### Cek Status

```bash
# Lihat container status
docker compose -f docker-compose.prod.yml ps

# Lihat logs
docker compose -f docker-compose.prod.yml logs -f web

# Lihat migration status
docker compose -f docker-compose.prod.yml exec web php artisan migrate:status
```

---

## 7. Setup Reverse Proxy (Optional, Recommended)

Jika mau pakai domain, install **Nginx** atau **Caddy** di host level sebagai reverse proxy:

```bash
# Install Caddy (auto SSL)
sudo apt install -y debian-keyring debian-archive-keyring apt-transport-https
curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/gpg.key' | sudo gpg --dearmor -o /usr/share/keyrings/caddy-stable-archive-keyring.gpg
curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/debian.deb.txt' | sudo tee /etc/apt/sources.list.d/caddy-stable.list
sudo apt update
sudo apt install caddy
```

Contoh **Caddyfile** (`/etc/caddy/Caddyfile`):

```
api.yourdomain.com {
    reverse_proxy localhost:8000
}

app.yourdomain.com {
    reverse_proxy localhost:80
}
```

```bash
sudo systemctl restart caddy
```

---

## Checklist Ringkasan

- [ ] VPS sudah ready dengan Docker & Docker Compose
- [ ] User `deploy` sudah dibuat dengan akses Docker
- [ ] SSH key pair sudah di-generate
- [ ] GitHub Secrets sudah diset di kedua repository
- [ ] `.env` production sudah dibuat di server (`~/hris-api/.env`)
- [ ] Nama Docker image di `docker-compose.prod.yml` sudah disesuaikan
- [ ] GHCR login sudah dilakukan di server
- [ ] (Optional) Reverse proxy sudah di-setup untuk domain
