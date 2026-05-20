# Modul Digitalitzacio i IA

## Objectiu del modul

El modul de digitalitzacio incorpora un assistent IA dins de HomeTab per facilitar l'acces a la informacio de la llar. L'usuari pot preguntar en llenguatge natural i obtenir una resposta sintetitzada sobre les seves dades.

Aquest modul forma part del projecte desenvolupat i s'ha d'explicar explicitament a la presentacio final.

## Endpoint

```text
POST /api/assistant/chat
```

Requereix autenticacio JWT.

## Exemple de peticio

```json
{
  "message": "Quins pagaments tinc pendents?"
}
```

## Exemple de resposta

```json
{
  "answer": "Tens 2 pagaments pendents, per un total de 35.00 EUR...",
  "sources": ["payments", "expenses"]
}
```

## Capacitats actuals

L'assistent pot respondre sobre:

- Tasques pendents.
- Esdeveniments propers.
- Pagaments compartits pendents.
- Balances de la llar.
- Resum general.

## Implementacio

Fitxers principals:

```text
src/Controller/Api/AssistantController.php
src/Service/Assistant/AssistantService.php
src/Service/Assistant/AssistantContextBuilder.php
Assistent_IA.md
```

El controlador valida:

- que hi hagi usuari autenticat
- que el missatge no sigui buit
- que la resposta provingui del servei

El servei:

1. Normalitza el text de l'usuari.
2. Detecta la intencio segons paraules clau.
3. Consulta les dades de les llars de l'usuari.
4. Construeix una resposta textual amb fonts.

## Intencions detectades

| Intencio | Paraules clau representatives | Resposta |
| --- | --- | --- |
| `tasks` | tasca, tarea, pendent, pendiente, todo | Tasques pendents assignades. |
| `events` | esdeveniment, evento, calendari, proximo | Esdeveniments futurs. |
| `payments` | pagament, pago, despesa, gasto, deuda | Quotes pendents de pagar. |
| `balances` | balance, saldo, reparto, liquidacion | Saldo positiu o negatiu per llar. |
| `summary` | resum, resumen, estat, general | Resum agregat de llar. |

## Limitacions de seguretat

La primera versio es deliberadament de consulta:

- No crea, edita ni elimina dades.
- No respon sobre dades d'altres usuaris fora de les llars de l'usuari.
- No inventa informacio si no hi ha dades suficients.
- Si la pregunta no es de HomeTab, ho indica.

## Diferencia amb la IA SQL del backoffice

HomeTab inclou dues funcionalitats relacionades amb IA:

| Funcionalitat | Usuari | Finalitat |
| --- | --- | --- |
| Assistent HomeTab | Usuari autenticat | Consultar dades funcionals de la llar. |
| IA SQL | Admin/superadmin | Generar consultes SQL de lectura per suport intern. |

L'assistent HomeTab forma part del modul de digitalitzacio orientat a l'usuari. La IA SQL es una eina administrativa.

## Millores futures

- Afegir suport multilingue complet en catala/castella.
- Integrar un model LLM extern amb guardrails.
- Afegir cites mes detallades per cada resposta.
- Permetre accions confirmades, com marcar una tasca feta, en una fase posterior.
- Crear historials de conversa per usuari.
