# Production deployment: Vercel + EC2 + Nginx + DuckDNS

This application uses a bearer token in the `Authorization` header. The frontend is on Vercel and the API is on a separate DuckDNS origin, so no cross-site cookies or Sanctum SPA cookie configuration are required.

## Before the first deployment

1. Point your DuckDNS hostname (for example, `api.example.duckdns.org`) at the EC2 public IP. Install the DuckDNS updater using its official instructions. An Elastic IP is still recommended so an EC2 stop/start does not create an outage.
2. In the EC2 security group, allow TCP 80 and 443 from the internet, and SSH (22) only from your own fixed IP. Do **not** allow TCP 3306, PHP-FPM, or Redis from the internet.
3. Install Nginx, PHP 8.4 with the extensions required by Laravel (`curl`, `mbstring`, `xml`, `zip`, `mysql`, `bcmath`, `intl`), Composer, MariaDB/MySQL, and Certbot.
4. Configure MariaDB to bind to `127.0.0.1` only. Create a dedicated least-privilege application user; do not run Laravel as `root`.

```sql
CREATE DATABASE inventory_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'inventory_app'@'localhost' IDENTIFIED BY 'replace-with-a-unique-long-password';
GRANT ALL PRIVILEGES ON inventory_db.* TO 'inventory_app'@'localhost';
FLUSH PRIVILEGES;
```

Copy `.env.production.example` to the deployment's `.env`, replace every placeholder, and generate the key on the server:

```sh
php artisan key:generate --force
```

Never commit the generated `.env`, database credentials, DuckDNS token, mail credentials, or `APP_KEY`.

## API deployment

Place the checked-out release at `/var/www/inventory-api/current`, then run the following from that directory. Take a database backup before every migration.

```sh
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan migrate --force
php artisan storage:link
php artisan optimize
sudo chown -R www-data:www-data storage bootstrap/cache
sudo find storage bootstrap/cache -type d -exec chmod 775 {} \;
sudo find storage bootstrap/cache -type f -exec chmod 664 {} \;
```

Install `deploy/nginx/inventory-api.conf.example` as `/etc/nginx/sites-available/inventory-api`, replacing the hostname and PHP socket. Enable it, test it, then reload Nginx:

```sh
sudo ln -s /etc/nginx/sites-available/inventory-api /etc/nginx/sites-enabled/inventory-api
sudo nginx -t && sudo systemctl reload nginx
```

Obtain the certificate after the HTTP site is live, then test automatic renewal:

```sh
sudo certbot certonly --webroot -w /var/www/inventory-api/current/public -d api.example.duckdns.org
sudo nginx -t && sudo systemctl reload nginx
sudo certbot renew --dry-run
```

Install the worker template as `/etc/systemd/system/inventory-api-worker.service`, adjust the PHP binary if required, then enable it:

```sh
sudo systemctl daemon-reload
sudo systemctl enable --now inventory-api-worker
```

After each code deploy, repeat `composer install`, `migrate --force`, `optimize`, and `sudo systemctl restart inventory-api-worker`. Run `php artisan about --only=environment` and request `https://api.example.duckdns.org/up` before switching traffic.

## Database resilience

The database lives on a single EC2 disk, so it is not highly available. Schedule a daily `mysqldump --single-transaction` to a directory outside the release path and retain it off-instance (for example, encrypted S3). Also schedule EBS snapshots. Test restoring a dump into a separate database before relying on the backups. Database backups are required before migrations; no deployment command should drop or recreate the production database.

## Vercel configuration

In Vercel → Project Settings → Environment Variables, set this for **Production** and redeploy:

```text
NUXT_PUBLIC_API_BASE=https://api.example.duckdns.org/api
```

For Preview deployments, either use the same value and add those preview origins to `CORS_ALLOWED_ORIGIN_PATTERNS`, or do not authenticate against production from previews. Changes to Vercel environment variables only affect new deployments.

## CORS contract

Set the API's `CORS_ALLOWED_ORIGINS` to the exact Vercel production URL(s), comma-separated. Example:

```text
CORS_ALLOWED_ORIGINS=https://your-project.vercel.app,https://app.example.com
```

For Vercel preview URLs, use a tightly scoped pattern such as:

```text
CORS_ALLOWED_ORIGIN_PATTERNS=#^https://your-project-[a-z0-9-]+\\.vercel\\.app$#
```

After changing `.env`, run `php artisan optimize` again and restart PHP-FPM. Do not use `*` for authenticated API origins. Requests authenticate through the bearer token and intentionally use `credentials: 'omit'`, so browser third-party-cookie policy cannot break API calls.
