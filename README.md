

## Deploy to InfinityFree from GitHub

This PHP/MySQL app cannot run on GitHub Pages. The `Deploy to InfinityFree` GitHub Actions workflow uploads the site to InfinityFree over FTP when `main` is updated.

1. Create an InfinityFree hosting account and a MySQL database in its control panel. Note the database host, name, username, and password.
2. In phpMyAdmin launched from the control panel, select that database and import the table definitions from `schema.sql`, omitting its initial `CREATE DATABASE` and `USE cargo_track` statements.
3. Create `config.local.php` from `config.local.example.php`, fill in those database values, and leave `app_base_url` set to `/` for the domain root. Upload this file separately to `htdocs` with the InfinityFree File Manager; GitHub intentionally excludes it so credentials never enter the public repository.
4. In GitHub **Settings → Secrets and variables → Actions**, create `FTP_SERVER`, `FTP_USERNAME`, and `FTP_PASSWORD` from the hosting panel's FTP credentials. Never commit or share these secrets.
5. Create the first admin through phpMyAdmin if the host does not allow CLI/remote MySQL. Generate a password hash locally with PHP, then insert an admin row into `users`; do not create a public admin setup page.
6. After adding the FTP secrets, run the workflow from **Actions → Deploy to InfinityFree → Run workflow** or push to `main`. Confirm the URL works over HTTPS.

The FTP workflow targets `/htdocs/`. If your panel shows a different FTP root, update `server-dir` in `.github/workflows/infinityfree.yml`. GitHub Pages is not this application's hosting target.
