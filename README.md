# AMLO Dashboard - Production Ready

Sistem Monitoring Aktivitas & Kinerja Harian AMLO untuk AMLODashboard Kantor Wilayah.

## Fitur

- **3 Role Access:** Officer, Lead, Head Office
- **Approval Workflow:** Officer submit → Lead review → HO final approve
- **Task Management:** CRUD progress untuk 13 jenis laporan
- **Performance Monitoring:** KPI, scorecard, grafik per wilayah
- **Feedback System:** Lead/HO bisa kasih feedback ke Officer
- **Penugasan:** Lead bisa assign tugas adhoc ke Officer

## Struktur File

```
amlo-dashboard/
├── config/
│   └── database.php          # Koneksi MySQL
├── includes/
│   ├── auth.php              # Session, login, RBAC
│   └── functions.php          # Helper functions
├── pages/
│   ├── login.php             # Login page
│   ├── dashboard.php          # Main dashboard
│   ├── tasks.php             # To-Do list + CRUD
│   ├── laporan.php           # Tracking laporan
│   ├── performa.php          # Performance monitoring
│   ├── officers.php          # Monitoring officer (Lead/HO)
│   ├── wilayah.php            # Monitoring wilayah (HO)
│   ├── assignments.php        # Penugasan (Lead)
│   ├── assessment.php        # Assessment (HO)
│   └── jobdesc.php           # Job description reference
├── api/
│   ├── tasks.php             # Tasks API
│   ├── approvals.php         # Approvals API
│   └── feedback.php          # Feedback API
└── database.sql              # Schema + seed data
```

## Setup Local Development

### 1. Buat Database MySQL

```bash
# Login MySQL
mysql -u root -p

# Buat database
CREATE DATABASE amlo_dashboard;

# Exit
EXIT;
```

### 2. Import Schema

```bash
mysql -u root -p amlo_dashboard < database.sql
```

### 3. Edit Config

Edit `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'amlo_dashboard');
```

### 4. Start PHP Server

```bash
# Navigate to folder
cd amlo-dashboard

# Start PHP built-in server
php -S localhost:8000
```

### 5. Buka Browser

```
http://localhost:8000
```


## Deployment ke Shared Hosting (cPanel)

### 1. Export Database

```bash
mysqldump -u root -p amlo_dashboard > amlo_dashboard.sql
```

### 2. Import di Hosting (phpMyAdmin)

1. Buka phpMyAdmin di cPanel hosting
2. Buat database baru
3. Import `amlo_dashboard.sql`

### 3. Upload Files

Upload semua file ke `public_html/` via:
- FileZilla/WinSCP
- cPanel File Manager
- Git FTP

### 4. Update Config

Edit `config/database.php` dengan credentials hosting:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'hosting_username');
define('DB_PASS', 'hosting_password');
define('DB_NAME', 'your_hosting_db');
```

### 5. Test

Buka `domain.com` di browser

## Tech Stack

- **Backend:** PHP 7.4+ (procedural, no framework)
- **Database:** MySQL 5.7+
- **Frontend:** HTML5, CSS3, Vanilla JavaScript
- **Auth:** Session-based dengan bcrypt password hashing
- **Security:** CSRF protection, prepared statements, RBAC

## API Endpoints

| Method | Endpoint | Fungsi |
|--------|----------|--------|
| POST | /api/tasks.php | Create/update task progress |
| GET | /api/tasks.php | List tasks |
| POST | /api/approvals.php | Approve/reject submission |
| GET | /api/approvals.php | List pending approvals |
| POST | /api/feedback.php | Add feedback |
| GET | /api/feedback.php | Get feedbacks |

## License

© 2026 AMLODashboard - Internal Use Only