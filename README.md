# TheapKa Online API (`theapka-api`)

Backend API for **TheapKa Online**, a digital wedding invitation and wedding management platform for Cambodian couples.

This API serves three distinct consumer surfaces from a single unified backend:
1. **Public Guest Interface**: Zero-auth endpoints for viewing personalized digital invitations, submitting RSVPs, and posting wedding wishes.
2. **Couple Web App (`theapka-user`)**: Authenticated application for wedding couples to manage their wedding schedule, invitations, guest lists, QR codes, and gift ledger (ចំណងដៃ).
3. **Administration Platform (`theapka-admin`)**: Platform operations interface for Staff (`admin`) and Super Administrators (`super_admin`).

---

## 1. Tech Stack

| Component | Choice | Details |
|---|---|---|
| **Framework** | Laravel 13.33.0 | PHP 8.5 / 8.3 compatible |
| **Authentication** | Laravel Sanctum | First-party SPA token authentication |
| **Authorization** | `spatie/laravel-permission` | Role-based & fine-grained permission control |
| **Database** | MySQL 8 (Production) / SQLite (Testing) | Strict foreign keys and indexing |
| **Image Processing** | Intervention Image 3.9 | EXIF stripping and thumbnail processing |
| **QR Code Engine** | Simple-QRCode & Endroid QR Code | SVG and Data URI vector rendering |
| **Payment Drivers** | Extensible Driver Interface | Bakong KHQR and ABA PayWay drivers |
| **Testing** | Pest 5.2.1 | Feature, Isolation, Ledger, and Unit suites |
| **Static Analysis** | Larastan 3.12 (PHPStan 2.2 Level 5) | Zero-error clean analysis |

---

## 2. Architecture & Design Principles

### 2.1 Multi-Tenancy via Global `WeddingScope`
- Tenancy isolation is enforced at the Eloquent Model layer using `App\Models\Scopes\WeddingScope`.
- Automatically applied to `Wedding`, `Schedule`, `GuestGroup`, `Guest`, `Invitation`, `Wish`, `Checkin`, `GiftRecord`, and `Media`.
- **User role execution**: When a couple is authenticated, all queries are automatically scoped to weddings they own or are members of.
- **Admin role execution**: The scope automatically bypasses for staff accounts, but any access to private couple resources (`guests.view`, `gifts.view`) requires explicit Spatie permission and is written to `audit_logs`.
- Verified by automated tests in `tests/Feature/WeddingIsolationTest.php`.

### 2.2 The Gift Ledger (ចំណងដៃ) — Critical Business Rules
The gift ledger manages real monetary gifts recorded live at Cambodian wedding receptions:
1. **Append-Only Immutability**:
   - `App\Observers\GiftRecordObserver` throws a `RuntimeException` whenever an `updating` or `deleting` event fires on `GiftRecord`.
   - There is strictly **NO** `PUT`, `PATCH`, or `DELETE` route defined for `/api/user/gifts`, for any user or administrator role.
2. **Idempotent Synchronization**:
   - Every `POST /api/user/gifts` requires a client-generated UUIDv4 (`client_uuid`).
   - If an entry with that `client_uuid` already exists, the API returns the existing record with `HTTP 200`, preventing duplicates during offline-sync retries.
3. **Signed Corrections, Never Edits**:
   - Mistakes are rectified by submitting a new row with `entry_type: "correction"` and `corrects_id` referencing the original record.
   - The correction specifies the signed adjustment (`amount`), matching the currency of the original gift.
4. **Strict Currency Separation**:
   - `GiftLedgerService::totalsFor(Wedding $wedding)` groups totals by currency.
   - **KHR (Cambodian Riel) and USD (US Dollars) are NEVER summed together.**
   - Money is stored as `decimal(15,2)` in database columns.

### 2.3 Roles & Permissions Hierarchy
- **`user`**: Couple account scoped to their wedding workspace.
- **`admin`**: Staff operator with platform visibility. Admin can **NEVER** view, modify, suspend, or delete a `super_admin` account, and can **NEVER** be assigned Super Admin-only permissions.
- **`super_admin`**: Platform owner with full administrative authority.
- **Self-Lockout Protection**: The API rejects any action (disable, demote, delete) that would leave zero active `super_admin` accounts in the platform (`AdminPolicy::disableOrDelete`).

### 2.4 Staff Audit Logging
- `App\Http\Middleware\LogAdminAction` automatically records every write operation executed under `/api/admin/*` and `/api/super-admin/*`.
- Access to private couple data (`guests.view` and `gifts.view`) is explicitly audited as read events.
- Audit logs are append-only; no update or deletion endpoints exist.

### 2.5 Payment Provider Security
- `App\Services\PaymentService` coordinates payments via driver implementations (`KhqrDriver`, `AbaPayWayDriver`).
- **Provider secrets (API keys, merchant secrets) are write-only and are NEVER returned in any API response body.**

---

## 3. API Contract Reference

Base URL: `/api`

### 3.1 Standard Response Envelopes

#### Success Envelope:
```json
{
  "data": { ... },
  "meta": {
    "page": 1,
    "per_page": 20,
    "total": 54,
    "last_page": 3
  }
}
```

#### Validation Error Envelope (`422 Unprocessable Entity`):
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email has already been taken."]
  }
}
```

#### Error Envelopes:
- `401 Unauthorized`: Unauthenticated or expired Sanctum token.
- `403 Forbidden`: `{"message": "Action rejected / Unauthorized."}`
- `404 Not Found`: `{"message": "Resource not found."}`
- `503 Service Unavailable`: `{"message": "The service is currently undergoing scheduled maintenance."}`

### 3.2 Endpoint Summary

| Group | Method | Endpoint | Description |
|---|---|---|---|
| **Auth** | `POST` | `/api/auth/login` | Authenticate with email or phone |
| | `POST` | `/api/auth/register` | Register couple and initialize wedding workspace |
| | `POST` | `/api/auth/logout` | Revoke active Sanctum token |
| | `GET` | `/api/auth/me` | Current user profile, role, and permissions |
| **Couple** | `GET` | `/api/user/dashboard` | Aggregated wedding stats (guests, gifts, days left) |
| | `GET/PUT` | `/api/user/wedding` | Wedding profile and groom/bride details |
| | `CRUD` | `/api/user/schedules` | Wedding ceremony milestones timeline |
| | `CRUD` | `/api/user/guest-groups` | Guest categories and VIP groups |
| | `CRUD` | `/api/user/guests` | Guest management with unique tokens |
| | `POST` | `/api/user/guests/import/preview` | Preview CSV with duplicate detection |
| | `POST` | `/api/user/guests/import/commit` | Commit validated CSV rows |
| | `GET/PUT` | `/api/user/invitation` | Digital invitation configuration |
| | `POST` | `/api/user/invitation/publish` | Publish wedding invitation |
| | `POST` | `/api/user/invitation/unpublish` | Revert invitation to draft |
| | `GET` | `/api/user/qr-code/guest/{id}` | Vector QR code for personalized guest link |
| | `GET` | `/api/user/qr-code/wedding` | Vector QR code for general invitation link |
| | `GET/POST` | `/api/user/gifts` | Idempotent gift ledger entry (NO edit/delete) |
| | `GET` | `/api/user/gifts/summary` | Distinct KHR & USD gift totals |
| | `PUT` | `/api/user/profile` | Update profile information |
| | `PUT` | `/api/user/password` | Update account password |
| **Admin** | `GET` | `/api/admin/dashboard` | Platform metrics & stats |
| | `GET/POST`| `/api/admin/users[/:id]` | User management (suspend, reactivate, reset) |
| | `GET/POST`| `/api/admin/weddings[/:id]` | Wedding moderation (suspend, restore, archive) |
| | `GET` | `/api/admin/weddings/:id/guests` | View couple guest list (`guests.view` audited) |
| | `GET` | `/api/admin/weddings/:id/gifts-summary` | View couple gifts (`gifts.view` audited) |
| | `CRUD` | `/api/admin/templates` | Invitation template catalogue |
| | `POST` | `/api/admin/payments/:id/verify` | Verify manual bank transfer payment |
| | `POST` | `/api/admin/payments/:id/refund` | Refund payment with reason |
| | `GET` | `/api/admin/reports/:type` | Revenue, user, and subscription reports |
| | `GET` | `/api/admin/reports/:type/export` | Stream CSV export of report data |
| **Super Admin** | `GET/POST`| `/api/super-admin/admins[/:id]` | Staff account management (self-lockout protected) |
| | `GET/PUT` | `/api/super-admin/roles` | Role-permission matrix |
| | `GET/PUT` | `/api/super-admin/settings` | System-wide configuration |
| | `GET/PUT` | `/api/super-admin/payment-config`| Configure Bakong & ABA gateways (secrets masked) |
| | `GET/PUT` | `/api/super-admin/maintenance` | Toggle system maintenance mode (503) |
| | `GET/POST`| `/api/super-admin/backups` | Database snapshot creation & restore |
| | `GET` | `/api/super-admin/audit-logs` | Query platform audit log trail |
| **Public** | `GET` | `/api/public/invitation/{slug}` | Generic invitation (no authentication required) |
| | `GET` | `/api/public/invitation/{slug}/{token}` | Personalized invitation (marks opened_at) |
| | `GET` | `/api/public/invitation/{slug}/wishes` | List visible wishes |
| | `POST` | `/api/public/invitation/{token}/rsvp` | Submit RSVP response (`attending_count <= seats`) |
| | `POST` | `/api/public/invitation/{token}/wish` | Post wedding blessing / wish |
| | `POST` | `/api/public/payments/webhook/{driver}` | Signature-verified payment webhook |

---

## 4. Getting Started

### 4.1 Prerequisites
- PHP >= 8.2 (tested up to PHP 8.5)
- Composer
- SQLite or MySQL 8

### 4.2 Installation
```bash
# Install dependencies
composer install

# Environment setup
cp .env.example .env
php artisan key:generate

# Database migration & seeding
php artisan migrate:fresh --seed
```

### 4.3 Pre-Seeded Accounts
Running `php artisan db:seed` automatically provisions accounts for testing all three tiers:

| Role | Email | Password | Scope |
|---|---|---|---|
| **Super Admin** | `superadmin@theapka.com` | `Password123!` | Complete platform ownership |
| **Admin** | `admin@theapka.com` | `Password123!` | Platform operations (no couple private data by default) |
| **Couple** | `couple@theapka.com` | `Password123!` | Wedding couple with initialized workspace |

---

## 5. Verification & Testing

### 5.1 Automated Test Suite (Pest)
Run the full test suite (49 tests, 284 assertions):
```bash
./vendor/bin/pest
```

Run specific test modules:
```bash
# Multi-tenancy isolation
./vendor/bin/pest tests/Feature/WeddingIsolationTest.php

# Gift Ledger immutability & idempotency
./vendor/bin/pest tests/Feature/GiftLedgerTest.php

# Role hierarchy & self-lockout guards
./vendor/bin/pest tests/Feature/RoleAccessTest.php

# Public guest invitation endpoints
./vendor/bin/pest tests/Feature/Public/PublicInvitationTest.php

# Couple user workflow
./vendor/bin/pest tests/Feature/User/UserWorkflowTest.php

# Admin moderation & audit logging
./vendor/bin/pest tests/Feature/Admin/AdminWorkflowTest.php

# Super Admin security & maintenance
./vendor/bin/pest tests/Feature/SuperAdmin/SuperAdminWorkflowTest.php
```

### 5.2 Static Code Analysis (Larastan)
Run Larastan Level 5 static analysis:
```bash
./vendor/bin/phpstan analyse --debug --memory-limit=2G
```
Result: **`[OK] No errors`**.

---

## 6. Live API Specification
The full OpenAPI 3.0 specification is available at:
- File: `openapi.yaml`
- API Endpoint: `GET /api/docs.yaml`
# theapka-backend-api
