# CargoTrack Kenya

A PHP and MySQL cargo tracking system based on the CargoTrack Kenya proposal. It supports customer accounts, staff-managed shipment records, status and movement history, delivery confirmation, optional browser GPS coordinates, and CSV reports.

## Local setup with XAMPP

1. Start Apache and MySQL from the XAMPP Control Panel.
2. Import `schema.sql` using phpMyAdmin or the MySQL command-line client.
3. Review `config.php`. The defaults match a local XAMPP installation with MySQL on `127.0.0.1`, database `cargo_track`, user `root`, and an empty password. Set environment variables `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, and `APP_BASE_URL` to override them. For a deployment at the web root, set `APP_BASE_URL` to `/`.
4. From the project directory, create the first administrator using `C:\xampp\php\php.exe createAdmin.php`. The script is CLI-only and refuses to create another administrator if one already exists.
5. Open `http://localhost/isaac/` and sign in. Create staff and customer accounts on the Users page.

Use PHP 8.0 or later with PDO MySQL and MySQL 8 or MariaDB. The browser's current-location button requires user permission and a secure context (HTTPS or localhost). Coordinates are optional and are saved only when explicitly requested.

## Roles and access

- **Administrator:** manages staff/customer accounts, registers and updates cargo, and downloads organization-wide reports.
- **Staff:** registers and updates shipments and downloads organization-wide reports.
- **Customer:** views only shipments assigned to their account and downloads a customer-scoped report.

Customer self-registration creates customer accounts only. Administrators are provisioned through the CLI script; additional staff/customer accounts are created by an administrator.

## Deployment notes

The GPS feature reads device coordinates in the browser; it does not connect to a GPS tracker or IoT device. The app does not include a cloud hosting provider, external notification service, or customs/warehouse workflow. Before public deployment, configure strong database credentials, HTTPS, production error logging, database backups, and hosting-specific environment variables. Do not expose a development `config.php` with production credentials.
