<?php

namespace App\Controller\twig;

use App\Entity\HouseholdMessage;
use App\Entity\User;
use App\Repository\HouseholdMessageRepository;
use App\Service\ChatTypingService;
use App\Service\HouseholdAccessService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/chat-widget')]
#[IsGranted('ROLE_USER')]
class ChatWidgetController extends AbstractController
{
    #[Route('/households', name: 'app_chat_widget_households', methods: ['GET'])]
    public function households(HouseholdAccessService $householdAccess, HouseholdMessageRepository $messageRepository): JsonResponse
    {
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

    #[Route('/households/{homeId}/messages', name: 'app_chat_widget_messages', methods: ['GET'])]
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
        $messages = $afterId !== null && ctype_digit((string) $afterId)
            ? $messageRepository->findAfterId($household, (int) $afterId, $limit)
            : $messageRepository->findRecentForHousehold(
                $household,
                $limit,
                $beforeId !== null && ctype_digit((string) $beforeId) ? (int) $beforeId : null
            );

        return $this->json(array_map([$this, 'mapMessageToArray'], $messages));
    }

    #[Route('/households/{homeId}/messages', name: 'app_chat_widget_message_new', methods: ['POST'])]
    public function new(
        int $homeId,
        Request $request,
        EntityManagerInterface $em,
        HouseholdAccessService $householdAccess,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): JsonResponse {
        if (!$csrfTokenManager->isTokenValid(new CsrfToken('chat_widget', (string) $request->headers->get('X-CSRF-Token')))) {
            return $this->json(['error' => 'Token CSRF invàlid'], 419);
        }

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
        $error = $this->validateContent($content);
        if ($error !== null) {
            return $this->json(['error' => $error], 400);
        }

        $message = (new HouseholdMessage())
            ->setHousehold($household)
            ->setSender($user)
            ->setContent($content);

        $em->persist($message);
        $em->flush();

        return $this->json([
            'message' => 'Missatge enviat correctament',
            'chatMessage' => $this->mapMessageToArray($message),
        ], 201);
    }

    #[Route('/households/{homeId}/typing', name: 'app_chat_widget_typing_mark', methods: ['POST'])]
    public function markTyping(
        int $homeId,
        Request $request,
        HouseholdAccessService $householdAccess,
        ChatTypingService $typingService,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): JsonResponse {
        if (!$csrfTokenManager->isTokenValid(new CsrfToken('chat_widget', (string) $request->headers->get('X-CSRF-Token')))) {
            return $this->json(['error' => 'Token CSRF invàlid'], 419);
        }

        /** @var User $user */
        $user = $this->getUser();
        $household = $householdAccess->getMemberHousehold($homeId, $user);
        if (!$household) {
            return $this->json(['error' => 'Accés denegat'], 403);
        }

        $typingService->markTyping($household, $user);

        return $this->json(['typing' => true]);
    }

    #[Route('/households/{homeId}/typing', name: 'app_chat_widget_typing_list', methods: ['GET'])]
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

    private function validateContent(string $content): ?string
    {
        if ($content === '') {
            return 'El missatge no pot estar buit';
        }

        return mb_strlen($content) > 1000 ? 'El missatge no pot superar els 1000 caràcters' : null;
    }

    private function mapMessageToArray(HouseholdMessage $message): array
    {
        $sender = $message->getSender();

        return [
            'id' => $message->getId(),
            'content' => $message->getContent(),
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

    private function formatDate(null|string|\DateTimeInterface $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('c');
        }

        return $value ? (new \DateTimeImmutable($value))->format('c') : null;
    }
}
