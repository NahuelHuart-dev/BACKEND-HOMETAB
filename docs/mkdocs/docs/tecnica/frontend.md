# Frontend Vue

El frontend de HomeTab es una SPA Vue 3 construida con Vite. Vive en `Frontend/frontend-grup-6-gensync` y consume la API Symfony mediante Axios. Esta pagina documenta la estructura real del codigo actual.

## Estructura

```text
src/
├── App.vue
├── main.js
├── main.css
├── assets/
├── components/
├── composables/
├── i18n/
├── router/
├── services/
├── styles/
└── views/
    └── tabs/
```

## Router

El router actual no usa un guard global `requiresAuth`; la proteccion se reparte entre las vistas, el token de `localStorage` y las respuestas `401` del backend. Las rutas principales estan en `src/router/index.js`:

```js
{ path: '/', name: 'landing', component: LandingView }
{ path: '/login', name: 'login', component: LoginView }
{ path: '/register', name: 'register', component: RegisterView }
{ path: '/tabhub', name: 'tabhub', component: TabHubView }
{ path: '/profile', name: 'profile', component: () => import('../views/ProfileView.vue') }
{
  path: '/households/:id',
  component: () => import('../views/HouseholdLayoutView.vue'),
  redirect: to => ({ path: `/households/${to.params.id}/dashboard` }),
  children: [
    { path: 'dashboard', name: 'dashboard', component: () => import('../views/tabs/DashboardTab.vue') },
    { path: 'expenses', name: 'expenses', component: () => import('../views/tabs/ExpensesTab.vue') },
    { path: 'tasks', name: 'tasks', component: () => import('../views/tabs/TasksTab.vue') },
    { path: 'calendar', name: 'calendar', component: () => import('../views/tabs/CalendarTab.vue') },
    { path: 'multimedia', name: 'multimedia', component: () => import('../views/tabs/MultimediaTab.vue') },
    { path: 'settings', name: 'settings', component: () => import('../views/tabs/SettingsTab.vue') }
  ]
}
```

## Servicio HTTP

`src/services/http.js` centraliza Axios. Calcula la URL base segun `VITE_API_URL` o, en local, `http://<host>:8000/api`. Tambien exporta `assetUrl()` para resolver imagenes de `/uploads`.

```js
const isLocalFrontend = ['localhost', '127.0.0.1'].includes(window.location.hostname)
const localApiUrl = `${window.location.protocol}//${window.location.hostname}:8000/api`
const configuredApiUrl = import.meta.env.VITE_API_URL
const baseURL = configuredApiUrl || (isLocalFrontend ? localApiUrl : '/api')

const http = axios.create({
  baseURL,
  headers: { 'Content-Type': 'application/json' }
})

http.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})
```

Importante: el interceptor actual solo anade el JWT. La gestion de redireccion por `401` esta cubierta por vistas/composables concretos, no por un interceptor global de respuesta.

## Estado Compartido

`useHouseState.js` mantiene estado global reactivo de la casa actual y del chat:

```js
const currentHomeId = ref(null)
const currentHouseName = ref('')
const houseTabs = ref([])
const isChatOpen = ref(false)

export function useHouseState() {
  const isInsideHouse = computed(() => !!currentHomeId.value)
  const setHouseData = (id, tabs, name = '') => {
    currentHomeId.value = id
    houseTabs.value = tabs
    currentHouseName.value = name
  }
  const clearHouseData = () => {
    currentHomeId.value = null
    currentHouseName.value = ''
    houseTabs.value = []
    isChatOpen.value = false
  }
  return { currentHomeId, currentHouseName, houseTabs, isInsideHouse, isChatOpen, setHouseData, clearHouseData }
}
```

Este composable conecta `HouseholdLayoutView`, navbar, chat y widgets.

## Vistas

| Vista | Ruta | Responsabilidad |
| --- | --- | --- |
| `LandingView.vue` | `/` | Landing publica del producto. |
| `LoginView.vue` | `/login` | Login, 2FA, prompt de activacion y recuperacion de contrasena. |
| `RegisterView.vue` | `/register` | Registro de usuario. |
| `TabHubView.vue` | `/tabhub` | Lista, creacion, union y orden de hogares. |
| `HouseholdLayoutView.vue` | `/households/:id/*` | Layout interno de casa y tabs. |
| `ProfileView.vue` | `/profile` | Perfil, avatar, datos personales y seguridad. |
| `FeedbackView.vue` | `/feedback` | Envio de feedback publico. |
| `NotFoundView.vue` | fallback | 404 de la SPA. |

Tabs internas:

| Vista | Endpoint principal |
| --- | --- |
| `DashboardTab.vue` | `GET /api/households/{homeId}/dashboard` |
| `TasksTab.vue` | `/api/households/{homeId}/tasks` |
| `ExpensesTab.vue` | `/api/households/{homeId}/expenses` |
| `CalendarTab.vue` | `/api/households/{homeId}/events` |
| `MultimediaTab.vue` | `/api/households/{homeId}/multimedia/*` |
| `SettingsTab.vue` | `/api/households/{id}`, miembros e invite code |

## Componentes Y Composables

| Pieza | Uso |
| --- | --- |
| `appNavbar.vue` | Navegacion principal, selector de idioma, tema, perfil, tabs de casa y accesos a widgets. |
| `HouseChatWidget.vue` | Chat flotante conectado a hogares y mensajes. |
| `HouseNotificationsWidget.vue` | Notificaciones agrupadas y acciones de lectura. |
| `PostItCard.vue` | Tarjetas de hogares en TabHub. |
| `AppConfirmDialog.vue` | Confirmaciones reutilizables. |
| `useNotifications.js` | Fetch, polling, contador, markRead y markAllRead. |
| `useTheme.js` | Tema claro/oscuro y color de acento en `localStorage`. |
| `useNavigation.js` | Helpers de navegacion entre hogares y tabs. |
| `useConfirmDialog.js` | Estado global del dialogo de confirmacion. |
| `useAnimations.js` | Animaciones GSAP no criticas para negocio. |

## i18n

`src/i18n/index.js` configura `vue-i18n`. La UI principal trabaja con catalan, castellano e ingles, aunque todavia hay textos historicos mezclados en algunas vistas. Esa deuda aparece en `Cobertura i deute tecnic`.

## Tests Frontend

| Suite | Cobertura |
| --- | --- |
| `tests/unit/services/http.test.js` | `baseURL`, JWT y `assetUrl`. |
| `tests/unit/composables/useHouseState.test.js` | Estado de casa y chat. |
| `tests/unit/composables/useNotifications.test.js` | Fetch, polling y acciones de lectura. |
| `tests/unit/views/LoginView.test.js` | Login, 2FA, errores y redireccion. |
| `tests/unit/views/TabHubView.test.js` | Carga de hogares, 401 y navegacion. |
| `tests/unit/views/ProfileView.test.js` | Perfil, avatar y errores API. |
| `tests/unit/views/MultimediaTab.test.js` | Playlists, videos y errores. |
| `tests/e2e/critical-flows.spec.js` | Login, TabHub, casa, recursos principales, chat y multimedia. |

## Tabla Vista → Endpoint → Test

| Vista | Endpoint | Test |
| --- | --- | --- |
| `LoginView` | `POST /api/login_check`, `POST /api/login/verify` | `LoginView.test.js`, `critical-flows.spec.js` |
| `TabHubView` | `GET /api/households`, `POST /api/households/new`, `POST /api/households/join` | `TabHubView.test.js`, `critical-flows.spec.js` |
| `DashboardTab` | `GET /api/households/{homeId}/dashboard` | `DashboardTab.test.js` |
| `ProfileView` | `GET/POST /api/profile` | `ProfileView.test.js` |
| `HouseChatWidget` | `/api/chat/households`, `/api/households/{homeId}/chat/*` | `HouseChatWidget.test.js`, `critical-flows.spec.js` |
| `HouseNotificationsWidget` | `/api/notifications/*` | `HouseNotificationsWidget.test.js` |
| `MultimediaTab` | `/api/households/{homeId}/multimedia/*` | `MultimediaTab.test.js`, `critical-flows.spec.js` |
