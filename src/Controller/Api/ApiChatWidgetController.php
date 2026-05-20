<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\HouseholdMessageRepository;
use App\Service\HouseholdAccessService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controlador de API para el widget de chat.
 * Proporciona endpoints para interactuar con los hogares y sus mensajes.
 */
#[Route('/api/chat')]
#[IsGranted('ROLE_USER')]
class ApiChatWidgetController extends AbstractController
{
    /**
     * Obtiene la lista de hogares a los que pertenece el usuario con información de sus mensajes.
     * 
     * @param HouseholdAccessService $householdAccess Servicio para acceder a los hogares del usuario.
     * @param HouseholdMessageRepository $messageRepository Repositorio para acceder a los mensajes del hogar.
     * @return JsonResponse Respuesta JSON con la lista de hogares, cantidad de mensajes y última actividad.
     */
    #[Route('/households', name: 'api_chat_households', methods: ['GET'])]
    public function households(
        HouseholdAccessService $householdAccess,
        HouseholdMessageRepository $messageRepository,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        return $this->json(array_map(
            fn ($household): array => [
                'id' => $household->getId(),
                'name' => $household->getName(),
                'messageCount' => $messageRepository->countActiveForHousehold($household),
                'lastActivity' => $this->formatDate($messageRepository->lastActivityForHousehold($household)),
            ],
            $householdAccess->getMemberHouseholds($user)
        ));
    }

    private function formatDate(null|string|\DateTimeInterface $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('c');
        }

        return $value ? (new \DateTimeImmutable($value))->format('c') : null;
    }
}
