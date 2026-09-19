# Security Policy

## Reporting a vulnerability

Please do not disclose suspected vulnerabilities in a public issue.

Until a dedicated security contact is published, use GitHub's private vulnerability reporting feature on the repository's **Security** tab. Include reproduction steps, affected versions, impact, and any suggested mitigation.

Do not test vulnerabilities against installations you do not own or have permission to assess.

## Supported versions

Security fixes are currently provided for the latest release on the default branch. The project is in an early open-source stage and does not yet promise long-term support for older versions.

## Deployment checklist

- Set `APP_ENV=production` and `APP_DEBUG=false`.
- Generate a unique `APP_KEY` and strong administrator password.
- Serve the application over HTTPS.
- Keep `.env`, service-account files, logs, backups, and uploaded private files outside public access.
- Run queue workers and scheduled tasks under an unprivileged operating-system account.
- Apply Laravel and dependency security updates promptly.
- Run `php artisan security:audit` after importing content or redirects.

