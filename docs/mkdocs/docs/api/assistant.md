# Assistent IA

Endpoint del modul de digitalitzacio:

```http
POST /api/assistant/chat
```

Requereix JWT.

## Cos

```json
{
  "message": "Quines tasques tinc pendents?"
}
```

## Resposta

```json
{
  "answer": "Tens 2 tasques pendents: ...",
  "sources": ["tasks"]
}
```

## Errors

| Codi | Cas |
| --- | --- |
| `401` | Usuari no autenticat. |
| `422` | Missatge buit. |

## Preguntes suportades

| Tema | Exemple |
| --- | --- |
| Tasques | "Quines tasques tinc pendents?" |
| Esdeveniments | "Quins son els propers esdeveniments?" |
| Pagaments | "Quins pagaments tinc pendents?" |
| Balances | "Quin saldo tinc a la llar?" |
| Resum | "Fes-me un resum general." |

## Notes d'implementacio

L'assistent actual fa deteccio d'intencio per paraules clau i consulta dades internes de Doctrine a partir de l'usuari autenticat. No escriu dades ni executa accions destructives.
