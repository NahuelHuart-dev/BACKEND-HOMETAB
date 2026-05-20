# Guia d'estudi: backend d'administració, despeses compartides i IA

Aquest document resumeix el treball fet al backend Symfony perquè es pugui estudiar l'arquitectura sense haver d'anar saltant entre molts fitxers.

## 1. Objectiu general

El backend cobreix aquests blocs principals:

- Administració global per a `ROLE_SUPER_ADMIN`.
- Gestió completa de llars, usuaris, tasques, esdeveniments, calendari, configuració i invitacions des de vistes Twig.
- Despeses compartides amb participants, estat de pagament per persona i periodicitat.
- Chat de llar amb auditoria administrativa.
- Assistent IA funcional per al mòdul de digitalització.
- IA SQL amb OpenRouter per ajudar el superadministrador a consultar la base de dades.
- Documentació MkDocs servida des d'una ruta privada del backend.

## 2. Seguretat i accés

Fitxer principal:

```text
config/packages/security.yaml
```

Punts clau:

- `/api/login_check` fa el login manual per poder controlar el 2FA abans d'emetre JWT.
- `/api` exigeix JWT i `ROLE_USER`.
- `/admin` exigeix `ROLE_SUPER_ADMIN`.
- El firewall `main` utilitza `App\Security\LoginFormAuthenticator` per al login web de Symfony.
- La jerarquia de rols fa que `ROLE_SUPER_ADMIN` hereti `ROLE_ADMIN` i `ROLE_USER`.

En resum: el frontend Vue entra per API/JWT; el backend Twig entra per sessió web i només el superadministrador pot veure el panell intern.

## 3. Controlador d'administració

Fitxer:

```text
src/Controller/twig/AdminController.php
```

Aquest controlador és el centre de les vistes del backend. Té `#[Route('/admin')]` i `#[IsGranted('ROLE_SUPER_ADMIN')]`, per tant tot el que conté queda protegit per a superadministrador.

Rutes importants:

- `GET /admin/dashboard`: llista totes les llars a la vista de Tabs.
- `POST /admin/households/create`: crea una llar nova i assigna el superadministrador com a propietari.
- `GET /admin/households/{id}/{section}`: vista principal d'una llar. Seccions: `summary`, `expenses`, `tasks`, `calendar`, `settings`.
- `POST /admin/households/{id}/invite-code`: canvia el codi d'invitació.
- `POST /admin/households/{id}/expenses/save/{expenseId?}`: crea o edita una despesa.
- `POST /admin/households/{id}/expenses/{expenseId}/delete`: dona de baixa una despesa.
- `POST /admin/households/{id}/tasks/save/{taskId?}`: crea o edita una tasca.
- `POST /admin/households/{id}/tasks/{taskId}/delete`: dona de baixa una tasca.
- `POST /admin/households/{id}/events/save/{eventId?}`: crea o edita un esdeveniment.
- `POST /admin/households/{id}/events/{eventId}/delete`: dona de baixa un esdeveniment.
- `GET /admin/users`: llista tots els usuaris.
- `POST /admin/users/{id}/save`: edita un usuari des del modal.

Funcions privades clau:

- `userBelongsToHousehold()`: evita assignar pagadors, tasques o esdeveniments a usuaris que no pertanyen a la llar.
- `selectedHouseholdUsers()`: llegeix els ids enviats pel formulari i retorna usuaris vàlids de la llar.
- `replaceExpenseShares()`: reconstrueix les quotes d'una despesa compartida i marca qui ha pagat.
- `isValidRecurrence()`: valida que cada periodicitat tingui només la dada que li correspon.
- `buildCalendarWeeks()`: crea la matriu setmanal del calendari mensual.
- `generateInviteCode()`: genera codis únics si l'administrador no n'escriu cap.
- `storeAvatarUpload()` i `storeAvatarCrop()`: guarden fotografies de perfil validades o retallades.

## 4. Vistes Twig del backend

Directori:

```text
templates/admin
```

Plantilles principals:

- `dashboard.html.twig`: pantalla de Tabs; mostra totes les llars i permet crear-ne una de nova.
- `household.html.twig`: vista principal per llar amb pestanyes internes de resum, despeses, tasques, calendari i configuració.
- `users.html.twig`: taula global d'usuaris amb modal d'edició.
- `records.html.twig`: plantilla compartida per a llistats globals de tasques, despeses i esdeveniments.
- `chat.html.twig`: vista de l'agent IA SQL.
- `sql.html.twig`: consola SQL de només lectura.
- `documentation_missing.html.twig`: pantalla d'avís si el build de MkDocs encara no existeix.

`household.html.twig` utilitza macros per no repetir formularis:

- `expense_form()`: modal de despesa amb dades bàsiques, pagador, participants i periodicitat.
- `task_form()`: modal de tasca.
- `event_form()`: modal d'esdeveniment.

## 5. Despeses compartides

Entitats:

```text
src/Entity/Expense.php
src/Entity/ExpenseShare.php
```

`Expense` representa la despesa general:

- Nom, descripció, import i categoria.
- Llar (`household`).
- Persona que ha pagat (`paidBy`).
- Tipus de pagament (`paymentType`): `shared` o `individual`.
- Periodicitat: `periodicity`, `recurrenceDayOfMonth`, `recurrenceWeekday`, `recurrenceTime`.

`ExpenseShare` representa la part que deu cada usuari:

- Despesa.
- Usuari.
- Import que deu (`amountOwed`).
- Si ha pagat (`isPaid`).
- Data de pagament (`paidAt`).

Flux de guardat:

1. L'administrador tria pagador, tipus de pagament i participants.
2. Si el tipus és `individual`, només participa el pagador.
3. Si el tipus és `shared`, participen els usuaris marcats.
4. `replaceExpenseShares()` esborra les quotes anteriors i crea quotes noves iguals.
5. Les caselles `paidShares[]` indiquen qui ja ha pagat.

La taula de despeses mostra l'estat de cada participant: pagat o pendent, amb l'import de cada quota.

## 6. Periodicitat de despeses

La periodicitat està limitada per la interfície i pel backend:

- Sense periodicitat: no exigeix dades extra.
- Mensual: exigeix dia del mes (`recurrenceDayOfMonth`).
- Setmanal: exigeix dia de la setmana (`recurrenceWeekday`).
- Diària: exigeix hora (`recurrenceTime`).

El JavaScript de `assets/app.js` oculta els camps que no corresponen. El backend torna a validar amb `isValidRecurrence()` per no confiar només en el navegador.

## 7. Calendari

El calendari viu a `household.html.twig` i s'alimenta des de `AdminController::buildCalendarWeeks()`.

Funcionalitats:

- Vista mensual real en una graella de 7 columnes.
- Navegació al mes anterior i següent.
- Selector de mes i any amb `<input type="month">`.
- Clic en un dia per crear un esdeveniment amb data inicial precarregada.
- Clic en un esdeveniment per editar-lo.

El mes actual es controla amb query string:

```text
?month=YYYY-MM
```

## 8. Perfil i avatars

Fitxers:

```text
src/Controller/twig/ProfileController.php
templates/profile/index.html.twig
src/Entity/User.php
templates/admin/users.html.twig
```

Cada usuari pot tenir:

- `avatar`: ruta a una fotografia pujada.
- `avatarIcon`: icona predefinida de PrimeIcons.

La interfície permet:

- Pujar fotografia.
- Veure previsualització abans de guardar.
- Ajustar zoom.
- Guardar un retall quadrat normalitzat en PNG.
- Treure la fotografia i tornar a utilitzar una icona.

La validació evita dependre de `symfony/mime`: s'utilitzen `getimagesize()` i `getimagesizefromstring()`.

## 9. Tema visual del backend

Fitxers:

```text
templates/base.html.twig
assets/app.js
assets/styles/hometab.css
public/hometab-logo.svg
```

El backend replica l'estil del frontend Vue:

- Logo real de HomeTab.
- Fons de paper amb quadrícula, línies o llis.
- Mode fosc i clar.
- Panell lateral de tema.
- Selector de color d'accent.

La configuració es desa a `localStorage` amb `hometab_config`, igual que al frontend.

## 10. IA SQL amb OpenRouter

Fitxers:

```text
src/Service/SqlAIAssistant.php
templates/admin/chat.html.twig
config/services.yaml
```

Variables d'entorn:

- `OPENROUTER_API_KEY`: clau tipus `sk-or-v1-...`.
- `OPENROUTER_MODELS`: llista separada per comes.

Flux:

1. El superadministrador escriu una pregunta en llenguatge natural.
2. `SqlAIAssistant::generateSqlQuery()` construeix un prompt amb l'esquema de base de dades.
3. Crida `https://openrouter.ai/api/v1/chat/completions`.
4. Accepta només resposta SQL i extreu el bloc `sql`.
5. Si OpenRouter retorna 401, mostra un missatge clar sobre la clau.
6. Si fallen tots els models, retorna un comentari SQL amb l'error.

Regles del prompt:

- Només consultes `SELECT`.
- No `INSERT`, `UPDATE`, `DELETE` ni `DROP`.
- Resposta en SQL, sense explicacions.

## 11. Assistent IA del mòdul de digitalització

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

Aquest assistent ajuda l'usuari autenticat a consultar informació de la seva llar:

- Tasques pendents.
- Esdeveniments propers.
- Pagaments pendents.
- Balances.
- Resum general.

La primera versió és de només consulta. No modifica dades i només treballa amb informació de les llars de l'usuari.

## 12. Migracions rellevants

Directori:

```text
migrations
```

Migracions destacades:

- `Version20260503010000.php`: esquema base.
- `Version20260503013000.php`: camps de despeses compartides i periodicitat.
- `Version20260503014500.php`: camp `avatar_icon` per a usuaris.
- `Version20260506090000.php`: verificació en dos passos.
- `Version20260511194500.php`: borrat lògic i camps d'auditoria.
- `Version20260512193000.php`: chat de llar.
- `Version20260512200000.php`: auditoria d'accés administratiu al chat.

## 13. Formularis Symfony

Directori:

```text
src/Form
```

Formularis disponibles:

- `UserType`: crear o editar usuaris amb contrasenya opcional en edició.
- `ExpenseType`: formulari base de despesa amb pagador i participants no mapats.
- `TaskType`: formulari base de tasques.
- `EventType`: formulari base d'esdeveniments.
- `RegistrationFormType`: registre d'usuari.

Les vistes noves d'administració fan servir formularis Twig manuals en modals per tenir més control visual i d'experiència d'usuari.

## 14. Checklist d'estudi

1. Comença per `AdminController`.
2. Mira `templates/admin/dashboard.html.twig`.
3. Continua amb `templates/admin/household.html.twig`.
4. Estudia `Expense` i `ExpenseShare` conjuntament.
5. Revisa `assets/app.js` per entendre modals, tema, periodicitat i retall d'avatar.
6. Revisa `AssistantService` per al mòdul de digitalització.
7. Revisa `SqlAIAssistant` per entendre la IA SQL administrativa.
8. Mira `security.yaml` per entendre qui pot accedir a cada zona.
