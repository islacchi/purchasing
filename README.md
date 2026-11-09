# Purchasing – Procurement Management System

A Laravel-based purchasing / procurement management application for government and institutional procurement workflows. The system covers the end-to-end procurement cycle: projects, preplists, quotations/RFQs, purchase orders, entities (users, procuring entities, suppliers), price index tracking, and settings.

> **Status:** Frontend prototype / work-in-progress. Most pages render with hardcoded dummy data. Database models, migrations, and real controller logic are pending integration (see [Roadmap](#roadmap)).

---

## Tech Stack

| Layer | Technology |
|-------|------------|
| Framework | Laravel 12 (PHP 8.2+) |
| Database | MySQL (configured via `.env`); Laravel supports SQLite/PostgreSQL/SQL Server |
| Auth | Laravel session guard with database-backed administrator account |
| Frontend | Blade templates, Tailwind CSS v4, Vite |
| JavaScript | Axios (via `resources/js/bootstrap.js`) |
| Build Tool | Vite (`vite.config.js`) |
| Testing | PHPUnit |

---

## Project Structure

```
purchasing/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php      # Login/logout
│   │   │   ├── DashboardController.php # Not yet routed
│   │   │   ├── PageController.php      # Renders Blade views by name
│   │   │   ├── SettingsController.php  # Not yet routed
│   │   │   └── Controller.php          # Abstract base
│   ├── Models/
│   │   ├── Role.php                    # User roles
│   │   └── User.php                    # Authenticated user
│   └── Providers/
│       └── AppServiceProvider.php
├── bootstrap/
│   └── app.php                         # Route + middleware registration
├── config/                             # Standard Laravel config files
├── database/
│   ├── migrations/                     # Procurement domain + Laravel support tables
│   ├── factories/                      # UserFactory, RoleFactory
│   └── seeders/                        # DatabaseSeeder, AdminUserSeeder
├── resources/
│   ├── views/
│   │   ├── layouts/app.blade.php       # Main shell: sidebar, header, content
│   │   ├── auth/login.blade.php
│   │   ├── welcome.blade.php
│   │   ├── components/nav-icon.blade.php
│   │   ├── Projects/                   # index, show, form
│   │   ├── Preplist/                   # index, show, form, generate_rfq
│   │   ├── Quotation/                  # index, show
│   │   ├── Purchaseorder/              # index, show, create
│   │   ├── Entities/                   # index, form, show (users / procuring_entity / supplier)
│   │   ├── Priceindex/                 # index
│   │   ├── Settings/
│   │   │   ├── index.blade.php         # Settings dashboard (3 cards)
│   │   │   ├── logs.blade.php          # System logs viewer
│   │   │   ├── privileges.blade.php    # User privilege management
│   │   │   └── backup.blade.php        # Database backup
│   ├── css/app.css                     # Tailwind v4 import
│   └── js/                             # app.js, bootstrap.js
├── routes/
│   ├── web.php                         # All HTTP routes
│   └── console.php                     # Artisan commands
├── tests/                              # PHPUnit unit + feature tests
├── public/index.php
├── composer.json
├── package.json
├── vite.config.js
└── .env
```

---

## Navigation Module Order (Sidebar)

The sidebar navigation in `resources/views/layouts/app.blade.php` is ordered as follows:

1. **Projects** — `route('projects')`
2. **Preplist** — `route('preplist')`
3. **Quotation** — `route('quotation')`
4. **Price Index** — `route('priceindex')`
5. **Purchase Order** — `route('purchase-order')`
6. **Entities** — `route('entities')` (users / procuring entities / suppliers)
7. **Settings** — `route('settings')`

Price Index and Purchase Order were repositioned to appear above Entities and Settings.

---

## Routes Summary

### Public (no authentication required)

| Method | URI | Action | Name |
|--------|-----|--------|------|
| GET | `/` | Redirect to `/login` | — |
| GET | `/login` | `AuthController@showLogin` | `login` |
| POST | `/login` | `AuthController@login` | — |
| POST | `/logout` | `AuthController@logout` | `logout` |

### Authenticated (protected by `fake.auth` middleware)

| Method | URI | View / Action | Name |
|--------|-----|---------------|------|
| GET | `/projects` | `Projects.index` | `projects` |
| GET | `/projects/create` | `Projects.form` | `projects.create` |
| GET | `/projects/{id}` | `Projects.show` | `projects.show` |
| GET | `/projects/{id}/edit` | `Projects.form` | `projects.edit` |
| GET | `/preplist` | `Preplist.index` | `preplist` |
| GET | `/preplist/create` | `Preplist.form` | `preplist.create` |
| GET | `/preplist/{id}` | `Preplist.show` | `preplist.show` |
| GET | `/preplist/{id}/edit` | `Preplist.form` | `preplist.edit` |
| GET | `/quotation/generate` | `Preplist.generate_rfq` | `quotation.generate` |
| GET | `/quotation` | `Quotation.index` | `quotation` |
| GET | `/quotation/{id}` | `Quotation.show` | `quotation.show` |
| GET | `/purchase-order` | `Purchaseorder.index` | `purchase-order` |
| GET | `/purchase-order/create/{rfq}` | `Purchaseorder.create` | `purchase-order.create` |
| GET | `/purchase-order/{id}` | `Purchaseorder.show` | `purchase-order.show` |
| GET | `/entities/{type}/create` | `Entities.form` | `entities.create` |
| GET | `/entities/{type}/{id}/edit` | `Entities.form` | `entities.edit` |
| GET | `/entities/{type}/{id}` | `Entities.show` | `entities.show` |
| GET | `/entities` | `Entities.index` | `entities` |
| GET | `/priceindex` | `Priceindex.index` | `priceindex` |
| GET | `/settings` | `Settings.index` | `settings` |
| GET | `/settings/logs` | `SettingsController@logs` | `settings.logs` |
| GET | `/settings/privileges` | `SettingsController@privileges` | `settings.privileges` |
| GET | `/settings/backup` | `SettingsController@backup` | `settings.backup` |

> **Note:** Most authenticated routes currently use inline closures or `PageController@show` with hardcoded dummy data. Real controllers (e.g., `ProjectController`, `PurchaseOrderController`) are pending implementation.

---

## Installation

```bash
# 1. Clone the repository
git clone https://github.com/islacchi/purchasing.git
cd purchasing

# 2. Install PHP dependencies
composer install

# 3. Install Node.js dependencies
npm install

# 4. Copy environment file and generate key
cp .env.example .env
php artisan key:generate

# 5. Run migrations
php artisan migrate

# 6. Build frontend assets
npm run build

# 7. Serve the application
php artisan serve
```

Or use the provided composer setup script:

```bash
composer run setup
```

---

## Current State & Limitations

- **Authentication:** Uses Laravel's session guard with a database-backed administrator account. Seed the account with `php artisan db:seed` (default credentials: `admin` / `admin`; change before production).
- **Data:** All module views (Projects, Preplist, Quotation, Purchase Order, Entities, Price Index, Settings) use hardcoded PHP arrays (`@php` blocks) instead of database queries.
- **Forms:** All `<form action="#">` submissions are placeholders — no store/update/destroy controller methods exist yet.
- **Database:** MySQL schema and migrations for the procurement domain are in place. Module views still use hardcoded data pending controller integration.

---

## Roadmap

The codebase contains `@TODO` comments throughout indicating the intended next steps:

1. Create Eloquent models for the remaining procurement domain tables.
2. Replace inline closures in `routes/web.php` with real controller methods.
3. Wire up form submissions to controller store/update methods.
4. Add role-based authorization and user management (create/edit/deactivate accounts).
5. Implement PO status transitions (Draft → Sent → Partial → Completed → Cancelled).
6. Connect hardcoded dummy data in views to real Eloquent queries.

---

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
