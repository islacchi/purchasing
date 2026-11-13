# Purchasing — Procurement Management System

A Laravel 12 application that runs the procurement cycle end to end: **Projects → Prep Lists → Request for Quotation → Quotation tracking → Purchase Orders → Receiving**, plus the supplier / procuring-entity directory, price-index tracking, and settings & privileges. Built for Philippine government-style procurement but works for any institution.

> **Status:** actively developed. The procurement chain (Projects → Preplist → Quotation → Purchase Order → Receiving) is functional and database-backed, with role-based access control (None/View/Manage) enforced server-side and reflected in the UI, and an activity log wired across modules. Role × module matrix, settings, and the admin area are Administrator-only. The Price Index is database-backed and Settings → Backup still renders sample data. See [Project status](#project-status) for an exact, honest breakdown.

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
| **Role-based access control** | Role × module matrix (None / View / Manage) enforced server-side on every module route, admin bypass, greyed/hidden write affordances for view-only roles, shared access-restricted dialog, friendly 403 page | ✅ Working |
| **Price Index** | Per-item supplier price comparison across quotations: latest quote per supplier, requested quantities, best/average/spread/savings, supplier scorecard, monthly trend | ✅ Database-backed, working |
| **Settings → Privileges** | Administrator-only user management **and** the role × module access matrix (None/View/Manage). Administrator rows are locked full access | ✅ Working |
| **Settings → Logs** | Activity/log viewer — real rows written by `ActivityLogger` on every module create/edit/delete, plus user/matrix changes | ✅ Working |
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
- [x] **Price Index** — per-item comparison built from priced quotation lines: latest quote per supplier, requested quantities from project items, best/average/spread/savings, supplier scorecard, real monthly trend with a functional 3/6/12-month filter
- [x] **Delivery notifications** — header bell feed from due dates + fulfillment progress (red: due ≤ 2 days or overdue, amber: due ≤ 7 days or never started and due ≤ 14 days)
- [x] **Data-integrity guards** — a project cannot be deleted while sourced into a prep list; a prep list cannot be deleted while referenced by a quotation; a quotation cannot be deleted once quoted (upload removal is separate)
- [x] **Unique junction indexes** — composite unique constraints on `preplist_items` / `quotation_items` / `purchase_order_items` to prevent duplicate lines; duplicate key violations surface as validation errors
- [x] **Expiry normalization** — supplier `.xlsx` expiry cells (`MM/YYYY`, month names, ISO dates) parsed crash-proof by `App\Support\ExpiryParser`; `quotation_items.expiry` and supplier expiry columns are real `DATE` columns
- [x] **Role-based access control** — `role_module_access` matrix (None / View / Manage) configured from Settings → Privileges; enforced by the `module:{module}` middleware — `GET` requires View, writes require Manage; Administrator is implicit full access and not editable; role lacks View → module hidden from sidebar; writes additionally blocked by middleware (403 page for direct URL hits)
- [x] **Role-aware UI affordances** — view-only roles see inert greyed buttons (Add project / Add entity / Add Supplier / Compile prep list / Edit project / Edit entity) and access-restricted dialogs for blocked actions (Edit prep list, Generate RFQ, PO receive checkboxes, Delete project, quotation document removal for view-only). Seeded defaults: Procurement Officer = Manage everywhere, Viewer = View everywhere
- [x] **Activity logging** — `activity_logs` table + `App\Support\ActivityLogger`; every module's create/edit/delete writes a row (who, what, when, record label), plus user/role-matrix changes from Settings; Settings → Logs viewer is live (module/user filters, date range)
- [x] **Settings → Privileges** — admin-only user/role management with live search **and** the role × module access matrix (changes logged to the activity trail)
- [x] **Tests** — 177 passing (992 assertions) on every module above, including RBAC (`ModuleAccessTest`), viewer-affordance coverage (restricted dialogs, inert buttons, receive checkboxes, variation per role), and price-index aggregation (`PriceIndexTest`)

### Not yet done
- [ ] **PDF export** — both PO and RFQ have a “Download PDF” button but it is not wired (no PDF library in the project yet)
- [ ] **Settings → Backup** — real database backup (page hardcodes sample filenames)
- [ ] **Partial receipts** — receiving is all-or-nothing per line; `purchase_order_receipts` already has `received_qty`, `lot_no`, `expiry_date`, but the UI doesn't capture them
- [ ] **PO re-download** — the PO Excel is produced once at creation; there's no way to re-download it from the detail page
- [ ] **PO tracking reference numbers** — PO only references the quotation; PO→project links via allocations are not yet surfaced on the PO detail page
- [ ] **Notifications** are computed on every page view; there's no persisted notification log or read state
- [ ] **Activity log retention** — the activity trail is unbounded; no pruning/archival policy yet
- [ ] **PhpSpreadsheet migration** — if `ext-gd` is enabled later, the manual workbook generator can be replaced
- [ ] **Fix stale temp files** — root contains `fix_rfq.php` and other one-off scripts that were used during development

### Known fragility worth addressing
- **Project edit hard-fails (500) once any of its items is compiled into a prep list.** `syncItems()` deletes and re-creates `project_items` on every save; `preplist_item_sources.project_item_id` has a restricted FK, so the delete raises an unhandled FK violation and the `PUT projects.update` request 500s. A merge/update strategy (match by identity, update in place, never drop sourced rows) is required — no test covers this path.
- **A quotation whose prep list is missing renders a fixed error banner and cannot be re-uploaded or sent to PO.** This state is only reachable via DB-level preplist deletion or legacy rows predating the delete guard; `PreplistController::destroy` now blocks deletion of sourced prep lists, and `PurchaseOrderController::create` also refuses. Recovery is `Preplist::withTrashed()->find($id)->restore()` — there is no restore affordance in the UI.

---

## Security & access control

| Question | Answer |
|----------|--------|
| Who can log in? | Any active account with a role; `admin` / `admin` seeded by `AdminUserSeeder` (change before production) |
| How do roles map? | `Administrator` → full access including Settings and PO delete (locked in the matrix, not editable); `Procurement Officer` / `Viewer` → access per module configured in Settings → Privileges |
| How is module access configured? | Settings → Privileges → "Role module access" matrix: **None / View / Manage** for Projects, Preplist, Quotation, Price Index, Purchase Order, Entities. Seeded defaults: Officer = Manage everywhere, Viewer = View everywhere. Administrator rows are bypassed on every check and not editable |
| How is the matrix enforced server-side? | The `module:{module}` middleware (`App\Http\Middleware\EnsureModuleAccess`) wraps all six module route groups: `GET`/`HEAD`/`OPTIONS` require **View**, any other verb requires **Manage**. Administrator bypasses. Direct URL hits without access get the friendly 403 page. `Purchase Order → Delete` additionally keeps its admin-only controller guard |
| How is the matrix reflected in the UI? | View-only roles see inert greyed buttons (cursor-not-allowed) for Add/Edit/Delete actions, modules they cannot view are hidden from the sidebar, and blocked actions (Edit prep list, Generate RFQ, PO receive box, quotation removal) open a shared amber access-restricted dialog |
| What does Settings gate? | `access-settings` gate (administrator only) |
| Settings → Logs | Real activity rows: module writes and role-matrix/user changes (`ActivityLogger`), browsable with module/user filters |
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

Seeded module grants: **Procurement Officer → Manage** on all six modules, **Viewer → View** on all six modules, **Administrator** bypasses the matrix entirely (no grant rows). Grants are editable under Settings → Privileges and are logged to the activity trail when changed.

---

## Testing

```bash
composer run test          # or: php artisan test
```

Every suite feature-tests through `RefreshDatabase` on in-memory SQLite. Current baseline: **181 passed / 1045 assertions**. Quotation upload tests patch worksheet XML inside the generated workbook to simulate supplier submissions.

Role-access tests (`ModuleAccessTest`) cover the seeded grants, server-side middleware enforcement on all module routes, the privileges matrix update flow, sidebar visibility, viewer affordances (inert buttons, restricted dialogs, 403 page), and the admin-bypass rule.

---

## Known limitations

Accepted trade-offs in the current build — worth knowing before relying on the numbers:

- **Price Index compares per item, not per project.** The best price, savings estimate, and scorecard reflect the lowest/average quote across all suppliers and projects for that item. They do not decide which supplier each project actually buys from.
- **Requested quantity is an upper bound.** It is the sum of quantities in non-deleted projects; it is not reduced by received PO quantities, stock on hand, or fulfillment status.
- **Savings estimates exclude real-world costs.** They use the requested quantity × (average − best) from current quotes and ignore freight, taxes, lead time, minimum order quantities, unit conversions, and delivery windows. Supplier-stated brand/expiry are shown but not enforced against a project's delivery date.
- **Deleting an uploaded quotation file deletes its priced lines.** The price history derived from those lines cannot be rebuilt from the file afterwards.
- **The trend shows one point per supplier per month** (the latest priced quote within that month). The sparkline compresses the last six monthly best prices; it is not interactive and does not follow the period selector (the selector affects only the trend chart).
- **The Price Index renders the full catalogue on one page.** There is no server-side pagination; filters run client-side, so very large catalogues will load slowly.
- **Chart.js is loaded from the jsdelivr CDN** (4.4.3), not vendored. Without network access the trend chart does not render.
- **Supplier colour is not persisted.** Colours are assigned by sorted supplier order, so adding or removing a supplier can reassign chart colours.
- **Soft-deleted suppliers keep their historical priced lines visible** because the index reads submitted quotation rows. If a supplier's history must remain, restore it under Entities rather than deleting it.
- **Ties count as a win for every tied supplier** in the competitiveness scorecard.

---

## Layout of the repository

```
purchasing/
├── app/
│   ├── Http/Controllers/       # Auth, Entities, Projects, Preplist, Quotation, PurchaseOrder, Settings
│   ├── Http/Middleware/        # EnsureSessionIsActive (window), EnsureModuleAccess (module:{module})
│   ├── Models/                 # 20 models (ProcuringEntity, Project, ProjectItem, Preplist(+Item/Source),
│   │                           #   Quotation(+Item), PurchaseOrder(+Item/Receipt/Allocation),
│   │                           #   RoleModuleAccess, ActivityLog, User, Role, …)
│   ├── Support/                # RfqQuotationWorkbook, PurchaseOrderWorkbook, QuotationWorkbookParser,
│   │                           #   DeliveryNotifications, ModuleAccess, ActivityLogger, ExpiryParser
│   └── Providers/              # access-settings gate; view composer feeds the bell icon
├── database/
│   ├── migrations/             # 29 migrations covering the whole domain (seeded role grants, activity logs,
│   │                           #   unique junction indexes, expiry → DATE columns)
│   └── seeders/                # AdminUserSeeder (admin / user1 / user2 + seeded module grants)
├── resources/
│   ├── templates/              # RFQ-Template.xlsx and PO-Template.xlsx (letterhead documents)
│   └── views/                  # Blade modules mirroring the sidebar; role-aware affordances via canModule()
│       ├── components/         # access-restricted-modal (shared amber dialog)
│       ├── partials/           # restricted-notice (shared amber card), flash-success
│       └── errors/             # 403 — friendly module-access page
├── docs/                       # Procurement-flow PDFs; sample RFQ/PO artifacts
├── routes/web.php              # All routes (login/logout public; rest behind auth + session.active;
│                               #   six module groups additionally behind module:{module})
└── tests/Feature/              # Feature suites per module (incl. ModuleAccessTest for RBAC)
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
