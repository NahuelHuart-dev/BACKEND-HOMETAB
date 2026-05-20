# Variables d'entorn

HomeTab utilitza fitxers `.env` estàndard de Symfony. **Mai versionar valors secrets reals**.

## Jerarquia de fitxers

```
.env                    # Valors per defecte commitejats (sense secrets)
.env.local              # Sobreescritura local no commitejada (secrets reals locals)
.env.dev                # Valors per a l'entorn dev
.env.dev.local          # Secrets de dev locals
.env.test               # Valors per als tests (base de dades de test)
```

## Variables del Backend Symfony

### Aplicació

| Variable | Valor per defecte | Descripció |
|---|---|---|
| `APP_ENV` | `dev` | Entorn de Symfony: `dev`, `test`, `prod` |
| `APP_DEBUG` | `1` (dev) / `0` (prod) | Activa el mode debug |
| `APP_SECRET` | `change-me-in-env-local` | Secret de l'aplicació per a sessions i CSRF. **Canviar en .env.local** |
| `APP_SHARE_DIR` | `var/share` | Directori per a fitxers compartits interns |
| `DEFAULT_URI` | `http://localhost` | URI per generar URLs en context CLI |

### Base de dades

| Variable | Exemple | Descripció |
|---|---|---|
| `DATABASE_URL` | `mysql://root:@127.0.0.1:3306/db_hometab?serverVersion=8.0.43&charset=utf8mb4` | Connexió MySQL. Format: `mysql://user:pass@host:port/dbname?serverVersion=X` |

La base de dades de **test** es configura a `.env.test`:

```dotenv
DATABASE_URL="mysql://root:@127.0.0.1:3306/db_hometab_test?serverVersion=8.0.43&charset=utf8mb4"
```

### JWT (Lexik JWT Authentication)

| Variable | Valor per defecte | Descripció |
|---|---|---|
| `JWT_SECRET_KEY` | `%kernel.project_dir%/config/jwt/private.pem` | Ruta de la clau privada RSA |
| `JWT_PUBLIC_KEY` | `%kernel.project_dir%/config/jwt/public.pem` | Ruta de la clau pública RSA |
| `JWT_PASSPHRASE` | `change-me-in-env-local` | Passphrase de la clau privada. **Canviar en .env.local** |

Les claus es generen amb:

```bash
php bin/console lexik:jwt:generate-keypair
```

Les claus **no s'han de versionar**. A CI es generen automàticament al workflow.

### CORS (Nelmio CORS)

| Variable | Valor per defecte | Descripció |
|---|---|---|
| `CORS_ALLOW_ORIGIN` | `'^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$'` | Regex dels orígens permesos. En producció: URL exacta del frontend |

Exemple per a producció:

```dotenv
CORS_ALLOW_ORIGIN='^https://hometab\.es$'
```

### Mailer (Symfony Mailer)

| Variable | Exemple | Descripció |
|---|---|---|
| `MAILER_DSN` | `smtp://user:pass@smtp.example.com:587` | DSN del servidor de correu. En test: `null://null` |
| `MAILER_FROM` | `hometab@example.com` | Remitent dels correus (2FA, feedback, etc.) |

### Messenger

| Variable | Valor per defecte | Descripció |
|---|---|---|
| `MESSENGER_TRANSPORT_DSN` | `doctrine://default?auto_setup=0` | Transport per a cues asíncrones (poc usat en producció actual) |

### IA i integracions externes

| Variable | Exemple | Descripció |
|---|---|---|
| `OPENROUTER_API_KEY` | *(secret/opcional local)* | Clau de l'API OpenRouter per a l'assistent SQL i IA |
| `OPENROUTER_MODELS` | `meta-llama/llama-3.3-70b-instruct,...` | Models d'IA disponibles (llista separada per comes) |
| `YOUTUBE_API_KEY` | *(secret/opcional local)* | Clau de l'API de YouTube per a la cerca de vídeos |

---

## Variables del Frontend Vue

Configurades als fitxers `.env` i `.env.production` a `frontend-grup-6-gensync/`.

Totes les variables del frontend han de començar amb `VITE_` per ser accessibles en el codi.

| Variable | Exemple | Descripció |
|---|---|---|
| `VITE_API_URL` | `http://127.0.0.1:8000/api` en local o URL DEV/PROD real | URL base de l'API del backend. Si no es defineix, el frontend la dedueix automàticament |
| `VITE_ASSET_URL` | `http://127.0.0.1:8000` en local o URL DEV/PROD real | URL base per a assets (imatges d'avatar, etc.) |
| `BASE_URL` | `/` | Ruta base de l'SPA (configurat a Vite) |

El servei `src/services/http.js` determina la URL de l'API amb aquesta lògica:

```js
const isLocalFrontend = ['localhost', '127.0.0.1'].includes(window.location.hostname);
const localApiUrl = `${window.location.protocol}//${window.location.hostname}:8000/api`;
const baseURL = configuredApiUrl || (isLocalFrontend ? localApiUrl : '/api');
```

---

## Variables de CI/CD (GitHub Actions Secrets)

Configurades a **Settings → Secrets and variables → Actions** del repositori.

### Comuns (test)

| Secret | Descripció |
|---|---|
| `APP_SECRET` | Secret de l'aplicació per a l'entorn de test/deploy |

### Deploy DEV (EC2)

| Secret | Descripció |
|---|---|
| `DEV_SERVER_HOST` | IP o hostname del servidor DEV |
| `DEV_SERVER_USER` | Usuari SSH del servidor |
| `DEV_SERVER_SSH_KEY` | Clau privada SSH (PEM) |
| `DEV_BACKEND_PATH` | Ruta absoluta al backend al servidor |
| `DEV_DATABASE_URL` | URL de connexió MySQL del servidor DEV |
| `DEV_CORS_ALLOW_ORIGIN` | Regex CORS per a l'entorn DEV |
| `JWT_PRIVATE_KEY_B64` | Clau privada JWT en base64 |
| `JWT_PUBLIC_KEY_B64` | Clau pública JWT en base64 |
| `JWT_PASSPHRASE` | Passphrase de la clau JWT |
| `MAILER_DSN` | DSN del mailer per a DEV |
| `MAILER_FROM` | Remitent del mailer per a DEV |
| `OPENROUTER_API_KEY` | Clau OpenRouter per a DEV |
| `OPENROUTER_MODELS` | Models d'IA disponibles |
| `YOUTUBE_API_KEY` | Clau YouTube per a DEV |

### Deploy PROD (AWS ECS)

| Secret | Descripció |
|---|---|
| `AWS_ACCESS_KEY_ID` | Credencial AWS IAM |
| `AWS_SECRET_ACCESS_KEY` | Secret AWS IAM |
| `AWS_SESSION_TOKEN` | Token de sessió AWS (si s'usa MFA/AssumeRole) |
| `AWS_REGION` | Regió AWS (ex: `eu-west-1`) |
| `ECR_BACKEND_URI` | URI del repositori ECR per a la imatge Docker |
| `ECS_CLUSTER` | Nom del clúster ECS |
| `ECS_BACKEND_SERVICE` | Nom del servei ECS del backend |

### Tests E2E (Playwright)

| Secret | Descripció |
|---|---|
| `E2E_EMAIL` | Email de l'usuari de proves E2E |
| `E2E_PASSWORD` | Contrasenya de l'usuari E2E |
| `E2E_API_URL` | URL de l'API que usarà Playwright (ex: `http://127.0.0.1:8000/api` en local o la URL DEV real) |
| `E2E_BASE_URL` | URL del frontend que obrirà Playwright (ex: `http://127.0.0.1:5173` en local o la URL DEV real) |

---

## Configuració mínima per a entorn local

Copia i adapta al `.env.local` del backend:

```dotenv
APP_SECRET=genera-un-secret-aleatori
DATABASE_URL="mysql://root:@127.0.0.1:3306/db_hometab?serverVersion=8.0.43&charset=utf8mb4"
JWT_PASSPHRASE=la-teva-passphrase-local
MAILER_DSN="smtp://localhost:1025"
MAILER_FROM="hometab-local@example.com"
OPENROUTER_API_KEY=opcional-en-local
YOUTUBE_API_KEY=opcional-en-local
```

I al `.env` del frontend (o `.env.local`):

```dotenv
VITE_API_URL=http://localhost:8000/api
VITE_ASSET_URL=http://localhost:8000
```
