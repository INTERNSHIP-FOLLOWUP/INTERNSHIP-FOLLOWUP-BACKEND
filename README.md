# Internship Follow-up System — Backend

Laravel 12 backend API for managing student internships, worklogs, evaluations, and reporting.

---

## Setup Commands

```bash
# Install PHP dependencies
composer install

# Copy environment file (if not exists)
cp .env.example .env

# Generate application key
php artisan key:generate

# Run database migrations
php artisan migrate

# Seed the database with all required data
php artisan db:seed

# Or seed individual seeders:
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=AdminUserSeeder
php artisan db:seed --class=BatchSeeder
php artisan db:seed --class=TutorSeeder
php artisan db:seed --class=CompanySeeder
php artisan db:seed --class=StudentUserSeeder
php artisan db:seed --class=InternshipAssignmentSeeder

# Seed batches via API (alternative)
curl -X POST http://localhost:8000/api/admin/batches/seed

# Install & build frontend assets
npm install
npm run build
```

**One-command setup:**
```bash
composer run setup
```

---

## Development Commands

```bash
# Start all dev servers (Laravel + Queue + Logs + Vite)
composer run dev

# Or start individually:
php artisan serve                          # Laravel dev server (localhost:8000)
php artisan queue:listen --tries=1         # Queue worker
php artisan pail                           # Log viewer
npm run dev                                # Vite HMR

# Run tests
composer run test
# or
php artisan test
```

---

## Database Commands

```bash
# Run migrations
php artisan migrate

# Rollback last migration batch
php artisan migrate:rollback

# Rollback all migrations and re-run
php artisan migrate:fresh

# Fresh migrate + seed
php artisan migrate:fresh --seed

# Create a new migration
php artisan make:migration create_example_table
```

---

## Model / Controller / Other Artisan Commands

```bash
# Create a model with migration, controller, etc.
php artisan make:model Example -mc

# Create a controller
php artisan make:controller Api/ExampleController

# Create a custom Artisan command
php artisan make:command SendReportEmails

# Create a mail class
php artisan make:mail ReportGenerated

# Create a notification
php artisan make:notification InternshipAssigned

# Create an event + listener
php artisan make:event InternshipCompleted
php artisan make:listener SendCompletionNotification --event=InternshipCompleted

# Create a form request
php artisan make:request StoreInternshipRequest

# Create a resource
php artisan make:resource InternshipResource

# Create a seeder
php artisan make:seeder ExampleSeeder

# Create a factory
php artisan make:factory ExampleFactory --model=Example
```

---

## Report & Export Endpoints

All report endpoints are protected by `auth:sanctum` + `role:admin`.

### General Reports

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/reports` | JSON report data (supports filters) |
| GET | `/api/admin/reports/export/pdf` | Download PDF report |
| GET | `/api/admin/reports/export/excel` | Download Excel report (.xlsx) |

**Available filters:** `batch_id`, `company_id`, `tutor_id`, `status`, `date_from`, `date_to`, `department`, `min_duration`, `max_duration`

### Batch Exports

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/batches/{batch}/export/pdf` | Download batch student list as PDF |
| GET | `/api/admin/batches/{batch}/export/excel` | Download batch student list as Excel |

### Example Requests

```bash
# Get report as JSON with filters
curl -H "Authorization: Bearer YOUR_TOKEN" \
  "http://localhost:8000/api/admin/reports?batch_id=1&status=Completed"

# Download PDF report
curl -H "Authorization: Bearer YOUR_TOKEN" \
  "http://localhost:8000/api/admin/reports/export/pdf" \
  --output report.pdf

# Download Excel report
curl -H "Authorization: Bearer YOUR_TOKEN" \
  "http://localhost:8000/api/admin/reports/export/excel" \
  --output report.xlsx

# Download batch student PDF
curl -H "Authorization: Bearer YOUR_TOKEN" \
  "http://localhost:8000/api/admin/batches/1/export/pdf" \
  --output batch-students.pdf
```

---

## API Routes Overview

### Public Auth
| Method | Endpoint |
|--------|----------|
| POST | `/api/register` |
| POST | `/api/login` |
| POST | `/api/forgot-password` |
| POST | `/api/reset-password` |

### Authenticated (auth:sanctum)
| Method | Endpoint(s) |
|--------|-------------|
| GET/POST | `/api/user`, `/api/logout` |
| CRUD | `/api/worklogs` |
| CRUD | `/api/company/profile` |
| CRUD | `/api/evaluations` |
| CRUD | `/api/issues` |

### Admin (role:admin)
| Method | Endpoint(s) |
|--------|-------------|
| CRUD | `/api/admin/users`, `/api/admin/batches`, `/api/admin/companies`, `/api/admin/students`, `/api/admin/assignments`, `/api/admin/worklogs` |
| GET | `/api/admin/dashboard` |
| GET | `/api/admin/reports` + `/export/pdf`, `/export/excel` |

---

## Packages Installed

| Package | Purpose |
|---------|---------|
| `barryvdh/laravel-dompdf` | PDF generation |
| `maatwebsite/excel` | Excel export |
| `laravel/sanctum` | API authentication |
| `laravel/tinker` | REPL playground |
| `laravel/pail` | Log viewer |
| `vite` + `tailwindcss` | Frontend build |

---

## Useful Composer Scripts

```bash
composer run setup    # Full project setup (install, env, key, migrate)
composer run dev      # Start all dev servers concurrently
composer run test     # Run test suite
```

---

## Requirements

- PHP ^8.2
- Composer
- Node.js & npm
- Database (SQLite, MySQL, or PostgreSQL — configured in `.env`)
