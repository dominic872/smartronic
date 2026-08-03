# Hourly Database Backups

This folder stores timestamped ZIP backups created by `backup_db.php`.

## Cron

Run this script every hour from your hosting panel:

```bash
php /absolute/path/to/smartronic/backup/backup_db.php
```

If your host requires a full binary path, use the path shown by the hosting panel.

## Diagnostics

Open this in a browser to see what the host is blocking:

```text
/backup/backup_db.php?diagnose=1
```

To keep and inspect the generated SQL file:

```text
/backup/backup_db.php?verify=1
```

## Retention

The script automatically deletes `.zip` backups older than `72` hours.

## Output

Each run creates a file like:

```text
smartronic-db-2026-06-04-15-00-00.zip
```
