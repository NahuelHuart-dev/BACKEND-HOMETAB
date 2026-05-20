# HomeTab Backend - Document tècnic de canvis realitzats

Aquest document resumeix i explica els canvis treballats al backend de HomeTab. Serveix com a mapa tècnic per entendre què s'ha tocat, per què s'ha tocat i com encaixen les peces.

No inclou secrets reals. Les claus SMTP, OpenRouter i JWT han de viure a `.env.local`, `.env.dev.local` o a la configuració segura de l'entorn, mai al repositori.

## Índex

1. Arquitectura general del backend després dels canvis
2. Verificació en dos passos opcional per correu
3. Enviament de correu: Symfony Mailer, Mailpit i SMTP real
4. Login Twig del backend i bloqueig a usuaris no superadministradors
5. Panell d'administració, capçalera i navegació agrupada
6. Selector de llars
7. Grids globals d'administració
8. Borrat lògic: alta, baixa i estat actiu
9. Perfil, fotografia i avatar
10. IA SQL i consola SQL integrada
11. Assistent IA del mòdul de digitalització
12. Fixtures i usuaris de prova
13. Canvis visuals i tema
14. Migracions
15. Fitxers principals modificats
16. Validacions realitzades
17. Consideracions futures

## 1. Arquitectura general del backend després dels canvis

El backend Symfony queda dividit en dues grans superfícies:

- API JSON per al frontend Vue.
- Backend Twig per a administració i eines internes.

L'API funciona amb JWT i usuaris normals. El backend Twig queda reservat per a superadministradors.

Rutes principals:

```text
API login:
POST /api/login_check
POST /api/login/verify

API 2FA:
POST /api/2fa/enable/start
POST /api/2fa/enable/verify
POST /api/2fa/disable

API funcional:
GET|POST /api/households
GET|POST /api/households/{homeId}/tasks
GET|POST /api/households/{homeId}/expenses
GET|POST /api/households/{homeId}/events
GET|POST /api/households/{homeId}/chat/messages
POST /api/assistant/chat

Backend admin:
GET /admin/dashboard
GET /admin/users
GET /admin/tasks
GET /admin/events
GET /admin/expenses
GET|POST /admin/sql
GET /admin/chat
GET /admin/documentacio
```

Fitxers importants:

```text
src/Controller/Api/AuthController.php
src/Controller/Api/AssistantController.php
src/Controller/Api/ChatApiController.php
src/Controller/twig/AdminController.php
src/Controller/twig/AdminChatController.php
src/Controller/twig/AdminDocumentationController.php
src/Controller/twig/ProfileController.php
src/Controller/twig/SecurityController.php
src/Security/LoginFormAuthenticator.php
src/Entity/User.php
src/Entity/TwoFactorCode.php
src/Entity/Task.php
src/Entity/Expense.php
src/Entity/Event.php
src/Entity/HouseholdMessage.php
src/Entity/ChatAccessLog.php
templates/base.html.twig
templates/admin/users.html.twig
templates/admin/records.html.twig
templates/admin/sql.html.twig
templates/profile/index.html.twig
assets/app.js
assets/styles/hometab.css
docs/mkdocs
```

## 2. Verificació en dos passos opcional per correu

S'ha implementat verificació en dos passos opcional per email.

Comportament final:

- Si un usuari no té 2FA activat, pot entrar normalment.
- Després del login o del registre se li pot proposar activar el 2FA.
- Si accepta, rep un codi per email.
- El 2FA només queda actiu quan verifica aquest codi.
- Si l'usuari ja té 2FA activat, el login es divideix en dos passos:
  - email i contrasenya
  - codi rebut per correu
- No es lliura el JWT final fins que el segon pas és correcte.

### Camps afegits a `User`

Fitxer:

```text
src/Entity/User.php
```

Camps:

```php
#[ORM\Column(options: ['default' => false])]
private bool $twoFactorEnabled = false;

#[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
private ?\DateTimeInterface $twoFactorEnabledAt = null;
```

Mètodes rellevants:

```php
public function isTwoFactorEnabled(): bool
{
    return $this->twoFactorEnabled;
}

public function setTwoFactorEnabled(bool $twoFactorEnabled): static
{
    $this->twoFactorEnabled = $twoFactorEnabled;
    $this->twoFactorEnabledAt = $twoFactorEnabled ? new \DateTime() : null;

    return $this;
}
```

### Entitat `TwoFactorCode`

Fitxer:

```text
src/Entity/TwoFactorCode.php
```

Desa codis temporals de 2FA sense guardar el codi en clar.

Camps conceptuals:

```text
id
user
challengeId
codeHash
purpose
expiresAt
usedAt
failedAttempts
createdAt
```

Propòsits:

```php
TwoFactorCode::PURPOSE_ENABLE
TwoFactorCode::PURPOSE_LOGIN
```

### Servei `TwoFactorService`

Fitxer:

```text
src/Service/TwoFactorService.php
```

Responsabilitats:

- Generar codi numèric.
- Crear `challengeId`.
- Fer hash del codi.
- Guardar el registre `TwoFactorCode`.
- Enviar el correu.
- Verificar codis.
- Marcar codis com a utilitzats.
- Controlar caducitat i intents fallits.

### Servei `TwoFactorEmailSender`

Fitxer:

```text
src/Service/TwoFactorEmailSender.php
```

Responsabilitat:

- Construir l'email.
- Utilitzar Symfony Mailer.
- Enviar el codi al correu de l'usuari.

El remitent ve de variable d'entorn:

```yaml
App\Service\TwoFactorEmailSender:
    arguments:
        $fromAddress: '%env(string:MAILER_FROM)%'
```

### Endpoints API de 2FA

Fitxer:

```text
src/Controller/Api/AuthController.php
```

Endpoints:

```text
POST /api/login_check
POST /api/login/verify
POST /api/2fa/enable/start
POST /api/2fa/enable/verify
POST /api/2fa/disable
```

## 3. Enviament de correu: Symfony Mailer, Mailpit i SMTP real

S'ha instal·lat i configurat Symfony Mailer.

Fitxers:

```text
composer.json
composer.lock
symfony.lock
config/packages/mailer.yaml
compose.override.yaml
```

Configuració:

```yaml
framework:
    mailer:
        dsn: '%env(MAILER_DSN)%'
```

### Mailpit en desenvolupament

Servei definit a:

```text
compose.override.yaml
```

Ports:

```text
SMTP: localhost:1025
UI:   http://localhost:8025
```

Mailpit permet provar correus localment sense enviar correus reals.

### SMTP real

Quan es configura SMTP real, les credencials han de viure fora del repositori.

Variables:

```dotenv
MAILER_DSN=smtp://usuari%40gmail.com:APP_PASSWORD@smtp.gmail.com:587
MAILER_FROM=usuari@gmail.com
```

En Symfony, `.env.dev.local` té prioritat en entorn `dev`.

## 4. Login Twig del backend i bloqueig a no superadministradors

El backend Twig queda restringit a superadministradors.

Fitxers:

```text
src/Security/LoginFormAuthenticator.php
src/Controller/twig/SecurityController.php
config/packages/security.yaml
templates/security/login.html.twig
templates/base.html.twig
```

Regles:

```yaml
- { path: ^/admin, roles: ROLE_SUPER_ADMIN }
- { path: ^/(dashboard|task|event|expense|profile), roles: ROLE_SUPER_ADMIN }
```

Si un usuari autenticat no té `ROLE_SUPER_ADMIN`, s'invalida la sessió i torna al login.

El header s'oculta a les rutes d'autenticació per evitar pantalles confuses.

## 5. Panell d'administració, capçalera i navegació agrupada

La capçalera es va reorganitzar per evitar saturació.

Estructura final:

```text
Tabs
Administració
  - Usuaris
  - Tasques
  - Esdeveniments
  - Despeses
  - Chats
SQL
  - IA SQL
  - Consola SQL
  - Documentació
```

La navegació utilitza `<details>` i `<summary>` per desplegables simples sense dependències addicionals.

Fitxers:

```text
templates/base.html.twig
assets/styles/hometab.css
```

## 6. Selector de llars

El menú mostra un selector `<select>` per canviar de llar.

Motivació:

- Amb moltes llars, una llista vertical creix massa.
- Un selector ocupa menys espai.
- El canvi de context és més clar.

Es va crear una extensió Twig:

```text
src/Twig/AdminHouseholdExtension.php
```

Funció:

```php
admin_households()
```

Així:

- El superadministrador veu totes les llars.
- L'usuari normal veuria només les seves llars.

## 7. Grids globals d'administració

S'han creat vistes globals per controlar informació des del backend.

Rutes:

```text
/admin/users
/admin/tasks
/admin/events
/admin/expenses
```

Plantilles:

```text
templates/admin/users.html.twig
templates/admin/records.html.twig
```

### Grid d'usuaris

Mostra:

- avatar o fotografia
- nom
- email
- telèfon
- llars
- rol
- estat
- data d'alta i baixa
- accions

Filtres:

- text (`q`)
- llar (`household`)
- rol (`role`)
- estat (`status`)

### Grids de tasques, despeses i esdeveniments

La plantilla `records.html.twig` serveix per als tres tipus i canvia columnes segons `type`.

Filtres habituals:

- estat actiu o baixa
- llar
- usuari relacionat
- text per títol

## 8. Borrat lògic: alta, baixa i estat actiu

S'ha canviat el model per no eliminar registres funcionals importants.

Motivació:

- No perdre històric.
- Poder auditar errors.
- Poder consultar elements donats de baixa des d'administració.
- Evitar inconsistències quan altres registres apunten a aquestes dades.

Camps destacats:

```text
isActive
createdAt
disabledAt
```

En comptes de:

```php
$entityManager->remove($task);
```

Es fa:

```php
$task->setIsActive(false);
```

Aplicat a tasques, despeses i esdeveniments.

## 9. Perfil, fotografia i avatar

Fitxers:

```text
templates/profile/index.html.twig
src/Controller/twig/ProfileController.php
src/Controller/twig/AdminController.php
assets/app.js
assets/styles/hometab.css
```

Regla:

- Si l'usuari té fotografia, es mostra la fotografia i es pot eliminar.
- Si no té fotografia, pot triar una icona o pujar una imatge.

El retall es fa al navegador amb `canvas` i s'envia com a base64.

El backend valida la imatge i desa el fitxer a:

```text
public/uploads/avatars
```

Quan es reemplaça o elimina una fotografia, el backend intenta eliminar el fitxer anterior de forma segura.

## 10. IA SQL i consola SQL integrada

Hi ha dues eines SQL internes:

- IA SQL: genera consultes de lectura a partir de llenguatge natural.
- Consola SQL: executa consultes de només lectura.

### IA SQL

Fitxers:

```text
src/Service/SqlAIAssistant.php
src/Controller/Api/ChatApiController.php
templates/admin/chat.html.twig
```

Rutes:

```text
GET /admin/chat
POST /admin/api/chat
```

### Consola SQL

Fitxers:

```text
src/Controller/Api/ChatApiController.php
templates/admin/sql.html.twig
```

Ruta:

```text
GET|POST /admin/sql
```

Permès:

```text
SELECT
SHOW
DESCRIBE
DESC
EXPLAIN
```

Bloquejat:

```text
INSERT
UPDATE
DELETE
DROP
ALTER
TRUNCATE
CREATE
consultes amb ;
```

## 11. Assistent IA del mòdul de digitalització

El mòdul de digitalització és l'assistent IA funcional de HomeTab.

Fitxers:

```text
src/Controller/Api/AssistantController.php
src/Service/Assistant/AssistantService.php
src/Service/Assistant/AssistantContextBuilder.php
Assistent_IA.md
```

Endpoint:

```text
POST /api/assistant/chat
```

Pot respondre sobre:

- Tasques pendents.
- Esdeveniments propers.
- Pagaments compartits.
- Balances.
- Resum general de la llar.

Limitacions:

- Només consulta dades.
- No modifica informació.
- Només treballa amb llars de l'usuari autenticat.
- Si la pregunta no és de HomeTab, ho indica.

## 12. Fixtures i usuaris de prova

Fitxer:

```text
src/DataFixtures/AppFixtures.php
```

Document:

```text
docs/test-users.md
```

Usuaris documentats:

```text
hometab.admin@gmail.com
naoctubre@gmail.com
jamon141006@gmail.com
anna.demo@hometab.local
```

Contrasenya de prova:

```text
HomeTab2026!Test
```

El superadministrador té `ROLE_SUPER_ADMIN`. Els usuaris normals tenen `ROLE_USER`.

## 13. Canvis visuals i tema

Fitxers:

```text
assets/styles/hometab.css
assets/app.js
templates/base.html.twig
templates/admin/users.html.twig
templates/profile/index.html.twig
```

Millores:

- Logo de HomeTab.
- Tema clar i fosc.
- Colors d'accent.
- Fons de paper.
- Taules més amples.
- Panell de configuració.
- Integració visual amb el frontend.

Variables destacades:

```css
--color-accent
--accent-bg
--accent-bg-subtle
--accent-border
--accent-bg-btn
--color-on-accent
```

## 14. Migracions

Migracions destacades:

```text
migrations/Version20260506090000.php
migrations/Version20260511194500.php
migrations/Version20260512193000.php
migrations/Version20260512200000.php
```

`Version20260506090000` afegeix 2FA:

- `user.two_factor_enabled`
- `user.two_factor_enabled_at`
- taula `two_factor_code`

`Version20260511194500` afegeix borrat lògic:

- `user.disabled_at`
- `task.disabled_at`
- `task.is_active`
- `expense.created_at`
- `expense.disabled_at`
- `expense.is_active`
- `event.created_at`
- `event.disabled_at`
- `event.is_active`

Les migracions posteriors incorporen chat i auditoria administrativa.

## 15. Fitxers principals modificats

### Seguretat i login

```text
config/packages/security.yaml
src/Security/LoginFormAuthenticator.php
src/Controller/twig/SecurityController.php
templates/security/login.html.twig
templates/security/two_factor.html.twig
templates/security/two_factor_offer.html.twig
```

### 2FA

```text
src/Entity/TwoFactorCode.php
src/Repository/TwoFactorCodeRepository.php
src/Service/TwoFactorService.php
src/Service/TwoFactorEmailSender.php
src/Controller/Api/AuthController.php
src/Controller/Api/ApiRegistrationController.php
```

### Administració

```text
src/Controller/twig/AdminController.php
src/Controller/twig/AdminChatController.php
src/Controller/twig/AdminDocumentationController.php
templates/admin/users.html.twig
templates/admin/records.html.twig
templates/admin/documentation_missing.html.twig
templates/base.html.twig
src/Twig/AdminHouseholdExtension.php
```

### SQL i IA

```text
src/Controller/Api/ChatApiController.php
src/Service/SqlAIAssistant.php
templates/admin/chat.html.twig
templates/admin/sql.html.twig
src/Controller/Api/AssistantController.php
src/Service/Assistant/AssistantService.php
src/Service/Assistant/AssistantContextBuilder.php
```

### Perfil

```text
src/Controller/twig/ProfileController.php
templates/profile/index.html.twig
assets/app.js
assets/styles/hometab.css
```

### Entitats

```text
src/Entity/User.php
src/Entity/Task.php
src/Entity/Expense.php
src/Entity/Event.php
src/Entity/HouseholdMessage.php
src/Entity/ChatAccessLog.php
```

### Documentació

```text
docs/test-users.md
docs/backend-admin-ai-guide.md
docs/backend-worklog-full.md
docs/mkdocs
```

## 16. Validacions realitzades

Comandes utilitzades o recomanades:

```bash
php -l src/Controller/twig/ProfileController.php
php -l src/Controller/twig/AdminController.php
php -l src/Controller/Api/ChatApiController.php
php -l src/Controller/twig/AdminDocumentationController.php
```

Twig:

```bash
php bin/console lint:twig templates
```

YAML:

```bash
php bin/console lint:yaml config
php bin/console lint:yaml docs/mkdocs/mkdocs.yml
```

Rutes:

```bash
php bin/console debug:router
php bin/console debug:router app_admin_sql
php bin/console debug:router app_admin_documentation
```

Doctrine:

```bash
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:schema:validate
php bin/console doctrine:migrations:status
```

Assets:

```bash
npm run build
```

MkDocs:

```bash
python -m mkdocs build -f docs/mkdocs/mkdocs.yml --strict
```

## 17. Consideracions futures

### Fotografies de perfil

Actualment es guarden a:

```text
public/uploads/avatars
```

Per producció seria millor utilitzar un servei d'emmagatzematge extern:

- Cloudflare R2
- AWS S3
- Supabase Storage
- un altre storage compatible

### Consola SQL

Ara és de només lectura. Si algun dia es permet escriptura, caldria:

- mode explícit de risc
- doble confirmació
- CSRF
- logs d'auditoria
- registre d'usuari, SQL, data i resultat
- limitació per entorn

No és recomanable obrir `UPDATE` o `DELETE` en producció sense auditoria.

### Borrat lògic complet

Millores possibles:

- Repositoris amb mètodes `findActiveBy...`.
- Evitar repetir `isActive => true`.
- Índex de base de dades sobre `is_active`.
- Vistes per restaurar baixes.
- Camp `disabled_by_id` per saber qui ha donat de baixa un registre.

### Auditoria de logins

El 2FA desa reptes i codis, però no és un registre complet de login. Es podria crear una entitat `LoginAudit` amb:

```text
id
user
emailAttempted
ip
userAgent
success
failureReason
twoFactorRequired
createdAt
```

## Resum final

El backend ha passat de ser un panell administratiu bàsic a tenir:

- login amb 2FA opcional per email
- enviament real o local de correus
- backend bloquejat a superadministradors
- panell admin amb grids globals filtrables
- borrat lògic
- control d'usuaris millorat
- perfil amb fotografia o avatar alternatiu
- neteja de fitxers d'avatar
- selector de llars correcte per a superadministrador
- chat de llar amb auditoria administrativa
- assistent IA funcional per al mòdul de digitalització
- IA SQL administrativa
- consola SQL integrada de només lectura
- documentació MkDocs protegida
- sistema visual més coherent amb el frontend

El resultat és una base més segura, més administrable i millor preparada per a la presentació final.
