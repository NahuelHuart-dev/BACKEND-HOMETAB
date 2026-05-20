# Testing i qualitat

## Objectiu

HomeTab ha passat de validar l'MVP manualment a tenir una bateria de proves automatitzades. L'objectiu es detectar regressions abans que arribin a produccio i demostrar, de cara al producte final ABP, que l'aplicacio es robusta, verificable i mantenible.

Les proves cobreixen quatre capes:

| Capa | Eines | Finalitat |
| --- | --- | --- |
| Backend unitari | PHPUnit | Validar logica de negoci aillada. |
| Backend funcional | PHPUnit, Symfony WebTestCase, MySQL test | Validar endpoints reals de l'API. |
| Frontend unitari | Vitest, Vue Test Utils, Testing Library, jsdom | Validar serveis, composables i vistes Vue. |
| E2E | Playwright | Validar fluxos critics des del navegador. |

## Es poden executar sense afectar el servidor local?

Si. Les proves unitaries i funcionals no necessiten el servidor local corrent i no toquen la base de dades normal:

| Tipus | Necessita servidor local? | Afecta dades reals? |
| --- | --- | --- |
| Backend unitari | No | No |
| Backend funcional | No | No, usa `db_hometab_test` |
| Frontend unitari | No | No, usa `jsdom` |
| Coverage frontend | No | No |
| Build frontend | No | No toca base de dades |
| E2E Playwright | Depen | Si apunta a DEV o local real, crea dades |

La regla segura es: `composer test`, `npm run test:unit`, `npm run test:coverage` i `npm run build` es poden executar sense afectar el servidor local. `npm run test:e2e` nomes s'ha d'executar contra un usuari i entorn de proves.

## Que son PHPUnit, Vitest i Playwright

- **PHPUnit** prova PHP/Symfony. L'usem per serveis i endpoints API.
- **Vitest** prova Vue/JavaScript sense navegador real.
- **Playwright** obre un navegador real i prova l'aplicacio de principi a fi. Aixo es E2E, "end to end".

## Exemples importants de tests

Backend 2FA: l'usuari amb 2FA no rep token directament, rep un `challengeId`.

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

Backend permisos: un usuari no pot llegir el chat d'una llar aliena.

```php
$user = $this->createUser('chat.foreign@example.test');
$foreign = $this->createHousehold('Casa chat aliena');
$this->flush();

$this->client->request('GET', "/api/households/{$foreign->getId()}/chat/messages", [], [], $this->authHeaders($user));

self::assertResponseStatusCodeSame(403);
```

Frontend notificacions: es mockeja `http.get` i es comprova l'estat del composable.

```js
localStorage.setItem('token', 'jwt')
http.get.mockResolvedValueOnce({ data: { totalUnread: 1, households: [] } })

const notifications = useNotifications()
await notifications.fetchNotifications()

expect(http.get).toHaveBeenCalledWith('/notifications')
expect(notifications.totalUnread.value).toBe(1)
```

Frontend 2FA: la vista canvia al pas de codi si el backend retorna `requiresTwoFactor`.

```js
http.post.mockResolvedValueOnce({
  data: { requiresTwoFactor: true, challengeId: 'challenge-1', message: 'Codi enviat' },
})

const wrapper = mountLogin()
await wrapper.find('button').trigger('click')
await flushPromises()

expect(wrapper.text()).toContain('Codi enviat')
expect(localStorage.getItem('token')).toBeNull()
```

Playwright: obre navegador real, escriu al login i comprova que entra.

```js
await page.goto('/login')
await page.getByLabel('Email').fill(email)
await page.getByLabel(/contrasena|password/i).fill(password)
await page.getByRole('button', { name: /entrar|sign in/i }).click()

await expect(page).toHaveURL(/\/tabhub|\/households/)
```

## Configuracio feta des de zero

Al backend Symfony s'ha afegit `symfony/test-pack`, `phpunit/phpunit` i `dama/doctrine-test-bundle`. La configuracio principal viu a `phpunit.dist.xml`, separa les suites `unit` i `functional`, i carrega l'entorn de test amb `.env.test`.

La base de dades de test es MySQL i queda separada de desenvolupament:

```text
db_hometab_test
```

La classe `tests/Support/ApiTestCase.php` centralitza la creacio d'usuaris, llars, membres, capcaleres JWT i peticions JSON. Aixo evita repetir fixtures manuals a cada test funcional.

Al frontend Vue s'ha afegit Vitest amb jsdom, Vue Test Utils, Testing Library, coverage i Playwright. El fitxer `tests/setup.js` prepara mocks globals com `localStorage`, `matchMedia` i la neteja del DOM entre proves.

## Que comprovem ara

Backend:

- login correcte amb JWT;
- error amb contrasenya incorrecta;
- bloqueig d'usuari inactiu;
- llistat nomes de llars propies;
- creacio de llar i unio per codi;
- denegacio d'acces a llars alienes;
- cicle basic de tasques, despeses i esdeveniments;
- validacions, com crear tasques sense titol;
- rebuig de participants que no pertanyen a la llar.

Frontend:

- configuracio HTTP, `baseURL`, JWT i assets;
- estat compartit de llar i chat;
- tema visual i accent;
- login, error de login i redireccio si ja hi ha token;
- carrega de llars, 401 i navegacio a dashboard;
- resum del dashboard, progres, tasques i esdeveniments.

E2E:

- login;
- obertura de Mis Tabs;
- creacio de llar;
- preparacio de tasca, despesa i esdeveniment per API autenticada;
- comprovacio al navegador que tasca, despesa i esdeveniment apareixen a les pestanyes;
- obertura de notificacions, chat i multimedia;
- enviament de missatge pel chat;
- creacio de playlist multimedia.

## Com funciona l'E2E final

Playwright obre Chromium real i utilitza la web com una persona: entra a `/login`, escriu email i contrasenya, accepta o omet el pas de 2FA si apareix, entra a Mis Tabs i navega cap a una llar.

El test final utilitza una estrategia mixta:

- crea la llar des de la interfície, perque es un flux visual critic;
- crea tasca, despesa i esdeveniment per API autenticada, usant el JWT guardat a `localStorage`;
- torna a la interfície i comprova que aquestes dades es renderitzen a les pestanyes de tasques, despeses i calendari;
- obre widgets reals de notificacions, chat i multimedia.

Aquesta estrategia evita que l'E2E sigui fragil per formularis llargs o labels ambigus, pero continua validant backend, permisos, token, rutes i renderitzat real.

## Errors trobats estabilitzant Playwright

Durant la posada en marxa van apareixer problemes reals de navegador:

| Error | Causa | Solucio |
| --- | --- | --- |
| Chromium no existia | Playwright estava instal-lat, pero no el navegador. | `npx playwright install chromium`. |
| Selector `Sign in` ambigu | Hi havia boto de navbar i boto del formulari. | Usar `.login-card .btn-login`. |
| Prompt 2FA `Not now` | El login podia quedar esperant una decisio. | Esperar URL o prompt i ometre'l si apareix. |
| Textos duplicats | `My Tabs` o playlists apareixien a diversos llocs. | Usar `heading`, `.first()` o zones com `.desktop-house-tabs`. |
| Targetes clicables | `Create New Tab` no era un `<button>`. | Fer clic sobre el text/targeta real. |
| Chat amb estats diferents | A vegades obre sala, a vegades llista de llars. | Suportar els dos estats abans d'escriure. |

## Com esta feta la web que prova Playwright

HomeTab frontend es una SPA Vue amb Vite i Vue Router. Les rutes principals que es proven son:

- `/login`: acces, JWT a `localStorage` i possible 2FA;
- `/tabhub`: llistat de llars en format post-it;
- `/households/:id/dashboard`: panell d'una llar;
- tabs internes: tasques, despeses, calendari i multimedia;
- widgets globals: notificacions i chat.

Els tests prioritzen selectors accessibles (`role`, `label`, `heading`, `textbox`). Quan la UI no te un selector unic, s'utilitzen classes estables de components concrets.

## Com s'executen

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

`composer test:setup` crea i migra la base de dades de test. `npm run test:e2e` necessita credencials E2E si volem executar els fluxos reals contra backend i frontend.

E2E local amb servidors ja aixecats:

```powershell
cd C:\Users\naoct\Desktop\GENSYNC\Frontend\frontend-grup-6-gensync
$env:E2E_EMAIL="usuari-e2e@example.com"
$env:E2E_PASSWORD="password-de-proves"
$env:E2E_BASE_URL="http://127.0.0.1:5173"
$env:E2E_API_URL="http://127.0.0.1:8000/api"
$env:E2E_SKIP_WEBSERVER="1"
npm run test:e2e
```

## Integracio continua

Els workflows de GitHub Actions funcionen com a porta de qualitat. El backend aixeca un servei MySQL, prepara la base de dades de test i executa PHPUnit. El frontend instal-la dependencies, executa Vitest i comprova que el build de Vite funciona.

Quan hi hagi secrets E2E disponibles, Playwright tambe pot executar els smoke tests de navegador:

```text
E2E_EMAIL
E2E_PASSWORD
E2E_API_URL
```

## Evidencia per a la presentacio final

En la presentacio es pot demostrar que HomeTab no nomes funciona en una demo manual. Tambe es pot executar la bateria automatitzada i explicar que cada test protegeix una part critica:

- autenticacio;
- permisos per llar;
- integritat de dades;
- validacions;
- experiencia principal d'usuari;
- regressions abans de desplegar.

## Fase 2: cobertura critica

La segona fase amplia les proves sobre els moduls amb mes risc funcional:

| Modul | Backend | Frontend |
| --- | --- | --- |
| 2FA | Challenge de login, codi invalid, codi valid, activar i desactivar. | Login amb codi, verificacio i activacio/omissio de 2FA. |
| Perfil | Lectura, actualitzacio, email duplicat, contrasenya actual incorrecta i avatar invalid. | Carrega de perfil, validacio d'avatar, guardat i errors API. |
| Chat | Llistar, crear, rebutjar missatge buit, editar/esborrar nomes propis i bloquejar llar aliena. | Obrir chat, carregar llars, enviar missatge i evitar enviament buit. |
| Notificacions | Agrupacio per llar, marcar una, marcar totes i bloquejar notificacions alienes. | Fetch, markRead, markAllRead, polling, contador, empty state i navegacio. |
| Multimedia | Llistar playlists, crear playlist, validar nom, afegir video i bloquejar llar aliena. | Carrega de playlists, creacio, afegir video i errors API. |

La cobertura continua sent informativa. Encara no bloqueja CI, pero es mesura amb:

```bash
npm run test:coverage
```

## Secrets E2E a GitHub

Els E2E s'executen contra DEV real quan existeixen:

```text
E2E_EMAIL
E2E_PASSWORD
E2E_API_URL
E2E_BASE_URL
```

`E2E_BASE_URL` apunta al frontend DEV public. L'usuari E2E ha de ser estable, pertanyer a una llar de proves i tenir rol normal d'usuari.

## Proximes proves recomanades

- Chat de llar: typing avancat, imatges i referencies multimedia.
- Perfil: pujada valida d'avatar i neteja de fitxers antics.
- 2FA: expiracio de codis i limit d'intents.
- Multimedia: cerca de YouTube amb servei mockejat i rutes per query params.
- Seguretat: token invalid, rutes sense token, CORS i mes casos IDOR.
