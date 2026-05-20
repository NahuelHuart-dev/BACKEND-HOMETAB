<?php

namespace App\Controller\Api;

use App\Service\Assistant\AssistantService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controlador de API para el asistente virtual.
 * Proporciona un endpoint de chat con IA.
 */
#[Route('/api/assistant')]
class AssistantController extends AbstractController
{
    /**
     * Procesa un mensaje de chat para el asistente virtual.
     * 
     * @param Request $request Petición HTTP con el mensaje del usuario.
     * @param AssistantService $assistantService Servicio del asistente (IA).
     * @return JsonResponse Respuesta JSON con la contestación del asistente.
     */
    #[Route('/chat', name: 'api_assistant_chat', methods: ['POST'])]
    public function chat(
        Request $request,
        AssistantService $assistantService
    ): JsonResponse {

        $user = $this->getUser();

        if (!$user) {
            return $this->json([
                'error' => 'Usuari no autenticat'
            ], 401);
        }

        $data = json_decode($request->getContent(), true);

        $message = trim($data['message'] ?? '');

        if ($message === '') {
            return $this->json([
                'error' => 'Missatge buit'
            ], 422);
        }

        $response = $assistantService->ask($user, $message);

        return $this->json($response);
    }
}
