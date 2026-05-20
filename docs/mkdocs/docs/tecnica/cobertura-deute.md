# Cobertura actual i deute tècnic

## Resum executiu

HomeTab té una bateria de proves funcionals que cobreix els fluxos crítics d'autenticació, accés a llars i operacions bàsiques. La cobertura de frontend és parcial però representativa. Els tests E2E cobreixen el flux principal d'ús de l'aplicació.

---

## Cobertura backend (PHPUnit)

### Completament cobert ✅

| Àrea | Tests | Casos coberts |
|---|---|---|
| Autenticació JWT | `AuthApiTest` | Login correcte, credencials incorrectes, usuari inactiu |
| 2FA | `AuthTwoFactorApiTest` | Challenge generat, codi invàlid, codi vàlid |
| Llars | `HouseholdApiTest` | Llistar, crear, unir-se, codi invàlid, ja membre |
| Permisos de llar | `HouseholdResourceApiTest` | Llar aliena 403, participants invàlids |
| Tasques bàsiques | `HouseholdResourceApiTest` | Crear, llistar, títol buit 400 |
| Despeses bàsiques | `HouseholdResourceApiTest` | Crear compartida, participant aliè 400 |
| Events bàsics | `HouseholdResourceApiTest` | Crear, participant invàlid 400 |
| Xat | `HouseholdChatApiTest` | Llistar, crear, buit 400, llar aliena 403 |
| Notificacions | `NotificationApiTest` | Agrupació, marcar una, marcar totes, aliena 404 |
| Multimèdia | `MultimediaApiTest` | Playlists, crear, nom buit 400, llar aliena 403, vídeo afegit |
| Perfil | `ProfileApiTest` | Lectura, actualització, email duplicat, contrasenya incorrecta |
| Servei accés llar | `HouseholdAccessServiceTest` | Membre vàlid, no membre |

### Parcialment cobert ⚠️

| Àrea | Que falta |
|---|---|
| Tasques | Editar, eliminar, filtres per prioritat/estat |
| Despeses | Editar, eliminar, balance, liquidacions |
| Events | Editar, eliminar, múltiples participants |
| 2FA | Expiració de codis i test específic del límit d'intents |
| Perfil | Pujada d'avatar, eliminació d'avatar |
| Registre | Registre correcte, email duplicat, validacions |
| Password reset | Els tres passos del flux de recuperació |

### No cobert ❌

| Àrea | Risc |
|---|---|
| Reordenació de llars | Baix |
| Codi d'invitació personalitzat | Baix |
| Assistent IA | Alt (depèn de servei extern) |
| Xat: editar/eliminar missatges | Mig |
| Typing indicator | Baix |
| SafeImageStorage (unitari) | Mig |
| NotificationService (unitari) | Mig |

---

## Cobertura frontend (Vitest)

### Completament cobert ✅

| Component / Composable | Tests | Casos coberts |
|---|---|---|
| `useNotifications` | `useNotifications.test.js` | Fetch, mark read, mark all, polling, comptador |
| `useHouseState` | `useHouseState.test.js` | Estat global, setter, reset |
| `useTheme` | `useTheme.test.js` | Tema clar/fosc, persistència localStorage |
| `LoginView` | `LoginView.test.js` | Submit, error credencials, 2FA step, redirecció si token |
| `HouseChatWidget` | `HouseChatWidget.test.js` | Renderitzat, estat, enviament |
| `HouseNotificationsWidget` | `HouseNotificationsWidget.test.js` | Fetch, marcat, buit |
| `DashboardTab` | `DashboardTab.test.js` | Resum tasques, despeses, events |
| `MultimediaTab` | `MultimediaTab.test.js` | Playlists, creació, vídeo, error |
| `ProfileView` | `ProfileView.test.js` | Càrrega, avatar, guardat, errors |
| `TabHubView` | `TabHubView.test.js` | Llars, loading, error 401 |

### Parcialment cobert ⚠️

| Component | Que falta |
|---|---|
| `TasksTab` | Cap test actual. Crear, completar, filtrar |
| `ExpensesTab` | Cap test actual. Crear, balance, liquidació |
| `CalendarTab` | Cap test actual. Events, vista mensual |
| `SettingsTab` | Cap test actual. Membres, codi, editar llar |
| `useNavigation` | No testejat. Navegació a llars |
| `useAnimations` | No testejat (difícil sense navegador) |
| `http.js` | Té test propi per JWT i `assetUrl`; falta cobrir resposta 401 global perquè actualment no hi ha interceptor de resposta |

### No cobert ❌

| Àrea | Risc |
|---|---|
| `appNavbar.vue` | Mig — menú mòbil, hamburger, canvi de llar |
| `RegisterView.vue` | Baix — formulari de registre |
| `LandingView.vue` | Baix — pàgina de presentació |
| Protecció centralitzada de rutes | Mig — ara no hi ha guard global; la protecció depèn de token, vistes i backend |

---

## Cobertura E2E (Playwright)

El test `critical-flows.spec.js` cobreix el flux complet de l'aplicació:

| Flux | Cobert |
|---|---|
| Login (email + pass) | ✅ |
| Gestió del prompt 2FA | ✅ |
| Navegació a TabHub | ✅ |
| Crear llar via UI | ✅ |
| Crear tasca per API | ✅ |
| Crear despesa per API | ✅ |
| Crear event per API | ✅ |
| Verificar renderitzat de tasca | ✅ |
| Verificar renderitzat de despesa | ✅ |
| Verificar renderitzat d'event | ✅ |
| Obrir widget de notificacions | ✅ |
| Obrir widget de xat | ✅ |
| Enviar missatge al xat | ✅ |
| Crear playlist multimèdia | ✅ |
| Registre d'usuari | ❌ |
| Perfil i 2FA via UI | ❌ |
| Backoffice admin | ❌ |

---

## Deute tècnic prioritzat

### Prioritat Alta 🔴

| Deute | Impacte | Esforç |
|---|---|---|
| Tests TasksTab (Vitest) | Alt: és el mòdul principal d'ús diari | 2-3h |
| Tests ExpensesTab (Vitest) | Alt: afecta diners dels usuaris | 2-3h |
| Tests de registre i recuperació de contrasenya (PHPUnit) | Mig: flux d'entrada de nous usuaris | 2h |
| Rate limiting al login | Seguretat: brute force | 1h |

### Prioritat Mitjana 🟡

| Deute | Impacte | Esforç |
|---|---|---|
| Tests CalendarTab (Vitest) | Mig: mòdul complex amb molts estats | 3h |
| Tests editar/eliminar tasques i despeses (PHPUnit) | Mig: cobreix el cicle complet CRUD | 2h |
| Tests del flux de password reset (PHPUnit) | Mig: 3 endpoints encadenats | 1h |
| Test del límit d'intents 2FA | Seguretat: validar que el bloqueig configurat es manté | 1h |
| Validació d'email al registre | Qualitat | 3h |

### Prioritat Baixa 🟢

| Deute | Impacte | Esforç |
|---|---|---|
| Tests SettingsTab (Vitest) | Baix: UI de configuració poc crítica | 2h |
| Test de reordenació de llars (PHPUnit) | Baix: funcionalitat auxiliar | 1h |
| Blacklist JWT per revocació de tokens | Seguretat millora | 4h |
| Auditoria de canvis a entitats (Doctrine) | Compliance | 4h |
| E2E de registre d'usuari | Cobertura d'entrada | 2h |

---

## Mesures de cobertura

Per obtenir les mètriques actuals de cobertura:

### Backend

```bash
# Requereix Xdebug instal·lat
composer test:coverage
```

### Frontend

```bash
npm run test:coverage
```

Genera un informe HTML a `coverage/`. Obre `coverage/index.html` per veure la cobertura per arxiu.

---

## Riscos si no es cobreix

| Àrea sense tests | Risc concret |
|---|---|
| TasksTab | Regressions en filtres o llista no detectades fins a producció |
| Password reset | Errors en el flux de 3 passos que bloquegen usuaris |
| Rate limiting | Comptes vulnerables a brute force |
| Protecció centralitzada de rutes | Sense guard global, cal vigilar que cada vista privada gestioni bé token caducat o absent |
