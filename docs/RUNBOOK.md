# RUNBOOK — running and rescuing the Purchasing system

**Audience:** whoever ends up responsible for this app after the person who built it stops touching it.
You do not need to understand Laravel. You need to follow the checklists and respect one rule: **never change the server before making a fresh back-up.**

Everything else is details, and the details are all here.

**Works with:** `README.md` (install & architecture), `docs/Teaching-Guide-Purchasing-System.md` (staff training).

---

## 0. The five things to know up front

1. This app runs entirely on **one machine on the office LAN**. There is no cloud component. If that machine dies, the app is down until it is restored — this is acceptable by design, provided you keep back-ups (see §2).
2. **Nothing in the app requires the internet** (as of 2026-11 the Tailwind CDN was removed; see §7). You can unplug the network cable from the server and the app still works.
3. Upgrades are **optional, not routine**. Do not update PHP, MySQL/MariaDB, XAMPP, or `composer` packages "to stay current" — an unmanaged box is safer frozen. Upgrade only when the hardware itself is replaced (see §4).
4. Every incident follows one shape: **restore from back-up, then verify, then fix.** Never improvise on the only copy of the data.
5. The **database back-up contains only the database.** The uploaded supplier quotation files live in a separate folder and must be backed up separately (see §2).

---

## 1. Where everything lives

| Thing | Path (default layout) |
|---|---|
| Application code | `C:\…\purchasing\` (the repo) |
| Environment (DB password, APP_KEY) | `purchasing\.env` — **not in git; keep a sealed copy with the back-ups** |
| Database | MySQL/MariaDB, database `purchasing_db`, user `root` (localhost-only) |
| DB back-ups (in-app) | `purchasing\storage\app\private\backups\*.sql` (keeps the 15 newest) |
| Uploaded quotation files | `purchasing\storage\app\private\quotations\` |
| Logs | `purchasing\storage\logs\laravel.log` |
| Schedule runner | Windows Task Scheduler or cron: `php artisan schedule:run` every minute (runs `activity:prune` weekly) |

If your layout differs, adjust the paths — but keep the discipline: code, env, DB, uploads, logs.

---

## 2. The weekly 10-minute back-up (all of it, every time)

**Do this every week, on the same day:**

1. Sign in to the app as an Administrator → **Settings → Backup** → **Create backup**.
2. Wait for the new row in the back-up list. Note the filename.
3. Copy to a USB stick / second PC / network share:
   - the newest `.sql` from `storage\app\private\backups\` **and**
   - the **whole folder** `storage\app\private\quotations\` **and**
   - `purchasing\.env` (fresh copy — the APP_KEY never changes, but keep it current)
4. Put the USB in a known drawer. Label it with the date.

> The in-app back-up alone can resurrect the database but **not** the supplier files the procurement cycle ran on. USB with only the database = half a restore.

The in-app list keeps the 15 newest back-ups; the USB keeps everything.

---

## 3. Monthly 2-minute health check

1. Can you open `http://<server-ip>/login` from a different PC? (Checks Apache/MySQL are alive.)
2. On the server: is there at least **10% free disk space**? (`C:\` drive → Properties.)
3. Settings → Logs — does the newest entry look recent? (Confirms the app is running and the schedule is alive.)
4. Is the newest USB back-up younger than **14 days**? If not, make one now.

If any of these fails, see §5.

---

## 4. Replacing the server (the 3–5 year reality)

The one failure you cannot script around is the PC itself dying. When that happens, or when you replace it:

1. Get a replacement machine (any modern Windows PC is fine).
2. Install the **same or newer** XAMPP (PHP ≥ 8.2, MySQL/MariaDB, Apache). Do not chase exact versions.
3. Clone the repo again (or copy the project folder from a working copy).
4. Run `composer install` in the project root. **npm is not needed at runtime** and neither is `npm run build`.
5. Copy back the sealed `.env`.
6. Import the latest `.sql` from §2 into a fresh `purchasing_db` database:
   open **phpMyAdmin** (comes with XAMPP) → select `purchasing_db` → **Import** → choose the file → Go.
   (Alternative: `mysql -u root -p purchasing_db < backup.sql`.)
7. Copy the `quotations\` folder from the USB into `storage\app\private\`.
8. Set the XAMPP Apache document root to `purchasing\public\`, and `APP_URL` in `.env` to the new machine's address (e.g. `http://192.168.0.10:8000`).
9. Start Apache and MySQL. Open the app from another PC, sign in, open one Project and one Purchase Order to confirm the data is intact. Print one RFQ. If those work, you are done.

**Test this drill once a year on a spare machine.** A back-up that has never been restored from is a hope, not a back-up.

---

## 5. Common incidents

**"The app won't open / shows an error page"**
1. Is Apache **and** MySQL running? (XAMPP Control Panel — both green.)
2. Is the disk full? (Free space → if 0, delete old USB-bound `.sql` copies first? No — **never delete the newest one**; delete other old files, or add disk.)
3. Error page in the browser lists a file and line — if it mentions a missing class or extension (e.g. `ZipArchive`), the PHP extension is disabled in `php.ini`; re-enable `extension=zip` and `extension=dom`, restart Apache.
4. Still stuck? Restore the last known-good state per §4. Do not edit code to "try fixing it".

**"Someone restored (or tried to restore) a back-up"**
The Restore button requires typing `RESTORE` and takes a fresh safety snapshot first; administrators stay logged in. If a restore went wrong, restore *that* safety snapshot (it's at the top of the back-up list). Nothing is lost that was ever valid.

**"A quotation is locked and nobody can fix it"**
Known case: the quotation's prep list was soft-deleted. Fix: **Manage → the amber banner → Restore prep list** (it brings the row back). If the role can't, an Administrator can; worst case the DB path `Preplist::withTrashed()->find($id)->restore()` exists via Tinker (`php artisan tinker`). Then editing works again.

**"The login page loads but looks broken (no styling)"**
The self-hosted stylesheet failed to load. First check: **APP_URL in `.env` points at the address the other PCs use** (e.g. `http://192.168.0.10:8000`, not `localhost`) — if it doesn't, every `asset()` URL resolves to the viewer's own machine and icons/logo/stylesheet all break. Second: confirm `public\js\tailwind-play-cdn.js` exists (§7). If the file is missing, copy it from any git clone of the repo — it is committed.

**"Windows Update rebooted the server during office hours"**
MySQL restart-during-write is the only risk; the app is transaction-safe, so no data is corrupted. Move future reboots off-hours (Settings → Windows Update → Active hours) and exclude the project folder and the MySQL `data` directory from anti-virus scanning to prevent file lock-ups.

---

## 6. Security posture, as decided

- The app is served on the **office LAN only, over plain HTTP** — accepted because the office network is physically controlled. If that changes (guest Wi-Fi, shared devices from outside, anything on the 2.4 GHz password on the wall), this decision is void: put the app behind HTTPS with an internal certificate from day one.
- Login is rate-limited (throttle on the login POST) and CSRF-protected; role-based permissions are enforced server-side; a user with a missing module grant cannot perform the action even by guessing the URL.
- Sessions die after **20 minutes idle** and **8 hours absolute** — on a shared front-desk PC this is the primary defense against someone walking up to an unlocked session. People should still click **Sign out**.
- MySQL should listen on `127.0.0.1` only (default in XAMPP). If you ever change the DB to listen on the LAN, set a real password in `.env` **and** in the MySQL user — otherwise anyone on the network owns the data.
- `APP_DEBUG` must stay `false` in `.env` on the real server. If it is `true`, any visitor sees internal paths and SQL on error pages — set it false today.
- `.env` contains the database password and `APP_KEY`. Treat the USB with the back-ups like a spare key: drawer, not desk.

---

## 7. Internet-independence guarantee

As of **2026-11**, the app loads **no external resources**: the Tailwind Play CDN was replaced by a self-hosted copy, committed at `public/js/tailwind-play-cdn.js` (Tailwind browser build **v3.0.0**, SHA-256 `176e894661aa9cdc9a5cba6c720044cbbf7b8bd80d1c9a142a7c24b1b6c50d15`).

- **Never update this file casually.** The UI was written against Tailwind v3 semantics; do not swap in Tailwind v4 or a newer browser build without redesign time.
- The repo is the source of truth (public/js is committed); if the file is lost, `git checkout` restores it.
- Verification: stop the internet connection on the server and browse to the app — everything must look and behave identically. Do this check once after every server change.

---

## 8. What to never do

- Never run `composer update` / `npm update` on the live server "to be safe". Freeze is a feature.
- Never run XAMPP's "upgrade" prompts on a working server without a §2 back-up and a spare machine to test on.
- Never delete `storage\app\private\backups\*.sql` other than via the in-app 15-newest pruning.
- Never edit vendor files or core migrations to "just fix it".
- Never give the USB back-up away; it contains everything the office has ever ordered.

---

*Last reviewed: 2026-11 (after the CDN removal and the lessons from shipping this to the office network). Update the date when you edit this file.*
