# Full Backend Technical Worklog

This worklog summarizes the main backend changes implemented in HomeTab.

## Architecture after the changes

The backend is split into:

- JSON API for the Vue frontend.
- Twig backend for internal administration tools.

The API uses JWT. The Twig backend is reserved for `ROLE_SUPER_ADMIN`.

## 2FA by email

Optional two-factor authentication was implemented by email.

Flow:

1. User sends email and password.
2. If 2FA is disabled, the API returns JWT.
3. If 2FA is enabled, the API returns `challengeId`.
4. A code is sent by email.
5. `POST /api/login/verify` verifies the challenge.
6. JWT is only issued after successful verification.

Main files:

```text
src/Entity/TwoFactorCode.php
src/Service/TwoFactorService.php
src/Service/TwoFactorEmailSender.php
src/Controller/Api/AuthController.php
```

## Mailer

Symfony Mailer was configured for local and real SMTP delivery.

Local development can use Mailpit:

```text
SMTP: localhost:1025
UI:   http://localhost:8025
```

Real SMTP credentials must live outside Git.

## Backend login restriction

The Twig backend is restricted to superadmins. Non-superadmin users are redirected back to login and their session is invalidated.

Relevant files:

```text
config/packages/security.yaml
src/Security/LoginFormAuthenticator.php
templates/security/login.html.twig
```

## Admin panel

The admin panel includes:

- Household dashboard.
- User grid.
- Global task grid.
- Global expense grid.
- Global event grid.
- Chat audit.
- SQL AI.
- Read-only SQL console.
- Protected MkDocs documentation.

## Soft deletion

Important functional records are deactivated instead of physically removed:

```php
$entity->setIsActive(false);
```

This applies to tasks, expenses and events.

## Profile and avatar work

The profile editor supports:

- Avatar icon selection.
- Photograph upload.
- Browser-side square crop.
- Backend image validation.
- Deletion of old avatar files.

## Chat and audit

Household chat was implemented with:

- message listing
- message sending
- typing indicators
- editing own messages
- soft deletion of own messages
- admin audit logs for chat access

## Documentation

MkDocs was integrated under:

```text
docs/mkdocs
```

The generated site is served through:

```text
/admin/documentacio
```

Access is protected by `ROLE_SUPER_ADMIN`.

## Validation commands

Recommended checks:

```bash
php -l src/Controller/twig/AdminDocumentationController.php
php bin/console lint:twig templates
php bin/console lint:yaml config
php bin/console doctrine:schema:validate
python -m mkdocs build -f docs/mkdocs/mkdocs.yml --strict
```
