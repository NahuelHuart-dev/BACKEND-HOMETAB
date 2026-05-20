<?php

namespace App\Service;

use App\Entity\Household;
use App\Entity\User;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;

class ChatTypingService
{
    public function __construct(private readonly CacheItemPoolInterface $cache)
    {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function markTyping(Household $household, User $user): void
    {
        $item = $this->cache->getItem($this->key($household, $user));
        $item->set(['typing' => true, 'at' => time()]);
        $item->expiresAfter(6);

        $this->cache->save($item);
    }

    /**
     * @param User[] $members
     *
     * @return array<int, array{id: int|null, fullName: string}>
     */
    public function activeTypers(Household $household, User $viewer, array $members): array
    {
        $typers = [];
        foreach ($members as $member) {
            if ($member->getId() === $viewer->getId()) {
                continue;
            }

            try {
                $item = $this->cache->getItem($this->key($household, $member));
                $state = $item->isHit() ? $item->get() : [];
            } catch (InvalidArgumentException) {
                $state = [];
            }

            if (($state['typing'] ?? false) === true) {
                $typers[] = [
                    'id' => $member->getId(),
                    'fullName' => $member->getFullName(),
                ];
            }
        }

        return $typers;
    }

    private function key(Household $household, User $user): string
    {
        return sprintf('chat_typing_%d_%d', $household->getId(), $user->getId());
    }
}
