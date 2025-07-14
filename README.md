
# API Project Management

Sistem backend Project Management berbasis **Laravel 12** dengan autentikasi **JWT** dan database **MySQL**. Mendukung fitur multi-workspace, manajemen proyek, tugas, komentar, lampiran, audit log, dan lainnya.

---

## Fitur Utama

- **Autentikasi JWT** (login, register, refresh token)
- **Manajemen Workspace** (multi-workspace, role per workspace)
- **Manajemen Proyek** (CRUD project, status project)
- **Manajemen Tugas** (CRUD task, assignee, prioritas, status, due date)
- **Komentar & Lampiran** pada tugas
- **Audit Log** (tracking perubahan data)
- **Role & Permission** (system role & workspace role)
- **Pencarian & Filter** (project, task, dsb)
- **API RESTful**

## Requirement

- PHP >= 8.1
- Composer
- MySQL >= 8

## Instalasi

1. **Clone repository**
   ```bash
   git clone https://github.com/burhanbur/api-proman.git
   cd api-proman
   ```

2. **Install dependency PHP**
   ```bash
   composer install
   ```

3. **Copy file environment**
   ```bash
   cp .env.example .env
   ```

4. **Generate key aplikasi**
   ```bash
   php artisan key:generate
   ```

5. **Konfigurasi database**
   - Edit `.env` bagian DB_*
   - Contoh:
     ```env
     DB_CONNECTION=mysql
     DB_HOST=127.0.0.1
     DB_PORT=3306
     DB_DATABASE=api_proman
     DB_USERNAME=root
     DB_PASSWORD=yourpassword
     ```

6. **Jalankan migrasi & seeder**
   ```bash
   php artisan migrate --seed
   ```

7. **Jalankan server lokal**
   ```bash
   php artisan serve
   ```

## Konfigurasi JWT

1. **Generate JWT secret**
   ```bash
   php artisan jwt:secret
   ```
2. **Pastikan middleware JWT aktif di route API**

## Struktur Database (Ringkasan)

Lihat detail skema di bawah atau file migrasi.

```text
users, system_roles, workspace_roles, workspaces, workspace_users, template_status, projects, project_status, priorities, tasks, task_assignees, task_activity_logs, comments, attachments, audit_logs
```

## Contoh Endpoint API

| Method | Endpoint                | Keterangan                |
|--------|-------------------------|---------------------------|
| POST   | /api/auth/login         | Login JWT                 |
| POST   | /api/auth/register      | Register user             |
| POST   | /api/auth/refresh       | Refresh token             |
| GET    | /api/projects           | List project              |
| POST   | /api/projects           | Create project            |
| GET    | /api/tasks              | List task                 |
| POST   | /api/tasks              | Create task               |
| ...    | ...                     | ...                       |

> Lihat dokumentasi Swagger/OpenAPI di `/api/documentation`

## Testing

Jalankan seluruh test:
```bash
php artisan test
```

## Deployment

1. **Set environment production di `.env`**
   ```env
   APP_ENV=production
   APP_DEBUG=false
   ```
2. **Optimasi autoload & cache**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   composer install --optimize-autoloader --no-dev
   ```
3. **Pastikan permission storage & cache**
   ```bash
   chmod -R 775 storage bootstrap/cache
   ```
4. **Gunakan web server (Nginx/Apache) & SSL**

## Kontribusi

Pull request & issue sangat terbuka!

## Lisensi

MIT