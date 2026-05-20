# Usuaris de prova

Aquests usuaris es creen des de:

```text
src/DataFixtures/AppFixtures.php
```

| Rol global | Nom | Email | Contrasenya | Llars |
| --- | --- | --- | --- | --- |
| `ROLE_SUPER_ADMIN` | Admin Suprem | `hometab.admin@gmail.com` | `HomeTab2026!Test` | Casa Home |
| `ROLE_USER` | Naomi Octubre | `naoctubre@gmail.com` | `HomeTab2026!Test` | Casa Home, Casa a Puigcerdà |
| `ROLE_USER` | Jamon Tester | `jamon141006@gmail.com` | `HomeTab2026!Test` | Casa Home, Pis Compartit a Girona |
| `ROLE_USER` | Anna López | `anna.demo@hometab.local` | `HomeTab2026!Test` | Casa Home, Club de Lectura |

Notes:

- El 2FA es crea desactivat per a tots els usuaris (`twoFactorEnabled = false`) per poder provar l'avís d'activació al login.
- `hometab.admin@gmail.com` també és el compte configurat com a remitent SMTP a l'entorn local.
- No s'han d'utilitzar aquestes credencials en producció.
