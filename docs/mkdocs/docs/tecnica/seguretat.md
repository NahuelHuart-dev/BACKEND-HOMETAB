# Seguretat i permisos

## Model de rols

El backend defineix una jerarquia de rols a `config/packages/security.yaml`:

```yaml
role_hierarchy:
    ROLE_ADMIN: ROLE_USER
    ROLE_SUPER_ADMIN: [ROLE_ADMIN, ROLE_ALLOWED_TO_SWITCH]
```

| Rol | Descripcio |
| --- | --- |
| `ROLE_USER` | Usuari autenticat de l'aplicacio. |
| `ROLE_ADMIN` | Rol administratiu intermedi heretat pel superadmin. |
| `ROLE_SUPER_ADMIN` | Acces complet al backend privat i a la documentacio. |

## Firewalls

| Firewall | Patró | Tipus | Funcio |
| --- | --- | --- | --- |
| `api` | `^/api` | Stateless JWT | Protegeix l'API consumida per Vue. |
| `main` | resta de rutes | Sessio web | Login Twig i backoffice. |
| `dev` | profiler/assets Symfony | sense seguretat | Eines de desenvolupament. |

## Control d'acces

Les regles principals son:

```yaml
- { path: ^/api/login_check, roles: PUBLIC_ACCESS }
- { path: ^/api/login/verify, roles: PUBLIC_ACCESS }
- { path: ^/api/register, roles: PUBLIC_ACCESS }
- { path: ^/api, roles: ROLE_USER }
- { path: ^/admin, roles: ROLE_SUPER_ADMIN }
```

Aixo implica:

- Login i registre API son publics.
- La resta de l'API exigeix JWT valid i `ROLE_USER`.
- Qualsevol ruta `/admin`, inclosa `/admin/documentacio`, exigeix `ROLE_SUPER_ADMIN`.

## JWT

El frontend envia el token a cada peticio:

```http
Authorization: Bearer <token>
```

Lexik JWT valida la signatura amb les claus configurades a:

```text
config/packages/lexik_jwt_authentication.yaml
```

Les claus privades no s'han de versionar.

## 2FA per correu

La verificacio en dos passos es opcional per usuari.

Flux:

1. L'usuari entra email i contrasenya.
2. Si `twoFactorEnabled=false`, rep token directament.
3. Si `twoFactorEnabled=true`, es genera un repte amb `challengeId`.
4. El codi s'envia per correu.
5. L'usuari verifica amb `POST /api/login/verify`.
6. Nomes llavors es genera el JWT.

El codi no es desa en clar. Es desa un hash a `TwoFactorCode`.

## Proteccio de dades per llar

La regla funcional es que un usuari nomes pot consultar dades de les llars on es membre. El servei `HouseholdAccessService` centralitza aquesta comprovacio per evitar duplicacio.

Metodes destacats:

- `getAccessibleHousehold()`: permet superadmin o membre.
- `getMemberHousehold()`: exigeix ser membre de la llar.
- `userBelongsToHousehold()`: comprova pertinenca.

## Chat administratiu auditable

El superadmin pot accedir a chats per motiu administratiu. Aquest acces queda registrat amb `ChatAccessLog`, que desa:

- admin
- llar
- motiu
- data d'acces

La finalitat es evitar un acces opac a converses privades.

## Documentacio protegida

La documentacio MkDocs no viu a `public/`. Es genera a:

```text
docs/mkdocs/site
```

I es serveix per:

```text
/admin/documentacio
```

El controlador valida:

- que l'usuari sigui `ROLE_SUPER_ADMIN`
- que el fitxer demanat existeixi
- que el path no surti del directori `site`

## Bones practiques aplicades

- Separacio entre API JWT i backoffice amb sessio.
- Rutes administratives sota `/admin`.
- Borrat logic en lloc de destruccio de registres.
- Validacio backend encara que la UI tambe limiti opcions.
- Evitar exposar documentacio tecnica directament a web publica.
- Variables sensibles en `.env.local` o equivalents no versionades.
