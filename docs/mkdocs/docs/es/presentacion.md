# Documentación de presentación final

## Identidad del producto

**HomeTab** es una herramienta digital para gestionar hogares compartidos. Resuelve problemas habituales de convivencia: tareas poco claras, gastos dispersos, recordatorios perdidos, poca visibilidad del calendario común y comunicación fragmentada.

## Solución propuesta

HomeTab ofrece:

- Creación de hogares y códigos de invitación.
- Gestión de miembros.
- Tareas asignables.
- Eventos de calendario compartido.
- Gastos compartidos e individuales.
- Cálculo de balances y compensaciones.
- Chat de hogar.
- Perfil de usuario con avatar o fotografía.
- Asistente IA para consultar datos del hogar en lenguaje natural.
- Backoffice privado para superadministradores.

## Qué se ha desarrollado

El proyecto incluye:

| Área | Descripción |
| --- | --- |
| Frontend Vue | Experiencia principal de usuario: landing, login, registro, selector de hogares y pestañas funcionales. |
| Backend Symfony | API JSON, autenticación, persistencia, panel de administración, herramientas internas y documentación protegida. |
| Landing page | Material público complementario para la presentación final. |

## Módulo Digitalización

El módulo de digitalización es el asistente IA de HomeTab, expuesto mediante:

```text
POST /api/assistant/chat
```

Permite que el usuario autenticado haga preguntas en lenguaje natural sobre sus datos:

- Tareas pendientes.
- Próximos eventos.
- Pagos compartidos pendientes.
- Balances.
- Resumen general del hogar.

La implementación actual es de solo lectura. No modifica datos y solo trabaja con hogares a los que pertenece el usuario.
