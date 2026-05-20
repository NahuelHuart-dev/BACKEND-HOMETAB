# Testing and Quality

## Goal

HomeTab has moved from manual MVP validation to an automated testing suite. The goal is to detect regressions before production and prove, for the ABP final product, that the application is robust, verifiable and maintainable.

The test suite covers four layers:

| Layer | Tools | Purpose |
| --- | --- | --- |
| Backend unit | PHPUnit | Validate isolated business logic. |
| Backend functional | PHPUnit, Symfony WebTestCase, MySQL test | Validate real API endpoints. |
| Frontend unit | Vitest, Vue Test Utils, Testing Library, jsdom | Validate Vue services, composables and views. |
| E2E | Playwright | Validate critical browser flows. |

## Configuration Built From Scratch

The Symfony backend includes `symfony/test-pack`, `phpunit/phpunit` and `dama/doctrine-test-bundle`. The main configuration lives in `phpunit.dist.xml`, separates the `unit` and `functional` suites, and loads the test environment through `.env.test`.

The test database uses MySQL and is separated from development:

```text
db_hometab_test
```

`tests/Support/ApiTestCase.php` centralizes helpers for creating users, households, members, JWT headers and JSON requests. This avoids repeating manual fixtures in every functional test.

The Vue frontend includes Vitest with jsdom, Vue Test Utils, Testing Library, coverage and Playwright. `tests/setup.js` prepares global mocks such as `localStorage`, `matchMedia` and DOM cleanup between tests.

## What We Test Now

Backend:

- successful JWT login;
- invalid password error;
- inactive user rejection;
- listing only owned households;
- household creation and joining by invite code;
- denied access to foreign households;
- basic task, expense and event flows;
- validations, such as creating tasks without a title;
- rejection of participants outside the household.

Frontend:

- HTTP configuration, `baseURL`, JWT and assets;
- shared household and chat state;
- visual theme and accent;
- login, login error and redirect when a token already exists;
- household loading, 401 handling and dashboard navigation;
- dashboard summary, progress, tasks and events.

E2E:

- login;
- opening Mis Tabs;
- household creation;
- task, expense and event setup through the authenticated API;
- browser verification that task, expense and event are rendered in their tabs;
- notifications, chat and multimedia widgets;
- sending a chat message;
- multimedia playlist creation.

## How The Final E2E Works

Playwright opens a real Chromium browser and uses the app like a user: it visits `/login`, fills email and password, handles the optional 2FA prompt, opens Mis Tabs and navigates into a household.

The final test uses a mixed strategy:

- create the household from the UI, because it is a critical visual flow;
- create task, expense and event through the authenticated API, using the JWT stored in `localStorage`;
- return to the UI and verify those records appear in tasks, expenses and calendar;
- open the real notifications, chat and multimedia widgets.

This keeps the smoke test stable when long forms or ambiguous labels change, while still validating backend, permissions, token handling, routing and real rendering.

## Playwright Issues Found And Fixed

| Issue | Cause | Fix |
| --- | --- | --- |
| Chromium was missing | Playwright was installed, but the browser binary was not. | `npx playwright install chromium`. |
| Ambiguous `Sign in` selector | Navbar and login form both had a sign-in button. | Use `.login-card .btn-login`. |
| Optional 2FA prompt | Login could pause on `Not now`. | Wait for either URL change or prompt, then skip it if visible. |
| Duplicate text | `My Tabs` and playlist names appeared in more than one place. | Use `heading`, `.first()` or scoped areas like `.desktop-house-tabs`. |
| Clickable cards | `Create New Tab` was not a semantic `<button>`. | Click the real card/text. |
| Chat has two states | It can open a room directly or a household list first. | Support both states before typing. |

## Web Structure Tested By Playwright

HomeTab frontend is a Vue SPA built with Vite and Vue Router. The tested areas are:

- `/login`: access form, JWT in `localStorage` and optional 2FA;
- `/tabhub`: household post-it list;
- `/households/:id/dashboard`: household dashboard;
- internal tabs: tasks, expenses, calendar and multimedia;
- global widgets: notifications and chat.

The tests prefer accessible selectors (`role`, `label`, `heading`, `textbox`). When the UI does not expose a unique selector, they use stable component classes.

## How To Run

Backend:

```bash
cd Backend/backend-grup-6-gensync
composer test:setup
composer test
composer test:unit
composer test:functional
```

Frontend:

```bash
cd Frontend/frontend-grup-6-gensync
npm run test:unit
npm run test:coverage
npm run build
npm run test:e2e
```

`composer test:setup` creates and migrates the test database. `npm run test:e2e` needs E2E credentials to execute real flows against backend and frontend.

Local E2E with already running servers:

```powershell
cd C:\Users\naoct\Desktop\GENSYNC\Frontend\frontend-grup-6-gensync
$env:E2E_EMAIL="e2e-user@example.com"
$env:E2E_PASSWORD="test-password"
$env:E2E_BASE_URL="http://127.0.0.1:5173"
$env:E2E_API_URL="http://127.0.0.1:8000/api"
$env:E2E_SKIP_WEBSERVER="1"
npm run test:e2e
```

## Continuous Integration

GitHub Actions workflows act as the quality gate. The backend starts a MySQL service, prepares the test database and runs PHPUnit. The frontend installs dependencies, runs Vitest and verifies that the Vite build works.

When E2E secrets are available, Playwright can also run browser smoke tests:

```text
E2E_EMAIL
E2E_PASSWORD
E2E_API_URL
```

## Evidence For The Final Presentation

In the final presentation, the team can show that HomeTab is not only working through a manual demo. The automated suite can also be executed, explaining that each test protects a critical part:

- authentication;
- household permissions;
- data integrity;
- validations;
- main user experience;
- regressions before deployment.

## Phase 2: Critical Coverage

The second phase expands tests over the highest-risk functional modules:

| Module | Backend | Frontend |
| --- | --- | --- |
| 2FA | Login challenge, invalid code, valid code, enable and disable. | Login with code, verification and enabling/skipping 2FA. |
| Profile | Read, update, duplicated email, wrong current password and invalid avatar. | Profile load, avatar validation, save and API errors. |
| Chat | List, create, reject empty message, edit/delete own messages only and block foreign household. | Open chat, load households, send message and avoid empty send. |
| Notifications | Group by household, mark one, mark all and block foreign notifications. | Fetch, markRead, markAllRead, polling, counter, empty state and navigation. |
| Multimedia | List playlists, create playlist, validate name, add video and block foreign household. | Playlist load, creation, add video and API errors. |

Coverage is still informational. It does not block CI yet, but it is measured with:

```bash
npm run test:coverage
```

## GitHub E2E Secrets

E2E tests run against real DEV when these secrets exist:

```text
E2E_EMAIL
E2E_PASSWORD
E2E_API_URL
E2E_BASE_URL
```

`E2E_BASE_URL` points to the public DEV frontend. The E2E user must be stable, belong to a test household and use a regular user role.

## Recommended Next Tests

- Household chat: advanced typing, images and multimedia references.
- Profile: valid avatar upload and cleanup of old files.
- 2FA: code expiration and attempt limit.
- Multimedia: YouTube search with a mocked service and route query params.
- Security: invalid token, routes without token, CORS and more IDOR cases.
