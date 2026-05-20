# Worklog técnico backend

Este worklog resume los principales cambios implementados en el backend de HomeTab.

## Arquitectura tras los cambios

El backend se divide en:

- API JSON para el frontend Vue.
- Backend Twig para herramientas internas de administración.

La API usa JWT. El backend Twig queda reservado para `ROLE_SUPER_ADMIN`.

## 2FA por email

Se implementó autenticación en dos pasos opcional por email.

Flujo:

1. El usuario envía email y contraseña.
2. Si el 2FA está desactivado, la API devuelve JWT.
3. Si el 2FA está activado, la API devuelve `challengeId`.
4. Se envía un código por email.
5. `POST /api/login/verify` verifica el reto.
6. El JWT solo se emite tras una verificación correcta.

Archivos principales:

```text
src/Entity/TwoFactorCode.php
src/Service/TwoFactorService.php
src/Service/TwoFactorEmailSender.php
src/Controller/Api/AuthController.php
```

## Mailer

Symfony Mailer se configuró para envío local y SMTP real.

En desarrollo se puede usar Mailpit:

```text
SMTP: localhost:1025
UI:   http://localhost:8025
```

Las credenciales SMTP reales deben vivir fuera de Git.

## Restricción del login backend

El backend Twig está restringido a superadministradores. Los usuarios que no son superadmin vuelven al login y su sesión se invalida.

Archivos relevantes:

```text
config/packages/security.yaml
src/Security/LoginFormAuthenticator.php
templates/security/login.html.twig
```

## Panel de administración

El panel incluye:

- Dashboard de hogares.
- Grid de usuarios.
- Grid global de tareas.
- Grid global de gastos.
- Grid global de eventos.
- Auditoría de chat.
- IA SQL.
- Consola SQL de solo lectura.
- Documentación MkDocs protegida.

## Borrado lógico

Los registros funcionales importantes se desactivan en lugar de eliminarse físicamente:

```php
$entity->setIsActive(false);
```

Se aplica a tareas, gastos y eventos.

## Perfil y avatar

El editor de perfil soporta:

- selección de icono
- subida de fotografía
- recorte cuadrado en navegador
- validación backend de imagen
- eliminación de archivos de avatar anteriores

## Chat y auditoría

El chat de hogar incluye:

- listado de mensajes
- envío de mensajes
- indicadores de escritura
- edición de mensajes propios
- borrado lógico de mensajes propios
- auditoría administrativa de acceso al chat

## Documentación

MkDocs se integró en:

```text
docs/mkdocs
```

El sitio generado se sirve mediante:

```text
/admin/documentacio
```

El acceso está protegido por `ROLE_SUPER_ADMIN`.

## Comandos de validación

Comprobaciones recomendadas:

```bash
php -l src/Controller/twig/AdminDocumentationController.php
php bin/console lint:twig templates
php bin/console lint:yaml config
php bin/console doctrine:schema:validate
python -m mkdocs build -f docs/mkdocs/mkdocs.yml --strict
```
