# Testing y calidad

## Objetivo

HomeTab ha pasado de validar el MVP manualmente a tener una bateria de pruebas automatizadas. El objetivo es detectar regresiones antes de produccion y demostrar, de cara al producto final ABP, que la aplicacion es robusta, verificable y mantenible.

Las pruebas cubren cuatro capas:

| Capa | Herramientas | Finalidad |
| --- | --- | --- |
| Backend unitario | PHPUnit | Validar logica de negocio aislada. |
| Backend funcional | PHPUnit, Symfony WebTestCase, MySQL test | Validar endpoints reales de la API. |
| Frontend unitario | Vitest, Vue Test Utils, Testing Library, jsdom | Validar servicios, composables y vistas Vue. |
| E2E | Playwright | Validar flujos criticos desde el navegador. |

## Puedo ejecutar tests sin afectar a mi servidor local?

Si. Los tests unitarios y funcionales no necesitan tu servidor local corriendo y no tocan la base de datos normal:

| Tipo | Necesita servidor local? | Afecta datos reales? |
| --- | --- | --- |
| Backend unitario | No | No |
| Backend funcional | No | No, usa `db_hometab_test` |
| Frontend unitario | No | No, usa `jsdom` |
| Coverage frontend | No | No |
| Build frontend | No | No toca base de datos |
| E2E Playwright | Depende | Si apunta a DEV o local real, si crea datos |

La regla segura es: `composer test`, `npm run test:unit`, `npm run test:coverage` y `npm run build` se pueden ejecutar sin afectar al servidor local. `npm run test:e2e` solo debe ejecutarse contra una cuenta y entorno de pruebas.

## Que son PHPUnit, Vitest y Playwright

- **PHPUnit** prueba PHP/Symfony. Lo usamos para servicios y endpoints API.
- **Vitest** prueba Vue/JavaScript sin navegador real.
- **Playwright** abre un navegador real y prueba la aplicacion de principio a fin. Eso es E2E, "end to end".

## Ejemplos importantes de tests

Backend 2FA: el usuario con 2FA no recibe token directamente, recibe un `challengeId`.

```php
$user = $this->createUser('2fa.challenge@example.test');
$user->setTwoFactorEnabled(true);
$this->flush();

$this->json('POST', '/api/login_check', [
    'username' => '2fa.challenge@example.test',
    'password' => 'password-de-test',
]);

self::assertTrue($this->responseJson()['requiresTwoFactor']);
self::assertNotEmpty($this->responseJson()['challengeId']);
```

Backend permisos: un usuario no puede leer el chat de una casa ajena.

```php
$user = $this->createUser('chat.foreign@example.test');
$foreign = $this->createHousehold('Casa chat ajena');
$this->flush();

$this->client->request('GET', "/api/households/{$foreign->getId()}/chat/messages", [], [], $this->authHeaders($user));

self::assertResponseStatusCodeSame(403);
```

Frontend notificaciones: se mockea `http.get` y se comprueba el estado del composable.

```js
localStorage.setItem('token', 'jwt')
http.get.mockResolvedValueOnce({ data: { totalUnread: 1, households: [] } })

const notifications = useNotifications()
await notifications.fetchNotifications()

expect(http.get).toHaveBeenCalledWith('/notifications')
expect(notifications.totalUnread.value).toBe(1)
```

Frontend 2FA: la vista cambia al paso de codigo si el backend devuelve `requiresTwoFactor`.

```js
http.post.mockResolvedValueOnce({
  data: { requiresTwoFactor: true, challengeId: 'challenge-1', message: 'Codigo enviado' },
})

const wrapper = mountLogin()
await wrapper.find('button').trigger('click')
await flushPromises()

expect(wrapper.text()).toContain('Codigo enviado')
expect(localStorage.getItem('token')).toBeNull()
```

Playwright: abre navegador real, escribe en el login y comprueba que entra.

```js
await page.goto('/login')
await page.getByLabel('Email').fill(email)
await page.getByLabel(/contrasena|password/i).fill(password)
await page.getByRole('button', { name: /entrar|sign in/i }).click()

await expect(page).toHaveURL(/\/tabhub|\/households/)
```

## Configuracion desde cero

En el backend Symfony se ha anadido `symfony/test-pack`, `phpunit/phpunit` y `dama/doctrine-test-bundle`. La configuracion principal vive en `phpunit.dist.xml`, separa las suites `unit` y `functional`, y carga el entorno de test con `.env.test`.

La base de datos de test es MySQL y queda separada de desarrollo:

```text
db_hometab_test
```

La clase `tests/Support/ApiTestCase.php` centraliza la creacion de usuarios, hogares, miembros, cabeceras JWT y peticiones JSON. Asi evitamos repetir fixtures manuales en cada test funcional.

En el frontend Vue se ha anadido Vitest con jsdom, Vue Test Utils, Testing Library, coverage y Playwright. El archivo `tests/setup.js` prepara mocks globales como `localStorage`, `matchMedia` y la limpieza del DOM entre pruebas.

## Que comprobamos ahora

Backend:

- login correcto con JWT;
- error con contrasena incorrecta;
- bloqueo de usuario inactivo;
- listado solo de hogares propios;
- creacion de hogar y union por codigo;
- denegacion de acceso a hogares ajenos;
- ciclo basico de tareas, gastos y eventos;
- validaciones, como crear tareas sin titulo;
- rechazo de participantes que no pertenecen al hogar.

Frontend:

- configuracion HTTP, `baseURL`, JWT y assets;
- estado compartido de hogar y chat;
- tema visual y acento;
- login, error de login y redireccion si ya hay token;
- carga de hogares, 401 y navegacion al dashboard;
- resumen del dashboard, progreso, tareas y eventos.

E2E:

- login;
- apertura de Mis Tabs;
- creacion de hogar;
- preparacion de tarea, gasto y evento por API autenticada;
- comprobacion en navegador de que tarea, gasto y evento aparecen en sus pestañas;
- apertura de notificaciones, chat y multimedia;
- envio de mensaje por chat;
- creacion de playlist multimedia.

## Como funciona el E2E final

Playwright abre Chromium real y usa la web como una persona: entra en `/login`, escribe email y contrasena, acepta u omite el paso de 2FA si aparece, entra en Mis Tabs y navega a una casa.

El test final usa una estrategia mixta:

- crea la casa desde la interfaz, porque es un flujo visual critico;
- crea tarea, gasto y evento por API autenticada, usando el JWT guardado en `localStorage`;
- vuelve a la interfaz y comprueba que esos datos se renderizan en las pestañas de tareas, gastos y calendario;
- abre widgets reales de notificaciones, chat y multimedia.

Esta estrategia evita que el E2E sea fragil por formularios largos o labels ambiguos, pero sigue validando backend, permisos, token, rutas y renderizado real.

## Errores encontrados al estabilizar Playwright

Durante la puesta en marcha aparecieron problemas reales de navegador:

| Error | Causa | Solucion |
| --- | --- | --- |
| Chromium no existia | Playwright estaba instalado, pero no su navegador. | `npx playwright install chromium`. |
| Selector `Sign in` ambiguo | Habia boton de navbar y boton del formulario. | Usar `.login-card .btn-login`. |
| Prompt 2FA `Not now` | El login podia quedarse esperando una decision. | Esperar URL o prompt y omitirlo si aparece. |
| Textos duplicados | `My Tabs` o playlists aparecian en varios sitios. | Usar `heading`, `.first()` o zonas como `.desktop-house-tabs`. |
| Tarjetas clickables | `Create New Tab` no era un `<button>`. | Hacer clic sobre el texto/tarjeta real. |
| Chat con estados distintos | A veces abre sala, a veces lista de casas. | Soportar ambos estados antes de escribir. |

## Como esta hecha la web que prueba Playwright

HomeTab frontend es una SPA Vue con Vite y Vue Router. Las rutas principales que se prueban son:

- `/login`: acceso, JWT en `localStorage` y posible 2FA;
- `/tabhub`: listado de hogares en formato post-it;
- `/households/:id/dashboard`: panel de una casa;
- tabs internas: tareas, gastos, calendario y multimedia;
- widgets globales: notificaciones y chat.

Los tests priorizan selectores accesibles (`role`, `label`, `heading`, `textbox`). Cuando la UI no tiene un selector unico, se usan clases estables de componentes concretos.

## Como se ejecutan

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

`composer test:setup` crea y migra la base de datos de test. `npm run test:e2e` necesita credenciales E2E si queremos ejecutar los flujos reales contra backend y frontend.

E2E local con servidores ya levantados:

```powershell
cd C:\Users\naoct\Desktop\GENSYNC\Frontend\frontend-grup-6-gensync
$env:E2E_EMAIL="usuario-e2e@example.com"
$env:E2E_PASSWORD="password-de-pruebas"
$env:E2E_BASE_URL="http://127.0.0.1:5173"
$env:E2E_API_URL="http://127.0.0.1:8000/api"
$env:E2E_SKIP_WEBSERVER="1"
npm run test:e2e
```

## Integracion continua

Los workflows de GitHub Actions funcionan como puerta de calidad. El backend levanta un servicio MySQL, prepara la base de datos de test y ejecuta PHPUnit. El frontend instala dependencias, ejecuta Vitest y comprueba que el build de Vite funciona.

Cuando existan secrets E2E disponibles, Playwright tambien puede ejecutar los smoke tests de navegador:

```text
E2E_EMAIL
E2E_PASSWORD
E2E_API_URL
```

## Evidencia para la presentacion final

En la presentacion se puede demostrar que HomeTab no solo funciona en una demo manual. Tambien se puede ejecutar la bateria automatizada y explicar que cada test protege una parte critica:

- autenticacion;
- permisos por hogar;
- integridad de datos;
- validaciones;
- experiencia principal de usuario;
- regresiones antes de desplegar.

## Fase 2: cobertura critica

La segunda fase amplia las pruebas sobre los modulos con mas riesgo funcional:

| Modulo | Backend | Frontend |
| --- | --- | --- |
| 2FA | Challenge de login, codigo invalido, codigo valido, activar y desactivar. | Login con codigo, verificacion y activacion/omision de 2FA. |
| Perfil | Lectura, actualizacion, email duplicado, contrasena actual incorrecta y avatar invalido. | Carga de perfil, validacion de avatar, guardado y errores API. |
| Chat | Listar, crear, rechazar mensaje vacio, editar/borrar solo propios y bloquear hogar ajeno. | Abrir chat, cargar hogares, enviar mensaje y evitar envio vacio. |
| Notificaciones | Agrupacion por hogar, marcar una, marcar todas y bloquear notificaciones ajenas. | Fetch, markRead, markAllRead, polling, contador, empty state y navegacion. |
| Multimedia | Listar playlists, crear playlist, validar nombre, anadir video y bloquear hogar ajeno. | Carga de playlists, creacion, anadir video y errores API. |

La cobertura sigue siendo informativa. Todavia no bloquea CI, pero se mide con:

```bash
npm run test:coverage
```

## Secrets E2E en GitHub

Los E2E se ejecutan contra DEV real cuando existen:

```text
E2E_EMAIL
E2E_PASSWORD
E2E_API_URL
E2E_BASE_URL
```

`E2E_BASE_URL` apunta al frontend DEV publico. El usuario E2E debe ser estable, pertenecer a un hogar de pruebas y tener rol normal de usuario.

## Proximas pruebas recomendadas

- Chat de hogar: typing avanzado, imagenes y referencias multimedia.
- Perfil: subida valida de avatar y limpieza de ficheros antiguos.
- 2FA: expiracion de codigos y limite de intentos.
- Multimedia: busqueda de YouTube con servicio mockeado y rutas por query params.
- Seguridad: token invalido, rutas sin token, CORS y mas casos IDOR.
