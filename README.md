# 10T Mailer

10T Mailer is a WordPress plugin for routing WordPress email through configurable delivery services.

The plugin is in early development. Its first supported transport will be SMTP, with Google OAuth/API delivery planned for a later release. The project is intended to support both single-site and multisite WordPress installations.

## Planned features

- SMTP delivery using WordPress's bundled PHPMailer
- Configuration through WordPress administration or `wp-config.php`
- Secure storage of database-backed credentials
- Network defaults and per-site settings for WordPress multisite
- Email logging and delivery diagnostics
- Google Workspace and Gmail delivery using OAuth 2.0

## Requirements

- WordPress 6.6 or later
- PHP 8.1 or later

## Current status

10T Mailer is under active development and is not yet ready to handle email on production websites. Until a transport is explicitly configured and enabled, the plugin will not alter WordPress's normal email delivery behavior.

## License

10T Mailer is licensed under the GNU General Public License v2.0 or later. See [LICENSE](LICENSE) once included in the project.
