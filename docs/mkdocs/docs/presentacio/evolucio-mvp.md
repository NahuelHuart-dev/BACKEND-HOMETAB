# Evolució MVP → Producte Final

## Context del projecte ABP

HomeTab és el producte final del projecte ABP (Aprenentatge Basat en Projectes) del Grup 6 de GenSync. Aquesta pàgina documenta l'evolució des del MVP inicial fins al producte lliurat.

---

## MVP inicial

El MVP de HomeTab oferia una funcionalitat bàsica de convivència:

| Funcionalitat | Estat MVP |
|---|---|
| Registre i login bàsic | ✅ Implementat |
| Una sola llar per usuari | ✅ Implementat |
| Llista de tasques simple | ✅ Implementat |
| Despeses bàsiques (sense shares) | ✅ Implementat |
| Events de calendari | ✅ Implementat |
| Panell Twig de superadmin | ✅ Implementat |

**Limitacions del MVP**:
- Un usuari podia pertànyer a **una sola llar**.
- Les despeses no tenien desglossat de quotes per participant (`ExpenseShare`).
- No hi havia verificació en dos passos (2FA).
- No hi havia xat de llar.
- No hi havia notificacions.
- No hi havia multimèdia.
- El frontend era un prototip funcional sense disseny final.
- No hi havia tests automatitzats.

---

## Evolució per àrees

### Autenticació i seguretat

| Canvi | Motiu |
|---|---|
| 2FA per correu electrònic | Petició de seguretat del projecte ABP. Millora la robustesa del login. |
| Recuperació de contrasenya en 3 passos | Experiència d'usuari completa sense intervenció manual. |
| Separació firewall API / backoffice | API stateless (JWT) vs backoffice amb sessió PHP. Patró estàndard. |
| `ChatAccessLog` per a auditoria | Transparència en l'accés administratiu als xats privats. |

### Gestió de llars

| Canvi | Motiu |
|---|---|
| Múltiples llars per usuari | Cas d'ús real: molts estudiants viuen en més d'un pis. |
| Roles: `owner`, `admin`, `member` | Control granular de qui pot gestionar la llar. |
| Codi d'invitació de 6 caràcters | Facilita unir-se sense compartir emails. |
| Reordenació de llars | Millora d'usabilitat basada en feedback dels proves. |
| Avatar per a la llar | Identitat visual de cada llar. |

### Despeses

| Canvi | Motiu |
|---|---|
| `ExpenseShare` per participant | Permet saber exactament qui deu quant. MVP no ho tenia. |
| Balance per membre | Vista ràpida del saldo de cada membre. |
| Liquidacions automàtiques | Algorisme greedy per calcular els pagaments mínims necessaris. |
| Tipus de pagament: individual vs compartit | Casos reals: la llum la paga un sol membre, el menjar es reparteix. |
| Periodicitat i recurrència | Despeses fixes mensuals (lloguer, internet, etc.). |

### Tasques

| Canvi | Motiu |
|---|---|
| Prioritat (Alta/Mitja/Baixa) | Feedback d'usuaris: calen urgències visibles. |
| Periodicitat | Tasques recurrents (netejar, treure les escombraries). |
| Categoria | Organització per tipus (neteja, cuina, compres). |
| Assignació a qualsevol membre | Distribució de tasques equitativa. |

### Xat

| Canvi | Motiu |
|---|---|
| Xat de llar en temps real (polling) | Comunicació interna sense sortir de l'app. |
| Widget slide-up en mòbil | Disseny mòbil natiu, no un popup incòmode. |
| Editar i eliminar missatges propis | Necessitat bàsica d'UX. |
| Typing indicator | Experiència de xat natural. |

### Notificacions

| Canvi | Motiu |
|---|---|
| Sistema de notificacions per llar | Centralitza alertes de tasques vencudes, despeses pendents, events. |
| Agrupació per llar | Visualització clara amb múltiples llars. |
| Polling automàtic | Actualització sense recàrrega de pàgina. |

### Multimèdia

| Canvi | Motiu |
|---|---|
| Playlists de YouTube per llar | Funcionalitat social diferenciadora. |
| Cerca integrada via YouTube API | No cal sortir de l'app per trobar vídeos. |
| Reproductor integrat | Visualització directa sense redirecció. |

### Frontend i disseny

| Canvi | Motiu |
|---|---|
| Sistema de disseny complet (CSS variables, dark mode) | Coherència visual i suport de temes. |
| Mobile-first amb layout adaptatiu | La majoria d'usuaris usen mòbil. |
| GSAP per a animacions | Millora de la sensació de qualitat del producte. |
| Composables reutilitzables | Reducció de duplicació de codi i millor mantenibilitat. |
| i18n (català, castellà, anglès) | Requeriment del projecte ABP. |

### Testing

| Canvi | Motiu |
|---|---|
| PHPUnit (unitari + funcional) | Detectar regressions automaticament. |
| Vitest + Vue Test Utils | Tests de components i composables Vue sense navegador. |
| Playwright E2E | Validació de fluxos crítics amb navegador real. |
| Base de dades de test separada | Tests sense afectar dades de desenvolupament. |

### Infraestructura i CI/CD

| Canvi | Motiu |
|---|---|
| GitHub Actions: test + deploy | Automatització i porta de qualitat. |
| Deploy DEV via rsync (EC2) | Entorn de proves sempre actualitzat. |
| Deploy PROD via Docker + AWS ECS | Escalabilitat i aïllament de producció. |
| MkDocs integrat i protegit | Documentació viva i accessible des del backoffice. |

---

## Feedback rebut i decisions preses

| Feedback | Decisió |
|---|---|
| "El MVP no distingeix qui ha pagat quant" | Implementar `ExpenseShare` amb quota individual |
| "Necessitem poder tenir diverses cases" | Migrar de relació 1:1 a N:N via `HouseholdMember` |
| "El xat s'obre sobre tot el contingut" | Redissenyar com a slide-up en mòbil i panel lateral en desktop |
| "Les tasques no es poden ordenar per urgència" | Afegir camp `priority` a `Task` |
| "No hi ha forma de saber si hi ha notificacions noves" | Implementar polling i badge numèric |

---

## Funcionalitats descartades

| Funcionalitat | Motiu del descart |
|---|---|
| Notificacions push natives | Requereix service worker i certificat VAPID. Complexitat excessiva pel calendari. |
| Chat en temps real (WebSocket) | Polling és suficient per al cas d'ús. WebSocket incrementaria la complexitat del servidor. |
| App mòbil nativa (Ionic/Capacitor) | Explorat però fora de l'abast del projecte ABP. |
| Autenticació per OAuth (Google, GitHub) | Temps insuficient. El sistema de 2FA cobreix el requeriment de seguretat. |
| Exportació a PDF de despeses | Funcionalitat futura interessant però no prioritari. |

---

## Estat final del producte

| Mòdul | Estat | Qualitat |
|---|---|---|
| Autenticació JWT + 2FA + Reset | ✅ Complet | Alta |
| Llars, membres i invitacions | ✅ Complet | Alta |
| Tasques amb prioritat i periodicitat | ✅ Complet | Alta |
| Despeses amb shares i liquidació | ✅ Complet | Alta |
| Calendari d'events | ✅ Complet | Alta |
| Xat de llar | ✅ Complet | Alta |
| Notificacions | ✅ Complet | Alta |
| Multimèdia / Playlists YouTube | ✅ Complet | Mitja-Alta |
| Perfil d'usuari i avatar | ✅ Complet | Alta |
| Assistent IA (SQL + preguntes) | ✅ Funcional | Mitja |
| Backoffice superadmin | ✅ Complet | Alta |
| Testing backend (PHPUnit) | ✅ Representatiu | Mitja |
| Testing frontend (Vitest) | ✅ Representatiu | Mitja |
| Tests E2E (Playwright) | ✅ Flux crític | Mitja |
| CI/CD GitHub Actions | ✅ Complet | Alta |
| MkDocs documentació | ✅ Complet | Alta |
| README professional | ✅ Creat | Alta |
