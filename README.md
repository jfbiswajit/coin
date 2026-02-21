# Coin

Personal finance PWA built with Laravel + Inertia + Vue 3.

## Deployment

### First time setup (server)

```bash
git clone https://github.com/jfBiswajit/coin /srv/coin
cd /srv/coin
cp .env.example .env
nano .env  # fill in APP_KEY, DB_PASSWORD, DB_ROOT_PASSWORD
docker compose up -d
```

### Deploy changes

**Local machine:**
```bash
docker buildx build --platform linux/amd64 -t jfbiswajit/coin:latest --push .
```

**Server:**
```bash
cd /srv/coin
docker compose pull
docker compose up -d
```

### Useful commands

```bash
# Check container status
docker compose ps

# View logs
docker compose logs app
docker compose logs queue

# Run artisan commands
docker compose exec app php artisan <command>

# Stop all containers
docker compose down
```
