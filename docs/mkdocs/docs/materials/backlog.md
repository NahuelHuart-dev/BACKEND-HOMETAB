# Backlog i estat del projecte

## Funcionalitats completades ✅

| Mòdul | Funcionalitat | Estat |
|---|---|---|
| Auth | Login JWT | ✅ Complet |
| Auth | Login amb 2FA per correu | ✅ Complet |
| Auth | Recuperació de contrasenya (3 passos) | ✅ Complet |
| Auth | Registre d'usuaris | ✅ Complet |
| Llars | Múltiples llars per usuari | ✅ Complet |
| Llars | Codi d'invitació de 6 caràcters | ✅ Complet |
| Llars | Rols: owner, admin, member | ✅ Complet |
| Llars | Reordenació de llars | ✅ Complet |
| Llars | Avatar per llar (imatge o icona) | ✅ Complet |
| Tasques | CRUD complet | ✅ Complet |
| Tasques | Prioritat, periodicitat, categoria | ✅ Complet |
| Tasques | Assignació a membre | ✅ Complet |
| Tasques | Borrat lògic | ✅ Complet |
| Despeses | CRUD complet | ✅ Complet |
| Despeses | Shares per participant | ✅ Complet |
| Despeses | Balance per membre | ✅ Complet |
| Despeses | Liquidacions automàtiques | ✅ Complet |
| Despeses | Tipus: individual / compartit | ✅ Complet |
| Despeses | Periodicitat i recurrència | ✅ Complet |
| Events | CRUD complet | ✅ Complet |
| Events | Participants, color, ubicació | ✅ Complet |
| Events | Borrat lògic | ✅ Complet |
| Xat | Missatges per llar | ✅ Complet |
| Xat | Editar i eliminar missatges propis | ✅ Complet |
| Xat | Typing indicator | ✅ Complet |
| Xat | Widget slide-up mòbil | ✅ Complet |
| Notificacions | Sistema per llar | ✅ Complet |
| Notificacions | Agrupació i badge | ✅ Complet |
| Notificacions | Polling automàtic | ✅ Complet |
| Multimèdia | Playlists de YouTube per llar | ✅ Complet |
| Multimèdia | Cerca per paraula clau i URL | ✅ Complet |
| Multimèdia | Reproductor integrat | ✅ Complet |
| Perfil | Editar perfil, bio, telèfon | ✅ Complet |
| Perfil | Avatar: foto pujada o icona | ✅ Complet |
| Perfil | Canvi de contrasenya | ✅ Complet |
| Perfil | Activar/desactivar 2FA | ✅ Complet |
| Backoffice | Panell superadmin (Twig) | ✅ Complet |
| Backoffice | Auditoria xats | ✅ Complet |
| Backoffice | Assistent IA SQL | ✅ Funcional |
| Testing | PHPUnit funcional i unitari | ✅ Representatiu |
| Testing | Vitest (components i composables) | ✅ Representatiu |
| Testing | Playwright E2E | ✅ Flux crític |
| CI/CD | GitHub Actions test + deploy | ✅ Complet |
| Documentació | MkDocs Material | ✅ Complet |
| Documentació | Swagger UI (NelmioApiDocBundle) | ✅ Complet |

---

## Funcionalitats parcials ⚠️

| Mòdul | Funcionalitat | Estat | Bloqueig |
|---|---|---|---|
| Testing | Tests de TasksTab (Vitest) | ⚠️ Cap test | Temps |
| Testing | Tests de ExpensesTab (Vitest) | ⚠️ Cap test | Temps |
| Testing | Tests de CalendarTab (Vitest) | ⚠️ Cap test | Temps |
| Testing | Tests de SettingsTab (Vitest) | ⚠️ Cap test | Temps |
| Testing | Tests d'editar/eliminar (PHPUnit) | ⚠️ Parcial | Temps |
| Testing | Test de flux password reset (PHPUnit) | ⚠️ Cap test | Temps |
| Auth | Verificació d'email al registre | ⚠️ No implementada | Decisió de disseny |
| Seguretat | Rate limiting al login | ⚠️ No implementat | Temps |
| Assistent IA | Respostes en llenguatge natural | ⚠️ Funcional però limitat | Quota API externa |

---

## Funcionalitats pendents (Backlog) 📋

### Prioritat Alta

| Funcionalitat | Justificació | Esforç estimat |
|---|---|---|
| Rate limiting als endpoints de login i reset | Seguretat: prevenció brute force | 1 dia |
| Tests Vitest per TasksTab i ExpensesTab | Cobertura de mòduls crítics | 2 dies |
| Límit d'intents 2FA per challengeId | Seguretat: prevenció brute force | 0.5 dies |

### Prioritat Mitjana

| Funcionalitat | Justificació | Esforç estimat |
|---|---|---|
| Verificació d'email al registre | Evitar comptes falsos | 1 dia |
| Blacklist JWT per revocació de tokens | Seguretat: logout real | 2 dies |
| Tests E2E de registre i perfil | Cobertura de fluxos d'entrada | 1 dia |
| Notificacions push (Web Push API) | Millora d'UX | 3 dies |
| Exportació de despeses a CSV | Utilitat financera | 1 dia |

### Prioritat Baixa

| Funcionalitat | Justificació | Esforç estimat |
|---|---|---|
| Reordenació de tasques (Drag & Drop) | Millora UX | 1 dia |
| Auditoria de canvis a entitats (Event Listener) | Compliance i debugging | 2 dies |
| Cerca i filtres avançats a tasques i despeses | Millora UX | 1 dia |
| OAuth2 (login amb Google) | Comoditat | 3 dies |
| App mòbil nativa (Ionic/Capacitor) | Expansió de plataformes | > 1 setmana |
| Xat en temps real (WebSocket) | Millora de latència | > 1 setmana |

---

## Riscos identificats

| Risc | Probabilitat | Impacte | Mitigació |
|---|---|---|---|
| Quota de YouTube API exhaurida | Mig | Alt | Afegir gestió d'errors 503 i missatge clar a l'usuari |
| Quota d'OpenRouter API exhaurida | Alt | Mig | L'assistent retorna un error clar; l'app no es veu afectada |
| Token JWT no revocable | Baix | Mig | TTL curt (1h); implementar blacklist si creix la base d'usuaris |
| Dependència de Chromium en CI | Baixa | Alt | `npx playwright install chromium` al workflow |
| Acumulació de fitxers d'avatar a `public/uploads` | Mig | Baix | `SafeImageStorage` elimina l'avatar antic en actualitzar |
