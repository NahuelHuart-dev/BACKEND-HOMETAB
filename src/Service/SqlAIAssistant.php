<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class SqlAIAssistant
{
    private const OPENROUTER_API_URL = 'https://openrouter.ai/api/v1/chat/completions';

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $openRouterApiKey,
        private string $openRouterModelsCsv = ''
    ) {
    }

    /** Genera una consulta SQL SELECT a partir de una pregunta en lenguaje natural. */
    public function generateSqlQuery(string $userPrompt): string
    {
        $apiKey = $this->normalizeApiKey($this->openRouterApiKey);
        if ($apiKey === '') {
            return '-- Configura OPENROUTER_API_KEY en .env.local (Symfony inyecta la variable en el servicio).';
        }

        $models = array_values(array_filter(array_map('trim', explode(',', $this->openRouterModelsCsv))));
        if ([] === $models) {
            $models = [
                'meta-llama/llama-3.3-70b-instruct',
                'meta-llama/llama-3.3-70b-instruct:free',
            ];
        }

        $schemaContext = $this->getDatabaseSchemaContext();

        $systemPrompt = "Eres un experto en bases de datos SQL y Doctrine. "
            . "Tu tarea es devolver ÚNICAMENTE una consulta SQL válida que responda a la solicitud del usuario, "
            . "basándote en el siguiente esquema de base de datos MySQL:\n\n"
            . $schemaContext . "\n\n"
            . "REGLAS IMPORTANTES:\n"
            . "1. NO incluyas explicaciones, solo el código SQL dentro de un bloque ```sql ... ```.\n"
            . "2. Solo puedes hacer consultas SELECT. Nunca INSERT, UPDATE, DELETE o DROP.\n"
            . "3. Usa alias claros en las tablas.\n";

        $lastError = '';
        foreach ($models as $model) {
            try {
                $response = $this->httpClient->request('POST', self::OPENROUTER_API_URL, [
                    'headers' => [
                        'Authorization' => 'Bearer '.$apiKey,
                        'Content-Type' => 'application/json',
                        'HTTP-Referer' => 'http://localhost',
                        'X-Title' => 'HomeTab',
                    ],
                    'json' => [
                        'model' => $model,
                        'messages' => [
                            ['role' => 'system', 'content' => $systemPrompt],
                            ['role' => 'user', 'content' => $userPrompt],
                        ],
                        'temperature' => 0.1,
                    ],
                ]);

                $statusCode = $response->getStatusCode();
                if ($statusCode === 401) {
                    $body = $response->getContent(false);

                    return "-- OpenRouter ha rechazado la API key (401 Unauthorized).\n"
                        . "-- Revisa OPENROUTER_API_KEY en .env.local: debe ser solo la clave sk-or-v1-..., sin 'Bearer ', sin comillas pegadas y sin espacios.\n"
                        . "-- Respuesta OpenRouter: " . $this->shortenErrorBody($body);
                }

                $data = $response->toArray();
                if (!isset($data['choices'][0]['message']['content'])) {
                    $lastError = "Respuesta inválida para el modelo {$model}.";

                    continue;
                }

                $content = $data['choices'][0]['message']['content'];
                preg_match('/```sql\s+(.*?)\s+```/is', $content, $matches);
                if (!empty($matches[1])) {
                    return $matches[1];
                }

                return $content;
            } catch (\Exception $e) {
                $lastError = $e->getMessage();

                continue;
            }
        }

        return "-- Error al conectar con la IA: {$lastError}\n-- Si recibes 429, revisa cuota/modelo en OPENROUTER_MODELS (.env.local).";
    }

    /** Limpia la API key para aceptar valores con comillas o Bearer accidental. */
    private function normalizeApiKey(string $apiKey): string
    {
        $apiKey = trim($apiKey);
        $apiKey = trim($apiKey, "\"' \t\n\r\0\x0B");

        if (str_starts_with(strtolower($apiKey), 'bearer ')) {
            $apiKey = trim(substr($apiKey, 7));
        }

        return trim($apiKey, "\"' \t\n\r\0\x0B");
    }

    /** Recorta respuestas de error largas para mostrarlas de forma legible. */
    private function shortenErrorBody(string $body): string
    {
        $body = trim(preg_replace('/\s+/', ' ', $body) ?? '');

        if ($body === '') {
            return 'sin cuerpo de respuesta.';
        }

        return mb_substr($body, 0, 240);
    }

    /** Resume el esquema de base de datos que se pasa al modelo de IA. */
    private function getDatabaseSchemaContext(): string
    {
        return "
        Table user: id (INT), email (VARCHAR), roles (JSON), password (VARCHAR), first_name (VARCHAR), last_name (VARCHAR), phone_number (VARCHAR), is_active (TINYINT).
        Table home_members (HouseholdMember): id (INT), user_id (INT), household_id (INT), role (VARCHAR), joined_at (DATETIME), sort_order (INT).
        Table household: id (INT), name (VARCHAR), invite_code (VARCHAR).
        Table task: id (INT), household_id (INT), assigned_to_id (INT), title (VARCHAR), description (TEXT), is_completed (TINYINT), created_at (DATETIME), due_date (DATETIME).
        Table expense: id (INT), paid_by_id (INT), household_id (INT), title (VARCHAR), description (VARCHAR), amount (NUMERIC 10,2), category (VARCHAR), is_paid (TINYINT), notes (TEXT), due_date (DATETIME), periodicity (VARCHAR).
        Table expense_share: id (INT), expense_id (INT), user_id (INT), amount_owed (NUMERIC 10,2), is_paid (TINYINT), paid_at (DATETIME).
        Table event: id (INT), household_id (INT), created_by_id (INT), title (VARCHAR), description (TEXT), start_date (DATETIME), end_date (DATETIME), is_all_day (TINYINT).
        Table event_user: event_id (INT), user_id (INT) (participants ManyToMany).
        Table household_message: id (INT), household_id (INT), sender_id (INT), content (LONGTEXT), created_at (DATETIME), edited_at (DATETIME), deleted_at (DATETIME), is_active (TINYINT), image_path (VARCHAR).
        Table chat_access_log: id (INT), admin_id (INT), household_id (INT), reason (LONGTEXT), accessed_at (DATETIME), ip_address (VARCHAR).
        Table password_reset_token: id (INT), user_id (INT), token_hash (VARCHAR), expires_at (DATETIME), used_at (DATETIME), created_at (DATETIME).
        Table notification: id (INT), user_id (INT), household_id (INT), notification_key (VARCHAR), type (VARCHAR), priority (VARCHAR), title (VARCHAR), message (LONGTEXT), target_at (DATETIME), route (VARCHAR), target_type (VARCHAR), target_id (INT), is_read (TINYINT), read_at (DATETIME), is_active (TINYINT), created_at (DATETIME), updated_at (DATETIME).
        Table multimedia_playlist: id (INT), household_id (INT), created_by_id (INT), name (VARCHAR), created_at (DATETIME).
        Table multimedia_video: id (INT), playlist_id (INT), added_by_id (INT), youtube_id (VARCHAR), title (VARCHAR), thumbnail_url (VARCHAR), channel_title (VARCHAR), position (INT), created_at (DATETIME).
        ";
    }
}
