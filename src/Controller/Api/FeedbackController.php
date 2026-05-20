<?php

namespace App\Controller\Api;

use App\Service\FeedbackEmailSender;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 *
 * Controlador de API para procesar el feedback de los usuarios.
 * Recibe, valida y envía comentarios usando un servicio de correo, controlando además el límite de envíos por IP.
 */
class FeedbackController extends AbstractController
{
    /**
     * Envía feedback de un usuario de forma asíncrona mediante correo.
     * 
     * @param Request $request Petición HTTP con los datos del feedback.
     * @param FeedbackEmailSender $feedbackEmailSender Servicio para el envío de correos de feedback.
     * @param CacheItemPoolInterface $cache Sistema de caché para el rate limiting.
     * @return JsonResponse Respuesta JSON indicando éxito o errores de validación.
     */
    #[Route('/api/feedback', name: 'api_feedback', methods: ['POST'])]
    public function send(
        Request $request,
        FeedbackEmailSender $feedbackEmailSender,
        CacheItemPoolInterface $cache
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $ip = $request->getClientIp() ?? 'unknown';

        $cacheKey = 'feedback_'.hash('sha256', $ip);
        $rateLimitItem = $cache->getItem($cacheKey);
        $attempts = (int) ($rateLimitItem->isHit() ? $rateLimitItem->get() : 0);
        if ($attempts >= 5) {
            return $this->json(['error' => 'Has enviado demasiados mensajes. Intentalo mas tarde.'], 429);
        }

        $name = trim((string) ($data['name'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $category = trim((string) ($data['category'] ?? 'General'));
        $message = trim((string) ($data['message'] ?? ''));
        $consent = (bool) ($data['consent'] ?? false);

        if (!$consent) {
            return $this->json(['error' => 'Debes aceptar el envio del feedback.'], 400);
        }

        if ($message === '' || mb_strlen($message) < 10 || mb_strlen($message) > 2000) {
            return $this->json(['error' => 'El mensaje debe tener entre 10 y 2000 caracteres.'], 400);
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['error' => 'El email no es valido.'], 400);
        }

        $allowedCategories = ['General', 'Bug', 'Mejora', 'Usabilidad', 'Legal'];
        if (!in_array($category, $allowedCategories, true)) {
            $category = 'General';
        }

        $feedbackEmailSender->send([
            'name' => $name !== '' ? mb_substr($name, 0, 120) : 'Anonimo',
            'email' => $email,
            'category' => $category,
            'message' => $message,
            'ip' => $ip,
        ]);

        $rateLimitItem->set($attempts + 1);
        $rateLimitItem->expiresAfter(900);
        $cache->save($rateLimitItem);

        return $this->json(['message' => 'Feedback enviado correctamente.']);
    }
}
