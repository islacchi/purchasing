# Purchasing — Procurement Management System

A Laravel 12 application that runs the procurement cycle end to end: **Projects → Prep Lists → Request for Quotation → Quotation tracking → Purchase Orders → Receiving**, plus the supplier / procuring-entity directory, price-index tracking, and settings & privileges. Built for Philippine government-style procurement but works for any institution.

> **Status:** actively developed. The procurement chain (Projects → Preplist → Quotation → Purchase Order → Receiving) is functional and database-backed. Settings′ audit log and backup pages, and the Price Index module, still render sample data. See [Project status](#project-status) for an exact, honest breakdown.

---

## What this system includes

| Module | What it does | Status |
|--------|--------------|--------|
| **Projects** | List / create / edit projects, award amounts, delivery period, item fulfillment tracking, plus live delivery notifications | ✅ Database-backed, working |
| **Prep Lists** | Compile one or more Project items into a prep list with minimum shelf-life requirements | ✅ Database-backed, working |
| **Generate RFQ** | Pick quoted/unquoted items per supplier, generate `.xlsx` RFQ files from a letterhead template (single file or ZIP for multiple suppliers) | ✅ Database-backed, working, tested |
| **Quotation** | Track supplier quotations (grouped by prep list), upload the supplier's returned `.xlsx` to extract prices/brand/expiry per item, flag short-dated offers per project | ✅ Database-backed, working |
| **Purchase Order** | Create a PO from an awarded quotation (draft), send it, receive lines (full qty per line), cancel with reason, admin-only delete (draft/cancelled), download PO as `.xlsx` | ✅ Database-backed, working |
| **Entities** | Directory of procuring entities (DOH offices, PGH, …) and suppliers with contact details | ✅ Database-backed, working |
| **Notifications (bell)** | Live delivery-deadline alerts derived from due dates + project fulfillment progress | ✅ Real, data-driven |
| **Price Index** | Per-item supplier price comparison across quotations | 🚧 Sample data only — not wired to the database |
| **Settings → Privileges** | Administrator-only user management (create/edit/deactivate accounts, roles) | ✅ Working |
| **Settings → Logs** | Activity/log viewer | 🚧 Placeholder (no audit-log middleware yet) |
| **Settings → Backup** | Database backup download | 🚧 Placeholder (hardcoded sample rows) |

---

## Tech stack

| Layer | Technology |
|-------|------------|
| Framework | Laravel 12 (PHP 8.2+) |
| Database | MySQL configured via `.env` (tests run on in-memory SQLite) |
| Auth | Laravel session guard with database-backed roles |
| Frontend | Blade + Tailwind CSS v4 + Vite |
| Excel | Raw OOXML workbook generation via built-in `ZipArchive` (see below) |
| Storage | Laravel `local` disk (`storage/app/private/quotations/`) for uploaded supplier files |

---

## End-to-end flow

```
Projects (items + quantities)
        │
        ▼
Prep Lists (compile items across projects, set min. shelf life)
        │
        ▼
Generate RFQ (per supplier; .xlsx report from the RFQ letterhead template)
        │
        ▼
Supplier returns the completed file → Quotation tracker → prices read in
        │
        ▼
Proceed to PO → PO created (draft, PO-YYYY-NNNN) → Excel PO downloaded
        │
        ▼
Send to supplier → lines received per line item → PO auto-moves Sent → Partially received → Completed
```

Operational notes:

- **RFQ numbers** come from the shared `number_sequences` table (`RFQ-YYYY-NNNN`), likewise `PL-YYYY-NNNN` for prep lists and `PO-YYYY-NNNN` for purchase orders.
- **Excel files** are generated manually (no PhpSpreadsheet — it is incompatible with the local missing `ext-gd`). The RFQ/PO templates live in `resources/templates/` and are verified against the generator XML.
- **Supplier uploads** are validated against the RFQ template's header layout before parsing; random `.xlsx` files are rejected.

---

## Project status

Legend: ✅ done · 🚧 in progress / not started.

### Done
- [x] **Authentication** — username/email + password, account activation, session inactivity & absolute lifetime enforcement, throttled login, clear login-error messages
- [x] **Entities** — procuring entities and suppliers: list/search/create/edit/delete, profile pages
- [x] **Projects** — list/search/create/edit/delete, delivery due-date tracking, per-item fulfillment status, progress bar, soft-delete
- [x] **Prep Lists** — list/search/create/edit/delete, item compilation with source quantities, per-project expiry/shelf-life rules
- [x] **RFQ Excel generation** — letterhead template, dynamic item rows, `.xlsx` download, ZIP for multiple suppliers
- [x] **Quotation** — database-backed tracker (grouped by prep list), supplier-file upload with strict template validation, per-item price/brand/expiry extraction, per-project expiry-vs-delivery color coding, short-dated warning, delete the uploaded file (doesn't delete the entry)
- [x] **Purchase Order** — create from awarded quotation (proportional allocation across project sources), draft → Sent → Partially received → Completed, cancel with reason, admin-only delete of draft/cancelled POs, Excel download on generation
- [x] **Delivery notifications** — header bell feed from due dates + fulfillment progress (red: due ≤ 2 days or overdue, amber: due ≤ 7 days or never started and due ≤ 14 days)
- [x] **Data-integrity guards** — a project cannot be deleted while sourced into a prep list; a prep list cannot be deleted while referenced by a quotation; a quotation cannot be deleted once quoted (upload removal is separate)
- [x] **Settings → Privileges** — admin-only user/role management with live search
- [x] **Tests** — 119 passing (696 assertions) on every module above

### Not yet done
- [ ] **Price Index** — wiring the price-comparison page to real quotation lines (all rendering is sample data)
- [ ] **PDF export** — both PO and RFQ have a “Download PDF” button but it is not wired (no PDF library in the project yet)
- [ ] **Settings → Logs** — audit logging middleware/table; page currently prints placeholder rows
- [ ] **Settings → Backup** — real database backup (page hardcodes sample filenames)
- [ ] **Partial receipts** — receiving is all-or-nothing per line; `purchase_order_receipts` already has `received_qty`, `lot_no`, `expiry_date`, but the UI doesn't capture them
- [ ] **PO re-download** — the PO Excel is produced once at creation; there's no way to re-download it from the detail page
- [ ] **PO tracking reference numbers** — PO only references the quotation; PO→project links via allocations are not yet surfaced on the PO detail page
- [ ] **Notifications** are computed on every page view; there's no persisted notification log or read state
- [ ] **Activity log** — created but not written (see above)
- [ ] **PhpSpreadsheet migration** — if `ext-gd` is enabled later, the manual workbook generator can be replaced
- [ ] **Fix stale temp files** — root contains `fix_rfq.php` and other one-off scripts that were used during development

### Known fragility worth addressing
- Project editing **deletes and re-creates** `project_items` rows, which invalidates any `preplist_item_sources` pointing at them (foreign-key violation risk). Not a crash today because prep-list edits go through their own flow, but it deserves a proper merge/update strategy.
- Quoted quotations linked to a deleted prep list cannot be uploaded to / sent to PO — the UI shows "this quotation's prep list has been deleted" but the data is unrecoverable except by restoring the prep list row (`withTrashed`).

---

## Security & access control

| Question | Answer |
|----------|--------|
| Who can log in? | Any active account with a role; `admin` / `admin` seeded by `AdminUserSeeder` (change before production) |
| How do roles map? | `Administrator` → full access including Settings and PO delete; `Procurement Officer` → create/manage POs and workflows; `Viewer` → read-only |
| What does Settings gate? | `access-settings` gate (administrator only) |
| Session lifetime | Inactivity + absolute lifetime enforced by custom middleware |
| Login throttling | `throttle:6,1` on the login POST route |

---

## Installation

```bash
# 1. Clone
git clone https://github.com/islacchi/purchasing.git
cd purchasing

# 2. Install dependencies
composer install
npm install

# 3. Configure
cp .env.example .env
php artisan key:generate

# 4. Migrate + seed
php artisan migrate
php artisan db:seed

# 5. Build frontend & serve
npm run build
php artisan serve
```

### Default accounts (change after first login)

| Name | Email | Role | Password |
|------|-------|------|----------|
| `admin` | admin@example.com | Administrator | `admin` |
| `user1` | user1@example.com | Procurement Officer | `user1` |
| `user2` | user2@example.com | Viewer | `user2` |

---

## Testing

```bash
composer run test          # or: php artisan test
```

Every suite feature-tests through `RefreshDatabase` on in-memory SQLite. Current baseline: **119 passed / 696 assertions**. Quotation upload tests patch worksheet XML inside the generated workbook to simulate supplier submissions.

---

## Layout of the repository

```
purchasing/
├── app/
│   ├── Http/Controllers/       # Auth, Entities, Projects, Preplist, Quotation, PurchaseOrder, Settings
│   ├── Models/                 # 18 models (ProcuringEntity, Project, ProjectItem, Preplist(+Item/Source),
│   │                           #   Quotation(+Item), PurchaseOrder(+Item/Receipt/Allocation), User, Role, …)
│   ├── Support/                # RfqQuotationWorkbook, PurchaseOrderWorkbook, QuotationWorkbookParser,
│   │                           #   DeliveryNotifications
│   └── Providers/              # access-settings gate; view composer feeds the bell icon
├── database/
│   ├── migrations/             # 24 migrations covering the whole domain
│   └── seeders/                # AdminUserSeeder (admin / user1 / user2)
├── resources/
│   ├── templates/              # RFQ-Template.xlsx and PO-Template.xlsx (letterhead documents)
│   └── views/                  # Blade modules mirroring the sidebar
├── docs/                       # Procurement-flow PDFs; sample RFQ/PO artifacts
├── routes/web.php              # All routes (login/logout public; rest behind auth + session.active)
└── tests/Feature/              # Feature suites per module
```

---

## Documentation

- `docs/Procurement-Flow-Project-A.pdf` — end-to-end flow for a sample project
- `docs/Sample-Request-for-Quotation-RFQ-2026-0042.pdf|.xlsx`
- `docs/Sample-Purchase-Order-PO-2026-0117.pdf|.xlsx`

These describe the intended workflow; historical PDFs may reference earlier UI iterations.

---

## License

The Laravel framework is open-sourced under the [MIT license](https://opensource.org/licenses/MIT).
