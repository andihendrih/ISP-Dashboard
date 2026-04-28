# AHNet ISP Dashboard

Dashboard manajemen ISP berbasis **Laravel 10 (PHP 8.2+)** dengan integrasi:

- **FreeRADIUS** (PPPoE & Hotspot — `radcheck` / `radreply` / `radusergroup` / `radgroupreply` / `radacct`)
- **Mikrotik RouterOS** (lewat API `evilfreelancer/routeros-api-php`)
- **SNMP monitoring** (poll IF-MIB, simpan ke `snmp_logs`, grafik realtime/histori)
- **GenieACS NBI** (list ONU, reboot, set SSID, set password WiFi)

UI: Blade + TailwindCSS + Chart.js. Auth: Laravel session-based dengan role
(Admin, NOC, Finance). Web server: Apache di port **8879**.

---

## 1. Stack & Requirements

- Ubuntu 22.04+ (atau Debian 12)
- PHP 8.2+ dengan ekstensi: `mbstring`, `xml`, `curl`, `mysql`, `bcmath`, `gd`, `zip`, `snmp`
- MariaDB / MySQL 10.4+
- Apache 2.4 + `mod_rewrite`
- Composer 2.x
- (Opsional) GenieACS NBI di `:7557`, FreeRADIUS 3.0 di MySQL

---

## 2. Install di Ubuntu (langkah demi langkah)

```bash
# 2.1 PHP 8.2 (Ondrej PPA)
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update
sudo apt install -y php8.2 php8.2-cli php8.2-mbstring php8.2-xml php8.2-curl \
                    php8.2-mysql php8.2-bcmath php8.2-gd php8.2-zip php8.2-snmp \
                    libapache2-mod-php8.2 apache2 mariadb-server unzip git
sudo update-alternatives --set php /usr/bin/php8.2

# 2.2 Composer
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer

# 2.3 Clone repo
sudo mkdir -p /var/www && sudo chown -R $USER:$USER /var/www
cd /var/www
git clone https://github.com/andihendrih/ISP-Dashboard.git isp-dashboard
cd isp-dashboard
composer install --no-dev --optimize-autoloader

# 2.4 Konfigurasi env
cp .env.example .env
php artisan key:generate
# Edit .env -> isi DB app + DB radius + Mikrotik + GenieACS

# 2.5 Database aplikasi (di MariaDB/MySQL)
sudo mysql -e "CREATE DATABASE ahnet_dashboard CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER 'ahnet'@'localhost' IDENTIFIED BY 'change_me'; \
               GRANT ALL ON ahnet_dashboard.* TO 'ahnet'@'localhost'; FLUSH PRIVILEGES;"

# 2.6 Migrate + seed
php artisan migrate --seed
# (Opsional, jika belum ada FreeRADIUS schema — buat juga DB radius + user lalu)
php artisan migrate --database=radius --path=database/migrations/radius

# 2.7 Permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# 2.8 Apache vhost
echo "Listen 8879" | sudo tee -a /etc/apache2/ports.conf
sudo cp docs/apache/isp-dashboard.conf /etc/apache2/sites-available/
sudo a2enmod rewrite headers
sudo a2ensite isp-dashboard.conf
sudo systemctl reload apache2
```

Akses: `http://<server>:8879/login`

Default users (seeder):

| Email                  | Password   | Role    |
|------------------------|------------|---------|
| admin@ahnet.local      | password   | admin   |
| noc@ahnet.local        | password   | noc     |
| finance@ahnet.local    | password   | finance |

> Ganti password setelah login pertama kali.

---

## 3. Koneksi ke FreeRADIUS

`config/database.php` mendefinisikan koneksi `radius` (terpisah dari koneksi
default). Set di `.env`:

```env
RADIUS_DB_HOST=127.0.0.1
RADIUS_DB_PORT=3306
RADIUS_DB_DATABASE=radius
RADIUS_DB_USERNAME=radius
RADIUS_DB_PASSWORD=...
```

Aplikasi akan menggunakan tabel berikut (sudah dibuat oleh paket
`freeradius-mysql`):

| Tabel             | Fungsi                                                |
|-------------------|-------------------------------------------------------|
| `radcheck`        | Cleartext-Password, Simultaneous-Use, Session-Timeout |
| `radreply`        | Mikrotik-Rate-Limit per user                          |
| `radusergroup`    | mapping user ↔ group                                  |
| `radgroupreply`   | rate limit / atribut per group                        |
| `radacct`         | accounting / session log                              |

Snippet `clients.conf` ada di `docs/freeradius/clients.conf.snippet`.
Service `RadiusService` (`app/Services/RadiusService.php`) adalah
**satu-satunya** penulis ke tabel-tabel ini.

---

## 4. Test PPPoE

1. Login sebagai admin → menu **PPPoE → Buat User**.
2. Form akan generate username `ahnet_<nama>#XX` + password acak.
   - Tabel `radcheck` mendapat `Cleartext-Password` & `Simultaneous-Use`.
   - `radusergroup` mendapat baris dengan `groupname` yang dipilih.
   - Jika rate limit diisi: `radreply` mendapat `Mikrotik-Rate-Limit`.
3. Centang **"Push langsung ke Mikrotik"** (pilih device) untuk membuat
   `/ppp/secret` di RouterOS.
4. Test dial dari client / Mikrotik → akan ter-log ke `radacct`. Username
   yang aktif muncul **online** di `User RADIUS`.

CLI cepat:

```bash
# memastikan auth jalan dengan radtest
radtest ahnet_budi#82 'P@ssw0rd9' 127.0.0.1 0 testing123
```

## 5. Test Hotspot Voucher

1. Menu **Hotspot → Generate Voucher**.
2. Default: code 5 huruf, username = password (sesuai spec).
3. Profile/group yang diisi akan jadi `radusergroup.groupname`.
4. Validasi di hotspot Mikrotik:
   - User ketik code → hotspot mengirim Access-Request ke FreeRADIUS.
   - `radacct` akan mencatat sesi.

---

## 6. Mikrotik

Tambahkan device di menu **Mikrotik**. Service akan otomatis ambil
`/system/identity` dan `/system/resource` untuk metadata.

Operasi yang didukung:
- Lihat active session (`/ppp/active`)
- Disconnect user
- Sync user PPPoE (`/ppp/secret/add` atau `set`)
- List & apply profile (`/ppp/profile`)

---

## 7. SNMP Monitoring

- `php artisan snmp:poll` mengambil counter IF-MIB dari semua device aktif.
- Sudah dijadwalkan setiap 5 menit di `app/Console/Kernel.php`. Pastikan
  cron Laravel scheduler aktif:

  ```bash
  * * * * * cd /var/www/isp-dashboard && php artisan schedule:run >> /dev/null 2>&1
  ```

- Menu **SNMP Monitor** menampilkan tabel interface terbaru + grafik
  history per interface (Chart.js).

---

## 8. GenieACS

Set `.env`:
```env
GENIEACS_NBI_URL=http://127.0.0.1:7557
GENIEACS_USERNAME=
GENIEACS_PASSWORD=
```

- `php artisan genieacs:sync` melakukan sync periodik (terjadwal 15 menit).
- Menu **GenieACS** mendukung: refresh, reboot, set SSID, set password WiFi
  (TR-069 setParameterValues).

---

## 9. Cloudflare Zero Trust

Lihat `docs/cloudflared/config.yml`. Aplikasi memakai `APP_URL` dari `.env`
(tidak hardcode `localhost`), dan asset Tailwind/Chart.js dimuat dari CDN
sehingga tidak butuh Vite di server.

---

## 10. Struktur Direktori (highlight)

```
app/
  Console/Commands/        SnmpPollAll, GenieacsSync
  Http/
    Controllers/           Auth, Dashboard, PPPoE, Hotspot, UserManagement,
                           Customer, Mikrotik, SNMP, GenieACS
    Middleware/            EnsureRole
  Models/
    Radius/                Radcheck, Radreply, Radusergroup,
                           Radgroupreply, Radgroupcheck, Radacct
    User, Role, DeviceMikrotik, SnmpLog,
    GenieacsDevice, CustomerProfile, HotspotVoucher, AuditLog
  Services/                RadiusService, MikrotikService,
                           SnmpService, GenieacsService

config/
  ahnet.php                konfigurasi brand + RADIUS + Mikrotik + SNMP + GenieACS
  database.php             koneksi default + koneksi `radius`

database/
  migrations/              tabel aplikasi
  migrations/radius/       skema FreeRADIUS opsional (bila DB radius kosong)
  seeders/                 RoleSeeder, UserSeeder

docs/
  apache/isp-dashboard.conf
  cloudflared/config.yml
  freeradius/clients.conf.snippet

resources/views/
  layouts/app.blade.php    sidebar biru + navbar + jam realtime
  auth, dashboard, pppoe, hotspot, users, customers, mikrotik, snmp, genieacs

routes/web.php             semua route + role middleware
```

---

## 11. Troubleshooting

- **"SQLSTATE 42S02 radcheck not found"** — DB `radius` belum punya skema.
  Jalankan `php artisan migrate --database=radius --path=database/migrations/radius`.
- **Mikrotik connection failed** — pastikan `/ip service api` enabled,
  user punya hak akses, port 8728 (atau 8729 jika SSL) terbuka.
- **SNMP timeout** — naikkan `SNMP_TIMEOUT` di `.env`, pastikan community
  benar dan SNMP service aktif di Mikrotik.
- **GenieACS 401** — set `GENIEACS_USERNAME` / `GENIEACS_PASSWORD`.
