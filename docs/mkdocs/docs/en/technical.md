# Technical Documentation

## Architecture

HomeTab is split into three main parts:

| Component | Technology | Responsibility |
| --- | --- | --- |
| Backend | Symfony 7.4, PHP 8.2, Doctrine ORM, Twig | API, persistence, security, backoffice and protected documentation. |
| Frontend | Vue 3, Vite, PrimeVue, Axios, GSAP | Main user-facing experience and API consumption. |
| Landing | Vue/Vite | Public presentation site. |

The backend is the source of truth for data and permissions. The Vue frontend consumes the JSON API with JWT. The Twig backend is reserved for superadmin operations.

## Backend structure

```text
src/
  Controller/Api/
  Controller/twig/
  Entity/
  Repository/
  Service/
  Security/
  Twig/
templates/
docs/mkdocs/
```

## Security model

- Public API routes: login, 2FA verification and registration.
- Protected API routes: all other `/api` endpoints require `ROLE_USER`.
- Private backend routes: `/admin` requires `ROLE_SUPER_ADMIN`.
- Documentation route: `/admin/documentacio`, also protected by `ROLE_SUPER_ADMIN`.

## Main services

| Service | Responsibility |
| --- | --- |
| `HouseholdAccessService` | Centralizes household access checks. |
| `TwoFactorService` | Generates, stores and verifies 2FA challenges. |
| `TwoFactorEmailSender` | Sends 2FA codes by email. |
| `ChatTypingService` | Tracks temporary typing state in household chats. |
| `SqlAIAssistant` | Generates read-only SQL for admin support. |
| `AssistantService` | Answers functional questions about HomeTab data. |

## Data model

Main entities:

- `User`
- `Household`
- `HouseholdMember`
- `Task`
- `Event`
- `Expense`
- `ExpenseShare`
- `HouseholdMessage`
- `ChatAccessLog`
- `TwoFactorCode`

Important functional records use soft deletion through `isActive=false` instead of physical deletion.

## Protected MkDocs integration

MkDocs source lives under:

```text
docs/mkdocs
```

The generated site is:

```text
docs/mkdocs/site
```

Symfony serves it through `AdminDocumentationController`, outside `public/`, so access cannot bypass backend permissions.

## Integrated long-form technical records

The Catalan section includes the full backend administration and AI guide, plus the full backend worklog:

- `Tecnica > Guia backend admin i IA`
- `Tecnica > Worklog tecnic complet`
