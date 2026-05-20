# Backend Administration and AI Study Guide

This guide summarizes the Symfony backend work so the architecture can be studied without jumping across many files.

## General objective

The backend covers:

- Global administration for `ROLE_SUPER_ADMIN`.
- Full household, user, task, event, calendar, settings and invitation management through Twig views.
- Shared expenses with participants, per-user payment status and recurrence.
- Household chat with administrative audit logs.
- Functional AI assistant for the digitization module.
- SQL AI with OpenRouter for superadmin database support.
- MkDocs documentation served from a private backend route.

## Security and access

Main file:

```text
config/packages/security.yaml
```

Key points:

- `/api/login_check` performs manual login so 2FA can be handled before issuing JWT.
- `/api` requires JWT and `ROLE_USER`.
- `/admin` requires `ROLE_SUPER_ADMIN`.
- The `main` firewall uses `App\Security\LoginFormAuthenticator` for Symfony web login.
- `ROLE_SUPER_ADMIN` inherits `ROLE_ADMIN` and `ROLE_USER`.

The Vue frontend enters through API/JWT. The Twig backend enters through web session and only the superadmin can access it.

## Admin controller

Main file:

```text
src/Controller/twig/AdminController.php
```

Important routes:

- `GET /admin/dashboard`: lists all households.
- `POST /admin/households/create`: creates a household and assigns the superadmin as owner.
- `GET /admin/households/{id}/{section}`: main household view.
- `POST /admin/households/{id}/invite-code`: changes the invitation code.
- `POST /admin/households/{id}/expenses/save/{expenseId?}`: creates or edits expenses.
- `POST /admin/households/{id}/tasks/save/{taskId?}`: creates or edits tasks.
- `POST /admin/households/{id}/events/save/{eventId?}`: creates or edits events.
- `GET /admin/users`: lists users.

Important helper methods:

- `userBelongsToHousehold()`
- `selectedHouseholdUsers()`
- `replaceExpenseShares()`
- `isValidRecurrence()`
- `buildCalendarWeeks()`
- `generateInviteCode()`
- `storeAvatarUpload()`
- `storeAvatarCrop()`

## Twig views

Main directory:

```text
templates/admin
```

Important templates:

- `dashboard.html.twig`
- `household.html.twig`
- `users.html.twig`
- `records.html.twig`
- `chat.html.twig`
- `sql.html.twig`
- `documentation_missing.html.twig`

`household.html.twig` uses macros for expenses, tasks and events to avoid repeating modal forms.

## Shared expenses

Main entities:

```text
src/Entity/Expense.php
src/Entity/ExpenseShare.php
```

`Expense` stores the global expense. `ExpenseShare` stores each participant's individual share, amount owed, payment state and payment date.

The save flow:

1. The admin selects payer, payment type and participants.
2. Individual expenses only include the payer.
3. Shared expenses include selected participants.
4. `replaceExpenseShares()` rebuilds the shares.
5. `paidShares[]` marks users who have already paid.

## Calendar

The calendar lives in:

```text
templates/admin/household.html.twig
```

It is built with `AdminController::buildCalendarWeeks()` and supports monthly navigation, event creation from a selected day and event editing.

## Profile and avatars

The profile supports either a photograph or an icon. Images are cropped in the browser with `canvas`, sent as base64 and validated in the backend before being stored under:

```text
public/uploads/avatars
```

## SQL AI and digitization assistant

There are two AI-related features:

| Feature | User | Purpose |
| --- | --- | --- |
| HomeTab assistant | Authenticated user | Query household data in natural language. |
| SQL AI | Admin/superadmin | Generate read-only SQL for internal support. |

The digitization assistant endpoint is:

```text
POST /api/assistant/chat
```

It can answer questions about tasks, events, pending payments, balances and household summaries.
