<?php

namespace App\Controller\Api;

use App\Entity\HouseholdMessage;
use App\Entity\User;
use App\Repository\HouseholdMessageRepository;
use App\Service\ChatTypingService;
use App\Service\HouseholdAccessService;
use App\Service\SafeImageStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controlador de API para el chat del hogar.
 * Permite listar mensajes, enviar mensajes y gestionar el estado de "escribiendo".
 */
#[Route('/api/households/{homeId}/chat')]
#[IsGranted('ROLE_USER')]
class ApiHouseholdChatController extends AbstractController
{
    /**
     * Obtiene los mensajes del chat de un hogar específico.
     * 
     * @param int $homeId ID del hogar.
     * @param Request $request Petición HTTP con posibles parámetros limit, afterId, beforeId.
     * @param HouseholdAccessService $householdAccess Servicio de acceso al hogar.
     * @param HouseholdMessageRepository $messageRepository Repositorio de mensajes.
     * @return JsonResponse Respuesta JSON con la lista de mensajes.
     */
    #[Route('/messages', name: 'api_household_chat_messages', methods: ['GET'])]
    public function messages(
        int $homeId,
        Request $request,
        HouseholdAccessService $householdAccess,
        HouseholdMessageRepository $messageRepository,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $household = $householdAccess->getMemberHousehold($homeId, $user);

        if (!$household) {
            return $this->json(['error' => 'Accés denegat'], 403);
        }

        $limit = (int) $request->query->get('limit', 50);
        $afterId = $request->query->get('afterId');
        $beforeId = $request->query->get('beforeId');

        if ($afterId !== null && ctype_digit((string) $afterId)) {
            $messages = $messageRepository->findAfterId($household, (int) $afterId, $limit);
        } else {
            $messages = $messageRepository->findRecentForHousehold(
                $household,
                $limit,
                $beforeId !== null && ctype_digit((string) $beforeId) ? (int) $beforeId : null
            );
        }

        return $this->json(array_map([$this, 'mapMessageToArray'], $messages), 200);
    }

    /**
     * Crea y envía un nuevo mensaje al chat de un hogar.
     * 
     * @param int $homeId ID del hogar.
     * @param Request $request Petición HTTP con el contenido del mensaje y/o imagen.
     * @param EntityManagerInterface $em Manejador de entidades de Doctrine.
     * @param HouseholdAccessService $householdAccess Servicio de acceso al hogar.
     * @param SafeImageStorage $imageStorage Servicio para almacenamiento seguro de imágenes.
     * @return JsonResponse Respuesta JSON confirmando el envío y los datos del mensaje.
     */
    #[Route('/messages', name: 'api_household_chat_message_new', methods: ['POST'])]
    public function new(
        int $homeId,
        Request $request,
        EntityManagerInterface $em,
        HouseholdAccessService $householdAccess,
        SafeImageStorage $imageStorage,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $household = $householdAccess->getMemberHousehold($homeId, $user);

        if (!$household) {
            return $this->json(['error' => 'Accés denegat'], 403);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['error' => 'JSON invàlid'], 400);
        }

        $content = trim((string) ($data['content'] ?? ''));
        $imagePath = null;
        if (!empty($data['imageData'])) {
            $imagePath = $imageStorage->storeDataUrl((string) $data['imageData'], 'chats');
            if ($imagePath === null) {
                return $this->json(['error' => 'No se pudo procesar la imagen del chat.'], 400);
            }
        }

        $error = $this->validateContent($content, $imagePath !== null);
        if ($error !== null) {
            return $this->json(['error' => $error], 400);
        }

        $message = new HouseholdMessage();
        $message->setHousehold($household);
        $message->setSender($user);
        $message->setContent($content);
        $message->setImagePath($imagePath);

        $em->persist($message);
        $em->flush();

        return $this->json([
            'message' => 'Missatge enviat correctament',
            'chatMessage' => $this->mapMessageToArray($message),
        ], 201);
    }

    /**
     * Marca que el usuario actual está escribiendo en el chat del hogar.
     * 
     * @param int $homeId ID del hogar.
     * @param HouseholdAccessService $householdAccess Servicio de acceso al hogar.
     * @param ChatTypingService $typingService Servicio de gestión de usuarios escribiendo.
     * @return JsonResponse Respuesta JSON confirmando la acción.
     */
    #[Route('/typing', name: 'api_household_chat_typing_mark', methods: ['POST'])]
    public function markTyping(
        int $homeId,
        HouseholdAccessService $householdAccess,
        ChatTypingService $typingService,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $household = $householdAccess->getMemberHousehold($homeId, $user);

        if (!$household) {
            return $this->json(['error' => 'Accés denegat'], 403);
        }

        $typingService->markTyping($household, $user);

        return $this->json(['typing' => true]);
    }

    /**
     * Obtiene la lista de usuarios que están escribiendo en este momento en el chat.
     * 
     * @param int $homeId ID del hogar.
     * @param HouseholdAccessService $householdAccess Servicio de acceso al hogar.
     * @param ChatTypingService $typingService Servicio de gestión de usuarios escribiendo.
     * @return JsonResponse Respuesta JSON con los usuarios activos escribiendo.
     */
    #[Route('/typing', name: 'api_household_chat_typing_list', methods: ['GET'])]
    public function typing(
        int $homeId,
        HouseholdAccessService $householdAccess,
        ChatTypingService $typingService,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $household = $householdAccess->getMemberHousehold($homeId, $user);

        if (!$household) {
            return $this->json(['error' => 'Accés denegat'], 403);
        }

        return $this->json([
            'typing' => $typingService->activeTypers($household, $user, $householdAccess->getHouseholdUsers($household)),
        ]);
    }

    /**
     * Edita un mensaje de chat existente si el usuario es su autor.
     * 
     * @param int $homeId ID del hogar.
     * @param int $id ID del mensaje a editar.
     * @param Request $request Petición HTTP con el nuevo contenido.
     * @param EntityManagerInterface $em Manejador de entidades.
     * @param HouseholdAccessService $householdAccess Servicio de acceso al hogar.
     * @param HouseholdMessageRepository $messageRepository Repositorio de mensajes.
     * @return JsonResponse Respuesta JSON indicando éxito y devolviendo el mensaje editado.
     */
    #[Route('/messages/{id}', name: 'api_household_chat_message_edit', methods: ['PATCH', 'PUT'])]
    public function edit(
        int $homeId,
        int $id,
        Request $request,
        EntityManagerInterface $em,
        HouseholdAccessService $householdAccess,
        HouseholdMessageRepository $messageRepository,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $household = $householdAccess->getMemberHousehold($homeId, $user);

        if (!$household) {
            return $this->json(['error' => 'Accés denegat'], 403);
        }

        $message = $messageRepository->findOneBy([
            'id' => $id,
            'household' => $household,
            'isActive' => true,
        ]);

        if (!$message) {
            return $this->json(['error' => 'Missatge no trobat'], 404);
        }

        if (!$this->canManageMessage($message, $user)) {
            return $this->json(['error' => 'Només pots editar els teus missatges'], 403);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['error' => 'JSON invàlid'], 400);
        }

        $content = trim((string) ($data['content'] ?? ''));
        $error = $this->validateContent($content);
        if ($error !== null) {
            return $this->json(['error' => $error], 400);
        }

        $message->setContent($content);
        $message->setEditedAt(new \DateTime());
        $em->flush();

        return $this->json([
            'message' => 'Missatge actualitzat',
            'chatMessage' => $this->mapMessageToArray($message),
        ], 200);
    }

    /**
     * Elimina un mensaje de chat (baja lógica) si el usuario es su autor.
     * 
     * @param int $homeId ID del hogar.
     * @param int $id ID del mensaje a eliminar.
     * @param EntityManagerInterface $em Manejador de entidades.
     * @param HouseholdAccessService $householdAccess Servicio de acceso al hogar.
     * @param HouseholdMessageRepository $messageRepository Repositorio de mensajes.
     * @return JsonResponse Respuesta JSON confirmando la eliminación del mensaje.
     */
    #[Route('/messages/{id}', name: 'api_household_chat_message_delete', methods: ['DELETE'])]
    public function delete(
        int $homeId,
        int $id,
        EntityManagerInterface $em,
        HouseholdAccessService $householdAccess,
        HouseholdMessageRepository $messageRepository,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $household = $householdAccess->getMemberHousehold($homeId, $user);

        if (!$household) {
            return $this->json(['error' => 'Accés denegat'], 403);
        }

        $message = $messageRepository->findOneBy([
            'id' => $id,
            'household' => $household,
            'isActive' => true,
        ]);

        if (!$message) {
            return $this->json(['error' => 'Missatge no trobat'], 404);
        }

        if (!$this->canManageMessage($message, $user)) {
            return $this->json(['error' => 'Només pots esborrar els teus missatges'], 403);
        }

        $message->setIsActive(false);
        $em->flush();

        return $this->json(['message' => 'Missatge esborrat']);
    }

    private function validateContent(string $content, bool $hasImage = false): ?string
    {
        if ($content === '' && !$hasImage) {
            return 'El missatge no pot estar buit';
        }

        if (mb_strlen($content) > 1000) {
            return 'El missatge no pot superar els 1000 caràcters';
        }

        return null;
    }

    private function canManageMessage(HouseholdMessage $message, User $user): bool
    {
        return $message->getSender()?->getId() === $user->getId();
    }

    private function mapMessageToArray(HouseholdMessage $message): array
    {
        $sender = $message->getSender();

        return [
            'id' => $message->getId(),
            'content' => $message->getContent(),
            'imagePath' => $message->getImagePath(),
            'createdAt' => $message->getCreatedAt()?->format('c'),
            'editedAt' => $message->getEditedAt()?->format('c'),
            'sender' => $sender ? [
                'id' => $sender->getId(),
                'firstName' => $sender->getFirstName(),
                'lastName' => $sender->getLastName(),
                'fullName' => $sender->getFullName(),
                'avatar' => $sender->getAvatar(),
                'avatarIcon' => $sender->getAvatarIcon(),
            ] : null,
        ];
    }

}
