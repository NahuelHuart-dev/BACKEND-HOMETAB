# HomeTab Backend

Backend Symfony de HomeTab, una aplicacion fullstack para gestionar la convivencia en hogares compartidos. Este repositorio contiene la API REST usada por el frontend Vue, el backoffice Twig para superadministracion, la documentacion MkDocs protegida y la configuracion de Swagger/OpenAPI.

## Estado Del Proyecto

| Area | Estado |
| --- | --- |
| API REST | Implementada con Symfony, Doctrine y JWT |
| Backoffice Twig | Implementado para `ROLE_SUPER_ADMIN` |
| MkDocs | Servido desde `/admin/documentacio` con login de superadmin |
| Swagger UI | Disponible en `/api/doc` |
| Tests backend | PHPUnit unitario y funcional |
| Deploy DEV/PROD | Workflows preparados; dependen de secrets del repositorio |

Las URLs publicas concretas dependen del entorno desplegado. En local, las rutas principales son:

| Recurso | URL local |
| --- | --- |
| API | `http://localhost:8000/api` |
| Swagger UI | `http://localhost:8000/api/doc` |
| OpenAPI JSON | `http://localhost:8000/api/doc.json` |
| Backoffice | `http://localhost:8000/admin` |
| MkDocs | `http://localhost:8000/admin/documentacio` |

## Stack

Backend:

- PHP `>=8.2`, usado en CI con PHP `8.3`.
- Symfony `7.4`.
- Doctrine ORM y Doctrine Migrations.
- Lexik JWT Authentication Bundle.
- Nelmio CORS Bundle.
- Nelmio API Doc Bundle para Swagger/OpenAPI.
- Symfony Mailer, Twig y Webpack Encore.
- MySQL `8.0`.

Testing y documentacion:

- PHPUnit.
- DAMA Doctrine Test Bundle.
- MkDocs Material.
- Mermaid dentro de MkDocs.

## Funcionalidades Principales

- Autenticacion JWT para la SPA.
- 2FA opcional por correo.
- Recuperacion de contrasena por codigo y token temporal.
- Hogares con miembros, roles e invitaciones.
- Tareas, gastos compartidos y eventos.
- Chat de hogar, notificaciones y multimedia.
- Perfil de usuario con avatar.
- Backoffice Twig para superadministracion.
- Asistente IA y consola SQL interna del backoffice.

## Instalacion Local

```bash
composer install
cp .env .env.local
```

Configura `.env.local` con al menos:

```dotenv
APP_SECRET=genera-un-secret-local
DATABASE_URL="mysql://root:@127.0.0.1:3306/db_hometab?serverVersion=8.0.43&charset=utf8mb4"
JWT_PASSPHRASE=passphrase-local
MAILER_DSN="smtp://localhost:1025"
MAILER_FROM="hometab-local@example.com"
```

Genera las claves JWT:

```bash
php bin/console lexik:jwt:generate-keypair
```

Prepara la base de datos:

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate -n
php bin/console doctrine:fixtures:load -n
```

Compila los assets Twig:

```bash
npm install
npm run build
```

Instala tambien los assets publicos de bundles Symfony. Swagger UI los necesita para cargar CSS/JS desde `/bundles/nelmioapidoc` sin depender de un CDN externo:

```bash
php bin/console assets:install public --symlink --relative
```

Arranca el servidor:

```bash
symfony server:start
```

## Tests

```bash
composer test:setup
composer test
composer test:unit
composer test:functional
```

La suite funcional usa `.env.test` y la base `db_hometab_test`, separada de la base normal.

## Documentacion

MkDocs:

```bash
python -m pip install -r docs/mkdocs/requirements.txt
python -m mkdocs build -f docs/mkdocs/mkdocs.yml --strict
```

La documentacion generada queda en `docs/mkdocs/site` y se sirve protegida desde:

```text
/admin/documentacio
```

Swagger:

```text
/api/doc
/api/doc.json
```

Swagger muestra el contrato vivo de rutas API. MkDocs explica el contexto, flujos, decisiones tecnicas, ejemplos de codigo y manual de uso.

## Estructura

```text
backend-grup-6-gensync/
├── src/
│   ├── Controller/Api/      # API REST JSON para Vue
│   ├── Controller/twig/     # Backoffice Twig y documentacion protegida
│   ├── Entity/              # Entidades Doctrine
│   ├── Repository/          # Consultas especificas
│   ├── Service/             # Logica transversal
│   └── Security/            # Login web del backoffice
├── config/                  # Symfony, seguridad, JWT, CORS, Swagger
├── migrations/              # Migraciones Doctrine
├── templates/               # Vistas Twig
├── tests/                   # PHPUnit unitario y funcional
├── docs/mkdocs/             # Documentacion MkDocs
├── .github/workflows/       # CI/CD backend
└── Dockerfile               # Imagen usada por el workflow de produccion
```

## Seguridad

- JWT firmado con claves RSA fuera del repositorio.
- Firewalls separados: API stateless y backoffice con sesion.
- `ROLE_SUPER_ADMIN` para `/admin/*` y MkDocs.
- Comprobaciones de pertenencia a hogar para evitar IDOR.
- CORS limitado por `CORS_ALLOW_ORIGIN`.
- Imagenes base64 validadas antes de guardarse.
- Soft delete en recursos principales donde aplica.

## Troubleshooting Rapido

Si faltan claves JWT:

```bash
php bin/console lexik:jwt:generate-keypair
```

Si falla MySQL:

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate -n
```

Si la documentacion protegida muestra que falta el build:

```bash
python -m mkdocs build -f docs/mkdocs/mkdocs.yml --strict
```

Si Symfony no detecta cambios de configuracion:

```bash
php bin/console cache:clear
```
