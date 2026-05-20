<?php

namespace App\Controller\Api;

use App\Service\SqlAIAssistant;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controlador de API para las funcionalidades de chat (Asistente SQL).
 * Proporciona endpoints para interactuar con la consola SQL y el asistente de inteligencia artificial.
 * Rutas HTML + JSON del asistente SQL en el mismo controlador para no duplicar lógica.
 */
class ChatApiController extends AbstractController
{
    /**
     * Devuelve la vista de prueba del chat.
     * 
     * @return Response Respuesta HTTP con la vista renderizada.
     */
    #[Route('/test/chat', name: 'app_test_chat')]
    public function testChatView(): Response
    {
        return $this->render('admin/chat.html.twig');
    }

    /**
     * Procesa una consulta de prueba del chat mediante la API.
     * 
     * @param Request $request Petición HTTP con los datos del prompt.
     * @param SqlAIAssistant $aiAssistant Servicio del asistente SQL.
     * @return Response Respuesta JSON con el resultado de la consulta generada.
     */
    #[Route('/test/api/chat', name: 'api_test_chat', methods: ['POST'])]
    public function testChatApi(Request $request, SqlAIAssistant $aiAssistant): Response
    {
        return $this->chatJsonResponse($request, $aiAssistant);
    }

    /**
     * Devuelve la vista de administración del chat.
     * 
     * @return Response Respuesta HTTP con la vista renderizada.
     */
    #[Route('/admin/chat', name: 'app_admin_chat')]
    #[IsGranted('ROLE_ADMIN')]
    public function adminChatView(): Response
    {
        return $this->render('admin/chat.html.twig');
    }

    /**
     * Procesa una consulta de administración del chat mediante la API.
     * 
     * @param Request $request Petición HTTP con el prompt.
     * @param SqlAIAssistant $aiAssistant Servicio del asistente SQL.
     * @return Response Respuesta JSON con la consulta SQL generada.
     */
    #[Route('/admin/api/chat', name: 'api_admin_chat', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function adminChatApi(Request $request, SqlAIAssistant $aiAssistant): Response
    {
        return $this->chatJsonResponse($request, $aiAssistant);
    }

    /**
     * Consola SQL de administración.
     * Permite ejecutar sentencias SELECT, SHOW, DESCRIBE y EXPLAIN directamente sobre la base de datos.
     * 
     * @param Request $request Petición HTTP con la consulta SQL.
     * @param Connection $connection Conexión DBAL de Doctrine.
     * @return Response Respuesta HTTP con la vista y resultados o errores.
     */
    #[Route('/admin/sql', name: 'app_admin_sql', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function sqlConsole(Request $request, Connection $connection): Response
    {
        $sql = trim((string) $request->request->get('sql', 'SELECT id, email, roles, is_active FROM user ORDER BY id DESC LIMIT 20'));
        $result = null;
        $error = null;
        $durationMs = null;

        if ($request->isMethod('POST')) {
            if (!$this->isReadOnlySql($sql)) {
                $error = 'Por seguridad, esta consola solo permite SELECT, SHOW, DESCRIBE y EXPLAIN.';
            } else {
                $startedAt = microtime(true);
                try {
                    $rows = $connection->fetchAllAssociative($sql);
                    $durationMs = round((microtime(true) - $startedAt) * 1000, 2);
                    $columns = [];
                    foreach ($rows as $row) {
                        $columns = array_values(array_unique(array_merge($columns, array_keys($row))));
                    }
                    $result = [
                        'columns' => $columns,
                        'rows' => $rows,
                        'count' => count($rows),
                    ];
                } catch (\Throwable $exception) {
                    $error = $exception->getMessage();
                    $durationMs = round((microtime(true) - $startedAt) * 1000, 2);
                }
            }
        }

        return $this->render('admin/sql.html.twig', [
            'sql' => $sql,
            'result' => $result,
            'error' => $error,
            'durationMs' => $durationMs,
        ]);
    }

    private function chatJsonResponse(Request $request, SqlAIAssistant $aiAssistant): Response
    {
        $data = json_decode($request->getContent(), true);
        $prompt = $data['prompt'] ?? '';

        if (empty($prompt)) {
            return $this->json(['sql' => '-- Por favor, escribe una pregunta.']);
        }

        return $this->json(['sql' => $aiAssistant->generateSqlQuery($prompt)]);
    }

    private function isReadOnlySql(string $sql): bool
    {
        $normalized = trim(preg_replace('/\s+/', ' ', $sql) ?? '');
        if ($normalized === '' || str_contains($normalized, ';')) {
            return false;
        }

        return (bool) preg_match('/^(SELECT|SHOW|DESCRIBE|DESC|EXPLAIN)\b/i', $normalized);
    }
}
