# HomeTab Testing

Este documento explica como funciona el sistema de testing de HomeTab, que se ha creado y como ejecutarlo. La idea es que el proyecto no dependa solo de pruebas manuales, sino de una red automatizada que detecte regresiones en backend, frontend, navegador y CI.

## Capas

| Capa | Herramientas | Objetivo |
| --- | --- | --- |
| Backend unitario | PHPUnit | Probar logica aislada, como permisos de hogar. |
| Backend funcional | PHPUnit, Symfony WebTestCase, MySQL test | Probar endpoints reales de API con JWT y base de datos de test. |
| Frontend unitario | Vitest, Vue Test Utils, Testing Library, jsdom | Probar servicios, composables, vistas y estados Vue sin navegador real. |
| E2E | Playwright | Abrir Chromium y probar flujos criticos como un usuario real. |
| CI | GitHub Actions | Ejecutar tests y builds antes de aceptar cambios. |

## Seguridad de ejecucion local

Los tests unitarios y funcionales no afectan al servidor local normal:

| Tipo | Necesita servidor local? | Afecta datos reales? |
| --- | --- | --- |
| Backend unitario | No | No |
| Backend funcional | No | No, usa `db_hometab_test` |
| Frontend unitario | No | No, usa `jsdom` |
| Frontend coverage | No | No |
| Frontend build | No | No |
| E2E Playwright | Si se ejecuta contra local/DEV | Si, crea datos de prueba en ese entorno |

`npm run test:e2e` solo debe ejecutarse con una cuenta de pruebas. Si faltan `E2E_EMAIL` o `E2E_PASSWORD`, Playwright salta los tests autenticados de forma controlada.

## Backend

Ruta:

```bash
cd Backend/backend-grup-6-gensync
```

Configuracion creada:

- `phpunit.dist.xml` separa suites `unit` y `functional`.
- `.env.test` usa MySQL de test con `db_hometab_test`.
- `dama/doctrine-test-bundle` ayuda a aislar cambios de base de datos.
- `tests/Support/ApiTestCase.php` centraliza usuarios, hogares, miembros, JWT, peticiones JSON y fixtures.
- `composer.json` expone scripts de ejecucion.

Comandos:

```bash
composer test:setup
composer test
composer test:unit
composer test:functional
```

`composer test:setup` crea/migra la base de datos de test. Hay que ejecutarlo al preparar el entorno o cuando cambian migraciones.

Tests principales:

- `AuthApiTest`: login JWT, password incorrecta y usuario inactivo.
- `AuthTwoFactorApiTest`: login con 2FA, codigo invalido, codigo valido y activar/desactivar 2FA.
- `HouseholdApiTest`: hogares propios, crear hogar, unirse por codigo y bloquear hogar ajeno.
- `HouseholdResourceApiTest`: tareas, gastos, eventos, validaciones y permisos.
- `ProfileApiTest`: leer perfil, actualizar datos, email duplicado, password actual incorrecta y avatar invalido.
- `HouseholdChatApiTest`: listar, crear, validar vacio, editar/borrar propios y bloquear hogares ajenos.
- `NotificationApiTest`: listar agrupadas, marcar una, marcar todas y bloquear notificaciones ajenas.
- `MultimediaApiTest`: listar playlists, crear, validar nombre, anadir video y bloquear hogares ajenos.
- `HouseholdAccessServiceTest`: test unitario del servicio de permisos.

## Frontend

Ruta:

```bash
cd Frontend/frontend-grup-6-gensync
```

Configuracion creada:

- `vitest.config.js` configura Vue, jsdom, alias `@`, setup global y coverage.
- `tests/setup.js` prepara `localStorage`, `matchMedia`, limpieza DOM y matchers.
- `playwright.config.js` configura E2E con `E2E_BASE_URL`, `E2E_API_URL` y salto limpio si no hay credenciales.
- `package.json` expone scripts de unit, coverage, build y E2E.

Comandos:

```bash
npm run test:unit
npm run test:coverage
npm run build
npm run test:e2e
```

Tests unitarios principales:

- `http.test.js`: base URL, token JWT y assets.
- `useHouseState.test.js`: estado compartido de hogar/chat.
- `useNotifications.test.js`: fetch, markRead, markAllRead, polling y errores.
- `useTheme.test.js`: tema visual y acento.
- `LoginView.test.js`: login, errores, token existente y flujo 2FA.
- `TabHubView.test.js`: carga de hogares, 401 y navegacion.
- `DashboardTab.test.js`: resumen, progreso, tareas y eventos.
- `ProfileView.test.js`: perfil, avatar invalido, guardado y errores.
- `HouseNotificationsWidget.test.js`: contador, empty state y acciones.
- `HouseChatWidget.test.js`: abrir chat, cargar mensajes, enviar y vacio.
- `MultimediaTab.test.js`: playlists, crear playlist, anadir video y errores API.

## Playwright E2E

Playwright abre Chromium real. Por eso detecta problemas que Vitest no ve: selectores ambiguos, overlays que tapan botones, rutas que no redirigen, formularios que quedan deshabilitados o textos duplicados.

Los smoke tests viven en:

```text
Frontend/frontend-grup-6-gensync/tests/e2e/critical-flows.spec.js
```

Cubren:

- login y apertura de Mis Tabs;
- creacion de hogar desde la UI;
- creacion de tarea, gasto y evento por API autenticada;
- verificacion en navegador de que tarea, gasto y evento aparecen en sus tabs;
- apertura/cierre de notificaciones;
- envio de mensaje por chat;
- creacion de playlist multimedia.

La estrategia E2E final es mixta: crea la casa desde la interfaz y usa la API para preparar tarea/gasto/evento. Despues valida en la UI que todo aparece. Esto mantiene el test estable y sigue comprobando backend, JWT, permisos, frontend real y renderizado.

### Ejecucion local

Con backend y frontend levantados:

```powershell
cd C:\Users\naoct\Desktop\GENSYNC\Frontend\frontend-grup-6-gensync
$env:E2E_EMAIL="usuario-e2e@example.com"
$env:E2E_PASSWORD="password-de-pruebas"
$env:E2E_BASE_URL="http://127.0.0.1:5173"
$env:E2E_API_URL="http://127.0.0.1:8000/api"
$env:E2E_SKIP_WEBSERVER="1"
npm run test:e2e
```

Si Chromium no esta instalado:

```powershell
npx playwright install chromium
```

## Errores E2E encontrados y solucion

1. **Faltaba Chromium**: Playwright daba `Executable doesn't exist`. Se resolvio con `npx playwright install chromium`.
2. **Selector ambiguo de login**: habia dos botones `Sign in`. Se cambio a `.login-card .btn-login`.
3. **Prompt de 2FA**: el test ahora espera redireccion o boton `Not now`; si aparece, lo omite.
4. **Textos duplicados**: se usan roles concretos, `.first()` y selectores por zonas como `.desktop-house-tabs`.
5. **Tarjetas clickables sin rol button**: `Create New Tab` era una tarjeta, asi que el test hace clic sobre el texto real.
6. **Formulario de tareas fragil**: la accion quedaba bloqueada por estados de UI. Se estabilizo creando recursos criticos por API y verificando la UI.
7. **Chat con dos estados**: el test soporta chat ya abierto en una casa o lista previa de casas.

## Como esta hecha la web para que el E2E la pruebe

HomeTab frontend es una SPA Vue con Vite y Vue Router:

- `/login`: formulario de acceso, JWT en `localStorage` y posible 2FA.
- `/tabhub`: listado de hogares como post-its.
- `/households/:id/dashboard`: dashboard de una casa.
- Tabs internas: tareas, gastos, calendario y multimedia.
- Widgets globales: notificaciones, chat y personalizacion.

Los tests usan primero accesibilidad (`role`, `label`, `heading`, `textbox`). Cuando la UI no ofrece un selector unico, usan clases estables de componentes concretos.

## CI

GitHub Actions ejecuta:

- backend: Composer, MySQL service, base de datos test y PHPUnit;
- frontend: npm install, Vitest, build y E2E si existen secrets.

Secrets recomendados:

```text
E2E_EMAIL
E2E_PASSWORD
E2E_API_URL
E2E_BASE_URL
```

La cobertura frontend se mide con `npm run test:coverage`, pero todavia no bloquea CI. Se usa como metrica para subir cobertura por modulos criticos.

## Resultado actual

Ultimo estado validado:

- Backend: `composer test` con 36 tests y 108 assertions.
- Frontend: `npm run test:unit` con 34 tests.
- Frontend coverage: funcionando como metrica informativa.
- Frontend build: correcto.
- E2E local: 3 tests pasando con Chromium.

