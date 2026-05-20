# Auditoria de seguretat

## Objectiu

Aquesta pàgina documenta els riscos de seguretat revisats durant el desenvolupament de HomeTab, les mesures preventives aplicades i els riscos residuals.

---

## Model de rols i jerarquia

HomeTab implementa tres nivells de privilegis:

| Rol | Accés |
|---|---|
| `ROLE_USER` | API pública autenticada. Veu i modifica dades de les seves llars. |
| `ROLE_ADMIN` | Heretat per `ROLE_SUPER_ADMIN`. Sense ús independent actual. |
| `ROLE_SUPER_ADMIN` | Accés complet al backoffice Twig i a la documentació. |

**Risc previngut**: no es pot accedir al backoffice amb un compte d'usuari normal, ni a la documentació interna.

---

## JWT: autenticació stateless

### Mesures aplicades

- Les claus JWT (RSA) no estan al repositori; es generen al servidor i es passen com a secrets al CI/CD.
- El TTL del token és configurable. Per defecte: **1 hora** (Lexik JWT).
- El frontend desa el token a `localStorage`. **No s'usa cookie** (no hi ha risc CSRF sobre l'API).
- El backend valida la signatura a cada petició via el firewall `api` (stateless).

### Riscos i mitigació

| Risc | Nivell | Mitigació |
|---|---|---|
| Token robat per XSS | Mig | L'app no injecta HTML extern sense sanititzar. Vue escapa per defecte. |
| Token no revocat | Baix-Mig | No hi ha revocació activa (acceptable per a TTL curt). Millorable amb blacklist. |
| Claus JWT exposades | Alt | Claus fora de `public/`, no versionades, passades via secrets CI. |

---

## Protecció contra IDOR (Insecure Direct Object Reference)

IDOR és quan un usuari pot accedir a dades d'un altre canviant un ID a la URL.

### Mesures aplicades

Tots els controladors de recursos de llar comproven explícitament la pertinença:

```php
// ApiTaskController, ApiExpenseController, ApiEventController...
private function getValidHousehold(int $homeId, User $user, EntityManagerInterface $em): ?Household
{
    $household = $em->getRepository(Household::class)->find($homeId);
    if (!$household) return null;

    if ($this->isGranted('ROLE_SUPER_ADMIN')) return $household;

    foreach ($user->getHouseholdMemberships() as $membership) {
        if ($membership->getHousehold()->getId() === $household->getId()) {
            return $household;
        }
    }
    return null; // Retorna null → el controlador respon 403
}
```

El servei `HouseholdAccessService` centralitza la mateixa lògica per als mòduls de multimèdia i xat:

```php
public function getMemberHousehold(int $householdId, User $user): ?Household
{
    $household = $this->em->getRepository(Household::class)->find($householdId);
    if (!$household) return null;

    if ($this->isGranted('ROLE_SUPER_ADMIN')) return $household;

    foreach ($user->getHouseholdMemberships() as $membership) {
        if ($membership->getHousehold()->getId() === $household->getId()) {
            return $household;
        }
    }
    return null;
}
```

### Tests que cobreixen IDOR

- `HouseholdChatApiTest::testForeignHouseholdAccessIsDenied()` → llar aliena retorna 403.
- `NotificationApiTest` → notificació d'un altre usuari retorna 404.
- `HouseholdResourceApiTest` → tasques, despeses i events de llar aliena retornen 403.

---

## CORS

La configuració CORS es gestiona via `NelmioCorsBundle`:

```dotenv
CORS_ALLOW_ORIGIN='^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$'
```

En producció, es reemplaça per la URL exacta del frontend:

```dotenv
CORS_ALLOW_ORIGIN='^https://hometab\.es$'
```

**Risc previngut**: cap origen extern no autoritzat pot fer peticions autenticades a l'API.

---

## Secrets i `.env`

### Regles aplicades

- `.env.local` i `.env.*.local` estan al `.gitignore` → mai versionats.
- Les claus JWT no versionades. A CI es passen com a secrets (`JWT_PRIVATE_KEY_B64`, `JWT_PUBLIC_KEY_B64`).
- `APP_SECRET` i les credencials de base de dades sempre en `.env.local` o secrets CI.
- Els correus de prova i usuaris del fitxer `test-users.md` no contenen credencials reals.

**Risc previngut**: cap secret real no és accessible al repositori públic.

---

## Rutes d'administració

### Mesures aplicades

- Totes les rutes `/admin/*` estan protegides per `ROLE_SUPER_ADMIN` a `access_control` de `security.yaml`.
- La documentació MkDocs no viu a `public/`; es serveix via `AdminDocumentationController` que:
  - Verifica `ROLE_SUPER_ADMIN`.
  - Valida que la ruta no surti del directori `site/` (path traversal prevention).
- El Symfony Profiler (`/_profiler`) és accessible **només en entorn `dev`** via el firewall `dev`.

```yaml
firewalls:
    dev:
        pattern: ^/(_(profiler|wdt)|css|images|js)/
        security: false
```

En producció (`APP_ENV=prod`), el profiler no s'activa.

---

## 2FA (Verificació en dos passos)

### Mesures aplicades

- El codi 2FA **no es desa en clar**: es desa un hash SHA-256 a l'entitat `TwoFactorCode`.
- Cada repte té `challengeId` únic i data d'expiració.
- El codi s'envia per correu electrònic via Symfony Mailer.
- El repte queda marcat com a `used = true` un cop verificat (no es pot reusar).

### Flux segur

```php
// TwoFactorService::startChallenge()
$plainCode = sprintf('%06d', random_int(0, 999999));
$challenge->setCodeHash(hash('sha256', $plainCode));
$challenge->setExpiresAt(new \DateTime('+10 minutes'));
```

**Risc previngut**: brute force del codi és difícil per l'expiració i el hash.

---

## Borrat lògic

Les tasques, despeses i esdeveniments **no s'eliminen físicament**. S'actualitza `isActive = false`.

Això protegeix:
- La integritat de l'historial (despeses i logs de xat es poden auditar).
- Contra errors d'eliminació accidental.

---

## Auditoria de xats

L'accés administratiu als xats privats queda registrat a `ChatAccessLog`:

```php
// AdminChatController
$log = new ChatAccessLog();
$log->setAdmin($admin);
$log->setHousehold($household);
$log->setReason($reason);
$log->setAccessedAt(new \DateTime());
$em->persist($log);
```

**Objectiu**: que cap superadmin pugui llegir xats privats de forma opaca. El registre és auditable.

---

## Pujada d'imatges

El servei `SafeImageStorage` processa imatges base64:

- Valida que el format sigui `image/jpeg`, `image/png` o `image/webp`.
- Valida la mida (màxim configurable).
- Desa a `public/uploads/{avatars|households}/`.
- Genera un nom de fitxer aleatori per evitar enumeració.

**Risc previngut**: no es poden pujar fitxers executables ni scripts disfressats d'imatge.

---

## Vulnerabilitats previngudes — resum

| Vulnerabilitat | Estat | Mètode |
|---|---|---|
| IDOR per ID de llar | ✅ Mitigat | Comprovació de pertinença a tots els controladors |
| JWT sense signatura | ✅ Mitigat | Lexik JWT amb RSA, claus fora del repositori |
| Accés al backoffice sense rol | ✅ Mitigat | `ROLE_SUPER_ADMIN` a `access_control` |
| CORS obert | ✅ Mitigat | `NelmioCorsBundle` amb regex per origen |
| Secrets versionats | ✅ Mitigat | `.gitignore` + CI secrets |
| Path traversal MkDocs | ✅ Mitigat | `AdminDocumentationController` valida ruta |
| XSS a la UI | ✅ Mitigat | Vue escapa contingut per defecte |
| Pujada d'arxius perillosos | ✅ Mitigat | `SafeImageStorage` valida MIME |
| 2FA en clar | ✅ Mitigat | Hash SHA-256 del codi |
| Profiler exposat en producció | ✅ Mitigat | Firewall `dev` no actiu en `APP_ENV=prod` |
| Registre d'accés admin als xats | ✅ Implementat | `ChatAccessLog` |

---

## Riscos residuals i recomanacions

| Risc | Prioritat | Recomanació |
|---|---|---|
| Token JWT no revocable | Mig | Implementar blacklist o reducir TTL en perfil de seguretat alt |
| Brute force de codis 2FA | Mig | Afegir límit d'intents per `challengeId` |
| Rate limiting a l'API | Mig | Implementar `symfony/rate-limiter` als endpoints de login i reset |
| Validació d'email al registre | Baix | Afegir confirmació d'email opcional |
| Auditoria de canvis a entitats | Baix | Implementar Doctrine Event Listeners per a log de canvis |
