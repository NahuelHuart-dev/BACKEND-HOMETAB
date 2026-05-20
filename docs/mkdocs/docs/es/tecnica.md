# Documentación técnica

## Arquitectura

HomeTab se divide en tres partes principales:

| Componente | Tecnología | Responsabilidad |
| --- | --- | --- |
| Backend | Symfony 7.4, PHP 8.2, Doctrine ORM, Twig | API, persistencia, seguridad, backoffice y documentación protegida. |
| Frontend | Vue 3, Vite, PrimeVue, Axios, GSAP | Experiencia principal de usuario y consumo de API. |
| Landing | Vue/Vite | Sitio público de presentación. |

El backend es la fuente de verdad de datos y permisos. El frontend Vue consume la API JSON con JWT. El backend Twig queda reservado para operaciones de superadministración.

## Estructura backend

```text
src/
  Controller/Api/
  Controller/twig/
  Entity/
  Repository/
  Service/
  Security/
  Twig/
templates/
docs/mkdocs/
```

## Modelo de seguridad

- Rutas públicas de API: login, verificación 2FA y registro.
- Rutas API protegidas: el resto de `/api` exige `ROLE_USER`.
- Rutas privadas backend: `/admin` exige `ROLE_SUPER_ADMIN`.
- Ruta de documentación: `/admin/documentacio`, también protegida por `ROLE_SUPER_ADMIN`.

## Servicios principales

| Servicio | Responsabilidad |
| --- | --- |
| `HouseholdAccessService` | Centraliza comprobaciones de acceso a hogares. |
| `TwoFactorService` | Genera, guarda y verifica retos 2FA. |
| `TwoFactorEmailSender` | Envía códigos 2FA por email. |
| `ChatTypingService` | Gestiona el estado temporal de escritura en chats. |
| `SqlAIAssistant` | Genera SQL de lectura para soporte administrativo. |
| `AssistantService` | Responde preguntas funcionales sobre datos de HomeTab. |

## Modelo de datos

Entidades principales:

- `User`
- `Household`
- `HouseholdMember`
- `Task`
- `Event`
- `Expense`
- `ExpenseShare`
- `HouseholdMessage`
- `ChatAccessLog`
- `TwoFactorCode`

Los registros funcionales importantes usan borrado lógico mediante `isActive=false` en lugar de eliminación física.

## Integración protegida de MkDocs

La fuente MkDocs vive en:

```text
docs/mkdocs
```

El sitio generado es:

```text
docs/mkdocs/site
```

Symfony lo sirve mediante `AdminDocumentationController`, fuera de `public/`, para que el acceso no pueda saltarse los permisos del backend.

## Registros técnicos integrados

La sección en catalán incluye la guía completa de administración backend e IA y el worklog técnico completo:

- `Tecnica > Guia backend admin i IA`
- `Tecnica > Worklog tecnic complet`
