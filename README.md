# Multi-Tenant FinTech Loan Management System

A Laravel 13 REST API for managing multi-tenant loan operations with EMI calculation, Stripe payment gateway integration, and reporting.

## Features

- **Multi-Tenancy**: Complete data isolation with global scopes, per-tenant currency/timezone config
- **Auth & Roles**: Sanctum API tokens + Spatie permissions (Super Admin, Admin, Loan Officer, Borrower) with forgot/reset password
- **Borrower Management**: CRUD with soft-delete, restore, full profile management
- **Loan Management**: Create, update, approve (generates EMI schedule), foreclose loans
- **EMI Calculation**: Flat Interest and Reducing Balance calculators
- **Payment Gateway**: Abstracted Stripe integration with full/partial payments, signed secure-pay URLs, webhook handling
- **Dashboard**: Aggregated metrics (borrowers, active loans, outstanding amount, upcoming EMIs)
- **Reports**: Filterable loan reports with Excel and PDF export
- **Notifications**: Database + email notifications for loan approval, EMI due reminders, payment received
- **Activity Logs**: Automatic audit trail for borrower CRUD, loan approval
- **Multi-Currency**: INR, USD, EUR, AED support

## Tech Stack

- **Laravel 13**, PHP 8.3+, MySQL
- **Laravel Sanctum** (API token auth)
- **Spatie Laravel Permission** (roles & permissions)
- **Stripe PHP SDK** (payment gateway)
- **Laravel Excel** (report export)
- **barryvdh/laravel-dompdf** (PDF export)

## Quick Start

```bash
cp .env.example .env
# Edit .env with your MySQL credentials, Stripe keys, and mail config
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan storage:link
php artisan serve
```

## Seed Credentials

| Role          | Email             | Password |
|---------------|-------------------|----------|
| Super Admin   | super@admin.com   | password |
| Admin         | admin@test.com    | password |
| Loan Officer  | officer@test.com  | password |
| Borrower      | borrower@test.com | password |

## API Endpoints

### Auth (Public)
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/login` | Login (rate-limited) |
| POST | `/api/forgot-password` | Request password reset link |
| POST | `/api/reset-password` | Reset password with token |
| POST | `/api/logout` | Logout (authenticated) |
| GET | `/api/me` | Get current user |

### Payments (Public)
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/payments/webhook` | Stripe webhook handler |
| GET | `/api/pay/{emiSchedule}` | Signed secure-pay URL (no auth) |

### Tenants (Super Admin only)
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/tenants` | List tenants |
| POST | `/api/tenants` | Create tenant |
| GET | `/api/tenants/{id}` | Get tenant |
| PUT | `/api/tenants/{id}` | Update tenant |
| DELETE | `/api/tenants/{id}` | Delete tenant |

### Users (manage-tenants or approve-loans permission)
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/users` | List users |
| POST | `/api/users` | Create user |
| GET | `/api/users/{id}` | Get user |
| PUT | `/api/users/{id}` | Update user |
| DELETE | `/api/users/{id}` | Delete user |

### Borrowers
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/borrowers` | List borrowers |
| POST | `/api/borrowers` | Create borrower |
| GET | `/api/borrowers/{id}` | Get borrower |
| PUT | `/api/borrowers/{id}` | Update borrower |
| DELETE | `/api/borrowers/{id}` | Delete borrower |

### Loans
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/loans` | List loans |
| POST | `/api/loans` | Create loan |
| GET | `/api/loans/{id}` | Get loan |
| PUT | `/api/loans/{id}` | Update loan (blocked after approval) |
| DELETE | `/api/loans/{id}` | Delete loan |
| POST | `/api/loans/{id}/approve` | Approve & generate EMI schedule |
| POST | `/api/loans/{id}/foreclose` | Foreclose loan |
| GET | `/api/loans/{id}/emi-schedules` | Get EMI schedule |

### Payments
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/payments` | List payments |
| GET | `/api/payments/{id}` | Get payment |
| POST | `/api/payments/initiate` | Initiate full or partial payment |

### Dashboard
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/dashboard` | Aggregated metrics |

### Reports
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/reports/loans` | Filterable loan report |
| GET | `/api/reports/loans/export` | Export to Excel |
| GET | `/api/reports/loans/export-pdf` | Export to PDF |

### Notifications
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/notifications` | List notifications |
| GET | `/api/notifications/unread-count` | Unread count |
| POST | `/api/notifications/{id}/read` | Mark as read |
| POST | `/api/notifications/read-all` | Mark all as read |

### Activity Logs
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/activity-logs` | List activity logs |

## Postman Collection

See [postman/](postman/) for the complete API collection and environment.

```bash
# Import into Postman:
postman/collection.json
postman/environment.json
```

## Testing

```bash
php artisan test
```

Runs 89 tests covering auth, tenants, borrowers, loans, EMI calculations, payments, webhooks, dashboard, reports, notifications, and activity logs.

## Architecture

```
app/Domains/
├── ActivityLog/     # Automatic audit trail
├── Auth/            # Authentication, users, roles
├── Borrower/        # Borrower CRUD + service layer
├── Dashboard/       # Aggregated metrics
├── Loan/            # Loans, EMI calculators, approval workflow
├── Notification/    # Database + email notifications, EMI reminders
├── Payment/         # Gateway abstraction, Stripe, webhooks
├── Report/          # Reporting + Excel/PDF export
└── Tenant/          # Multi-tenant management

app/Support/Traits/
├── ApiResponse.php  # Consistent JSON response wrapper
├── LogsActivity.php # Automatic activity logging trait
└── TenantScoped.php # Tenant isolation trait
```
