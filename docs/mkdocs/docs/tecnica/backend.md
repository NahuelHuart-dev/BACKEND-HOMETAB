# Backend Symfony

El backend de HomeTab combina una API REST stateless para Vue con un backoffice Twig stateful para superadministracion. El codigo vive en `Backend/backend-grup-6-gensync`.

## Estructura

```text
src/
├── Controller/
│   ├── Api/        # API JSON para frontend y servicios internos
│   └── twig/       # Backoffice Twig, login web y documentacion protegida
├── Entity/         # Entidades Doctrine
├── Repository/     # Consultas por entidad
├── Service/        # Logica transversal
├── Security/       # LoginFormAuthenticator
├── Twig/           # Extensiones Twig
└── EventSubscriber/
```

## API REST

Los controladores `src/Controller/Api` devuelven JSON. El firewall `api` es stateless y valida JWT para `/api/*`, con excepciones publicas para login, registro, feedback, password reset y Swagger.

Ejemplo real de login en `AuthController`:

```php
$data = json_decode($request->getContent(), true) ?: [];
$email = trim((string) ($data['username'] ?? $data['email'] ?? ''));
$password = (string) ($data['password'] ?? '');

$user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
if (!$user || !$passwordHasher->isPasswordValid($user, $password) || !$user->isActive()) {
    return $this->json(['error' => 'Credenciales invalidas.'], 401);
}

if ($user->isTwoFactorEnabled()) {
    $challenge = $twoFactorService->startChallenge($user, TwoFactorCode::PURPOSE_LOGIN);
    return $this->json([
        'requiresTwoFactor' => true,
        'challengeId' => $challenge->getChallengeId(),
        'message' => 'Te hemos enviado un codigo de verificacion por correo.',
    ]);
}
```

## Servicios Criticos

### `HouseholdAccessService`

Centraliza comprobaciones de pertenencia a hogar. Se usa especialmente en modulos como chat y multimedia.

```php
public function getAccessibleHousehold(int $homeId, User $user): ?Household
{
    $household = $this->householdRepository->find($homeId);
    if (!$household) {
        return null;
    }

    if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
        return $household;
    }

    return $this->userBelongsToHousehold($user, $household) ? $household : null;
}
```

La variante `getMemberHousehold()` no concede acceso global a superadmin; se usa cuando la funcionalidad debe limitarse a miembros reales.

### `TwoFactorService`

Genera challenges, invalida retos anteriores, hashea codigos y limita intentos.

```php
private const CODE_TTL_MINUTES = 10;
private const MAX_ATTEMPTS = 5;

public function startChallenge(User $user, string $purpose): TwoFactorCode
{
    $this->codeRepository->markPreviousUnusedAsUsed($user, $purpose);
    $plainCode = (string) random_int(100000, 999999);

    $challenge = (new TwoFactorCode())
        ->setUser($user)
        ->setPurpose($purpose)
        ->setChallengeId(bin2hex(random_bytes(32)))
        ->setCodeHash($this->hashCode($plainCode))
        ->setExpiresAt(new \DateTime('+'.self::CODE_TTL_MINUTES.' minutes'));
}
```

El codigo nunca se guarda en claro. Se almacena el hash SHA-256 y se marca `usedAt` cuando caduca, falla por limite de intentos o se verifica correctamente.

### `SafeImageStorage`

Valida imagenes base64 antes de escribirlas en `public/uploads/{folder}`.

```php
private const MAX_BYTES = 2 * 1024 * 1024;
private const ALLOWED_EXTENSIONS = ['png', 'jpg', 'webp'];

public function storeDataUrl(string $dataUrl, string $folder): ?string
{
    if (!preg_match('/^data:image\/(png|jpeg|jpg|webp);base64,([A-Za-z0-9+\/=]+)$/', $dataUrl, $matches)) {
        return null;
    }

    $extension = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
    $bytes = base64_decode($matches[2], true);

    return $this->storeBytes($bytes, $extension, $folder);
}
```

La validacion real comprueba extension permitida, tamano maximo y que `getimagesizefromstring()` reconozca una imagen valida.

## Backoffice Twig

Los controladores `src/Controller/twig` usan sesion PHP y el firewall `main`. Las rutas `/admin/*` requieren `ROLE_SUPER_ADMIN`. Desde ahi se gestionan usuarios, hogares, tareas, gastos, eventos, chats, consola SQL y documentacion.

`AdminDocumentationController` sirve MkDocs de forma protegida desde `docs/mkdocs/site`:

```php
$siteDir = $projectDir.'/docs/mkdocs/site';
$realSiteDir = realpath($siteDir);
$targetPath = $siteDir.'/'.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $normalizedPath);
$realTargetPath = realpath($targetPath);

$insideSite = $realTargetPath === $realSiteDir
    || str_starts_with($realTargetPath, $realSiteDir.DIRECTORY_SEPARATOR);

if (!$insideSite) {
    throw new NotFoundHttpException('Ruta de documentacio no valida.');
}
```

Esto evita path traversal y mantiene MkDocs fuera de `public/`.

## Entidades

Las entidades principales son:

| Entidad | Responsabilidad |
| --- | --- |
| `User` | Usuario, roles, avatar, perfil, 2FA. |
| `Household` | Hogar, codigo de invitacion y avatar. |
| `HouseholdMember` | Relacion usuario-hogar con rol y orden. |
| `Task` | Tareas asignables dentro de una casa. |
| `Expense` y `ExpenseShare` | Gastos y cuotas por participante. |
| `Event` | Calendario y participantes. |
| `HouseholdMessage` | Chat de una casa. |
| `Notification` | Notificaciones agrupadas por hogar. |
| `MultimediaPlaylist` y `MultimediaVideo` | Playlists y videos YouTube. |
| `TwoFactorCode` y `PasswordResetToken` | Seguridad temporal de 2FA y reset. |

## Swagger

`nelmio/api-doc-bundle` expone:

```text
GET /api/doc
GET /api/doc.json
```

Swagger se genera desde las rutas Symfony reales. MkDocs mantiene los ejemplos humanos detallados y la explicacion de flujos.

## Tests Backend

La suite se divide en:

| Suite | Comando | Cobertura |
| --- | --- | --- |
| Unit | `composer test:unit` | `HouseholdAccessService`. |
| Functional | `composer test:functional` | Login, 2FA, hogares, recursos, perfil, chat, notificaciones y multimedia. |
| Completa | `composer test` | Todas las suites. |
