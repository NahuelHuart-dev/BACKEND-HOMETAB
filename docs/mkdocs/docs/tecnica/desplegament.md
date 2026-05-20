# Desplegament i operacio

## Requisits backend

- PHP 8.2 o superior.
- Composer.
- MySQL o MariaDB.
- Extensions PHP habituals de Symfony.
- Claus JWT generades.
- Variables d'entorn configurades.

## Instal.lacio backend

```bash
cd backend-grup-6-gensync
composer install
php bin/console lexik:jwt:generate-keypair
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
```

## Execucio backend local

```bash
php -S localhost:8000 -t public
```

URL:

```text
http://localhost:8000
```

## Requisits frontend

- Node.js compatible amb `^20.19.0 || >=22.12.0`.
- npm.

## Execucio frontend local

```bash
cd frontend-grup-6-gensync
npm install
npm run dev
```

URL habitual:

```text
http://localhost:5173
```

## Variables d'entorn importants

| Variable | Funcio |
| --- | --- |
| `DATABASE_URL` | Connexio a MySQL. |
| `JWT_SECRET_KEY` | Clau privada JWT. |
| `JWT_PUBLIC_KEY` | Clau publica JWT. |
| `JWT_PASSPHRASE` | Passphrase JWT. |
| `MAILER_DSN` | Enviament de correu. |
| `MAILER_FROM` | Remitent dels correus 2FA. |
| `OPENROUTER_API_KEY` | Clau per a IA SQL administrativa. |
| `OPENROUTER_MODELS` | Models disponibles per IA SQL. |

## Build de la documentacio

```bash
cd backend-grup-6-gensync
python -m pip install -r docs/mkdocs/requirements.txt
python -m mkdocs build -f docs/mkdocs/mkdocs.yml
```

La documentacio queda a:

```text
docs/mkdocs/site
```

I s'accedeix per:

```text
http://localhost:8000/admin/documentacio
```

## Validacions recomanades

```bash
php bin/console lint:yaml config
php bin/console lint:twig templates
php bin/console debug:router
php bin/console doctrine:schema:validate
npm run build
```

Per MkDocs:

```bash
python -m mkdocs build -f docs/mkdocs/mkdocs.yml --strict
```

## Consideracions de produccio

- No pujar `.env.local`.
- No pujar claus JWT privades.
- Configurar HTTPS.
- Configurar CORS nomes per dominis coneguts.
- Usar SMTP real o servei transaccional.
- Moure avatars a storage extern si el projecte escala.
- Generar MkDocs en CI/CD abans de desplegar backend.
