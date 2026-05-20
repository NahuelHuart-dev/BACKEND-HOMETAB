# Mapa del projecte

## Visió general

El projecte HomeTab és un monorepo dividit en tres carpetes principals:

```
GENSYNC/
├── Backend/
│   └── backend-grup-6-gensync/     # API Symfony + Twig backoffice
├── Frontend/
│   └── frontend-grup-6-gensync/    # SPA Vue 3 + Vite
└── Landing/
    └── landing-page-grup-6-gensync/ # Landing pública
```

---

## Backend: `backend-grup-6-gensync/`

### `src/Controller/Api/` — Controladors API REST

Els controladors API atenen les peticions del frontend Vue. Tots usen JWT i retornen JSON.

| Arxiu | Rutes base | Responsabilitat |
|---|---|---|
| `AuthController.php` | `/api/login_check`, `/api/login/verify`, `/api/2fa/*`, `/api/password-reset/*` | Login, 2FA i recuperació de contrasenya |
| `ApiRegistrationController.php` | `/api/register` | Registre de nous usuaris |
| `ApiHouseholdController.php` | `/api/households/*` | CRUD de llars, membres, invitacions |
| `ApiUserController.php` | `/api/households/{homeId}/dashboard` | Resum del dashboard d'una llar |
| `ApiTaskController.php` | `/api/households/{id}/tasks/*` | CRUD de tasques |
| `ApiExpenseController.php` | `/api/households/{id}/expenses/*` | CRUD de despeses, balance i liquidacions |
| `ApiEventController.php` | `/api/households/{id}/events/*` | CRUD d'esdeveniments |
| `ApiHouseholdChatController.php` | `/api/households/{id}/chat/*` | Missatges del xat de llar |
| `ApiChatWidgetController.php` | `/api/chat/households` | Llars disponibles per al widget global de xat |
| `ApiNotificationController.php` | `/api/notifications/*` | Notificacions de l'usuari |
| `ApiMultimediaController.php` | `/api/households/{id}/multimedia/*` | Playlists i vídeos de YouTube |
| `ApiProfileController.php` | `/api/profile` | Lectura i actualització del perfil |
| `AssistantController.php` | `/api/assistant/*` | Assistent IA de consultes |
| `FeedbackController.php` | `/api/feedback` | Enviament de formulari de feedback |
| `UsersApiController.php` | `/api/users` | Llistat d'usuaris (ús intern) |
| `ChatApiController.php` | `/test/chat`, `/admin/chat`, `/admin/sql` | Vistes internes de prova, xat admin i consola SQL |

### `src/Controller/twig/` — Controladors del backoffice Twig

El panell Twig és accessible únicament per a `ROLE_SUPER_ADMIN` sota `/admin`.

| Arxiu | Ruta | Responsabilitat |
|---|---|---|
| `AdminController.php` | `/admin/*` | Panell principal: llars, usuaris, tasques, despeses, events |
| `AdminChatController.php` | `/admin/chats/*` | Auditoria de xats amb registre d'accés |
| `AdminDocumentationController.php` | `/admin/documentacio/*` | Serveix MkDocs de forma protegida |
| `SecurityController.php` | `/login`, `/logout`, `/login/2fa` | Login Twig i flux 2FA per a admins |
| `ProfileController.php` | `/profile` | Perfil del superadmin al backoffice |
| `RegistrationController.php` | `/register` | Registre Twig (usat en desenvolupament) |
| `ChatWidgetController.php` | `/chat-widget/*` | Widget de xat integrat a les vistes Twig |
| `ExpenseController.php` | `/expense/*` | Gestió de despeses des del Twig |
| `EventController.php` | `/event/*` | Gestió d'events des del Twig |
| `TaskController.php` | `/task/*` | Gestió de tasques des del Twig |
| `HomeController.php` | `/` | Redirecció a login o dashboard |
| `UserController.php` | `/dashboard` | Dashboard web simple per a usuari autenticat |

### `src/Entity/` — Entitats Doctrine

Model de domini de l'aplicació. Cada entitat mapeja una taula de la base de dades.

| Entitat | Taula | Responsabilitat |
|---|---|---|
| `User.php` | `user` | Usuari de l'aplicació, rols, 2FA, avatar |
| `Household.php` | `household` | Llar: nom, codi d'invitació, imatge |
| `HouseholdMember.php` | `household_member` | Relació usuari–llar amb rol i ordre |
| `Task.php` | `task` | Tasca: títol, prioritat, periodicitat, assignació |
| `Expense.php` | `expense` | Despesa: import, categoria, pagament, recurrència |
| `ExpenseShare.php` | `expense_share` | Quota individual d'una despesa per usuari |
| `Event.php` | `event` | Esdeveniment: dates, participants, color |
| `HouseholdMessage.php` | `household_message` | Missatge del xat d'una llar |
| `ChatAccessLog.php` | `chat_access_log` | Registre d'accés administratiu al xat |
| `Notification.php` | `notification` | Notificació per usuari i llar |
| `MultimediaPlaylist.php` | `multimedia_playlist` | Playlist de vídeos d'una llar |
| `MultimediaVideo.php` | `multimedia_video` | Vídeo de YouTube dins d'una playlist |
| `TwoFactorCode.php` | `two_factor_code` | Codi de verificació temporal per 2FA |
| `PasswordResetToken.php` | `password_reset_token` | Token de restabliment de contrasenya |

### `src/Repository/` — Repositoris Doctrine

Cada entitat té el seu repositori per a consultes específiques:

- `UserRepository` — cerca per email, filtres d'activació.
- `HouseholdMemberRepository` — membresies per usuari o llar.
- `NotificationRepository` — notificacions actives per usuari, no llegides.
- `MultimediaPlaylistRepository` — playlists d'una llar ordenades.
- `PasswordResetTokenRepository` — cerca tokens vàlids i no usats.

### `src/Service/` — Serveis de lògica transversal

| Servei | Funció |
|---|---|
| `TwoFactorService.php` | Genera reptes 2FA (challengeId + codi hash), els verifica i controla expiració |
| `TwoFactorEmailSender.php` | Envia l'email amb el codi 2FA via Symfony Mailer |
| `HouseholdAccessService.php` | Comprova si un usuari pertany a una llar; centralitza la guarda d'accés |
| `NotificationService.php` | Genera i sincronitza notificacions a partir de tasques, despeses i events |
| `ChatTypingService.php` | Gestiona l'estat temporal "escrivint..." en temps real |
| `SafeImageStorage.php` | Desa imatges base64 a `public/uploads/` validant format i mida |
| `YouTubeVideoService.php` | Cerca vídeos de YouTube via API i extreu metadades d'una URL |
| `SqlAIAssistant.php` | Genera SQL de lectura via OpenRouter (IA) a partir de preguntes en llenguatge natural |
| `FeedbackEmailSender.php` | Envia formularis de feedback per correu al suport |
| `Assistant/AssistantService.php` | Respon preguntes sobre dades de HomeTab combinant context i IA |
| `Assistant/AssistantContextBuilder.php` | Construeix el context de l'usuari per a l'assistent |

### `src/Security/` — Seguretat

| Arxiu | Funció |
|---|---|
| `LoginFormAuthenticator.php` | Autenticador per al firewall `main` (formulari Twig). Comprova email, contrasenya i 2FA. |

### `templates/` — Plantilles Twig

Organitzades per àrea funcional:
- `admin/` — panell de superadmin (llars, usuaris, tasques, despeses, events, SQL, AI).
- `security/` — login, 2FA, registre.
- `profile/` — perfil del backoffice.
- `chat/` — widget de xat integrat al backoffice.

### `config/` — Configuració

```
config/
├── packages/
│   ├── security.yaml          # Firewalls, rols, control d'accés
│   ├── lexik_jwt_authentication.yaml  # JWT keys i TTL
│   ├── nelmio_cors.yaml       # CORS per al frontend Vue
│   ├── nelmio_api_doc.yaml    # Swagger UI i configuració OpenAPI
│   ├── doctrine.yaml          # ORM i connexió MySQL
│   └── mailer.yaml            # Configuració del mailer
├── routes/
│   └── security.yaml          # Ruta /login i /logout Twig
└── services.yaml              # Autowiring i serveis
```

### `migrations/` — Migracions Doctrine

Cada migració registra un canvi incremental d'esquema. Segueixen el format `Version{timestamp}.php`. S'apliquen amb:

```bash
php bin/console doctrine:migrations:migrate
```

### `tests/` — Tests PHPUnit

```
tests/
├── bootstrap.php              # Inicialitza l'entorn de test
├── Support/
│   └── ApiTestCase.php        # Classe base: crea usuaris, llars, headers JWT
├── Unit/
│   └── HouseholdAccessServiceTest.php   # Test unitari del servei d'accés
└── Functional/
    ├── AuthApiTest.php              # Login, credencials incorrectes, inactiu
    ├── AuthTwoFactorApiTest.php     # Login+2FA: challengeId, codi invàlid
    ├── HouseholdApiTest.php         # Llistat, creació i unió a llar
    ├── HouseholdResourceApiTest.php # Tasques, despeses i events
    ├── HouseholdChatApiTest.php     # Xat: missatges, permisos llar aliena
    ├── MultimediaApiTest.php        # Playlists i vídeos
    ├── NotificationApiTest.php      # Notificacions i marcat de llegides
    └── ProfileApiTest.php           # Perfil, actualització, contrasenya
```

---

## Frontend: `frontend-grup-6-gensync/`

### `src/views/` — Vistes principals

| Vista | Ruta | Descripció |
|---|---|---|
| `LandingView.vue` | `/` | Pàgina de presentació del producte |
| `LoginView.vue` | `/login` | Formulari de login + flux 2FA i recuperació de contrasenya |
| `RegisterView.vue` | `/register` | Formulari de registre |
| `TabHubView.vue` | `/tabhub` | Llistat de llars de l'usuari en format post-it |
| `HouseholdLayoutView.vue` | `/households/:id` | Layout amb navbar i tabs per a una llar |
| `ProfileView.vue` | `/profile` | Edició del perfil i seguretat |
| `AboutView.vue` | `/about` | Sobre el projecte |
| `FeedbackView.vue` | `/feedback` | Formulari de feedback |
| `NotFoundView.vue` | `/*` | Pàgina 404 |

### `src/views/tabs/` — Pestanyes de la llar

Renderitzades com a child routes de `HouseholdLayoutView`:

| Vista | Ruta | Descripció |
|---|---|---|
| `DashboardTab.vue` | `.../dashboard` | Resum de tasques, despeses, events i membres |
| `TasksTab.vue` | `.../tasks` | Gestió de tasques amb filtres, prioritats i assignació |
| `ExpensesTab.vue` | `.../expenses` | Despeses, balance i liquidacions |
| `CalendarTab.vue` | `.../calendar` | Calendari d'events interactiu |
| `MultimediaTab.vue` | `.../multimedia` | Playlists i reproductor de YouTube |
| `SettingsTab.vue` | `.../settings` | Configuració de la llar i membres |

### `src/components/` — Components reutilitzables

| Component | Funció |
|---|---|
| `appNavbar.vue` | Barra de navegació principal. Canvia entre mode escriptori i mòbil. Inclou menú de llars, notificacions i avatar d'usuari |
| `HouseChatWidget.vue` | Widget de xat emergent. En mòbil és un slide-up a pantalla completa |
| `HouseNotificationsWidget.vue` | Widget de notificacions agrupades per llar |
| `AppConfirmDialog.vue` | Diàleg de confirmació reutilitzable |
| `PostItCard.vue` | Targeta post-it per a les llars al TabHub |
| `LandingDemoPreview.vue` | Preview animat de l'app per a la landing |
| `appFooter.vue` | Peu de pàgina |
| `svgLogo.vue` | Logo SVG animat de HomeTab |

### `src/composables/` — Lògica reutilitzable Vue

| Composable | Funció |
|---|---|
| `useHouseState.js` | Estat global reactiu: llar activa, nom, tabs i estat del xat |
| `useNotifications.js` | Fetch de notificacions, polling, marcar com llegides, comptadors |
| `useNavigation.js` | Helpers de navegació: anar a una llar, a les tabs, etc. |
| `useTheme.js` | Gestió del tema visual (clar/fosc) i color accent. Persista a localStorage |
| `useAnimations.js` | Animacions GSAP per a transicions, hover i efectes visuals |
| `useConfirmDialog.js` | Control del diàleg de confirmació global |

### `src/services/` — Serveis HTTP

| Arxiu | Funció |
|---|---|
| `http.js` | Instància Axios configurada amb `baseURL` dinàmica, interceptor JWT de request i helper `assetUrl` |

### `src/router/` — Router Vue

| Arxiu | Funció |
|---|---|
| `index.js` | Defineix totes les rutes de l'SPA amb lazy loading. Les rutes de llar (`/households/:id/*`) utilitzen children routes |

### `src/i18n/` — Internacionalització

L'aplicació suporta català, castellà i anglès via vue-i18n.

### `tests/` — Tests del frontend

```
tests/
├── setup.js                   # Mocks globals: localStorage, matchMedia
├── unit/
│   ├── components/
│   │   ├── HouseChatWidget.test.js         # Widget de xat: renderitzat, enviament
│   │   └── HouseNotificationsWidget.test.js # Widget notificacions: fetch, marcat
│   ├── composables/
│   │   ├── useHouseState.test.js            # Estat global de llar
│   │   ├── useNotifications.test.js         # Fetch, polling, mark read
│   │   └── useTheme.test.js                 # Tema i persistència
│   ├── services/
│   │   └── services/http.test.js            # JWT i helper assetUrl
│   └── views/
│       ├── LoginView.test.js        # Login, 2FA, recuperació, redirecció
│       ├── DashboardTab.test.js     # Dashboard: tasques, despeses, events
│       ├── MultimediaTab.test.js    # Playlists, creació, vídeos
│       ├── ProfileView.test.js      # Perfil, avatar, validació
│       └── TabHubView.test.js       # Llars, loading, error 401
└── e2e/
    └── critical-flows.spec.js  # Playwright: login, TabHub, llar, tasques, despeses, events, xat, multimedia
```

---

## Documentació: `docs/mkdocs/`

```
docs/mkdocs/
├── mkdocs.yml          # Configuració de MkDocs Material
├── requirements.txt    # mkdocs-material i extensions
├── docs/
│   ├── index.md               # Pàgina d'inici
│   ├── manual/
│   │   └── usuari.md          # Manual d'usuari no tècnic
│   ├── presentacio/
│   │   ├── documentacio-presentacio.md
│   │   └── evolucio-mvp.md
│   ├── tecnica/               # Documentació tècnica completa
│   ├── api/                   # Referència d'endpoints
│   └── materials/             # Checklist, decisions, backlog
└── site/               # Build generat i versionat perquè Symfony el serveix des de /admin/documentacio
```
