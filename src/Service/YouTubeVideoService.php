<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class YouTubeVideoService
{
    public function __construct(
        private HttpClientInterface $httpClient,
    ) {
    }

    /**
     * @return array<int, array{youtubeId: string, title: string, thumbnailUrl: ?string, channelTitle: ?string, url: string}>
     */
    public function search(string $query, int $limit = 5): array
    {
        $apiKey = $this->apiKey();
        if (!$apiKey) {
            throw new \RuntimeException('Falta configurar YOUTUBE_API_KEY.');
        }

        $response = $this->httpClient->request('GET', 'https://www.googleapis.com/youtube/v3/search', [
            'query' => [
                'key' => $apiKey,
                'part' => 'snippet',
                'type' => 'video',
                'maxResults' => max(1, min($limit, 5)),
                'q' => $query,
                'safeSearch' => 'moderate',
                'videoEmbeddable' => 'true',
            ],
        ]);

        $data = $response->toArray(false);
        $items = is_array($data['items'] ?? null) ? $data['items'] : [];

        return array_values(array_filter(array_map([$this, 'mapSearchItem'], $items)));
    }

    /**
     * @return array{youtubeId: string, title: string, thumbnailUrl: ?string, channelTitle: ?string, url: string}|null
     */
    public function videoFromUrlOrId(string $value): ?array
    {
        $youtubeId = $this->extractVideoId($value);
        if (!$youtubeId) {
            return null;
        }

        $apiKey = $this->apiKey();
        if (!$apiKey) {
            return [
                'youtubeId' => $youtubeId,
                'title' => 'Video de YouTube',
                'thumbnailUrl' => sprintf('https://img.youtube.com/vi/%s/hqdefault.jpg', $youtubeId),
                'channelTitle' => null,
                'url' => sprintf('https://www.youtube.com/watch?v=%s', $youtubeId),
            ];
        }

        $response = $this->httpClient->request('GET', 'https://www.googleapis.com/youtube/v3/videos', [
            'query' => [
                'key' => $apiKey,
                'part' => 'snippet',
                'id' => $youtubeId,
                'maxResults' => 1,
            ],
        ]);
        $data = $response->toArray(false);
        $item = $data['items'][0] ?? null;

        return is_array($item) ? $this->mapVideoItem($item) : null;
    }

    private function extractVideoId(string $value): ?string
    {
        $value = trim($value);
        if (preg_match('/^[A-Za-z0-9_-]{11}$/', $value)) {
            return $value;
        }

        if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|shorts/))([A-Za-z0-9_-]{11})~', $value, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function apiKey(): ?string
    {
        $apiKey = $_ENV['YOUTUBE_API_KEY'] ?? $_SERVER['YOUTUBE_API_KEY'] ?? getenv('YOUTUBE_API_KEY') ?: null;

        return is_string($apiKey) && trim($apiKey) !== '' ? trim($apiKey) : null;
    }

    private function mapSearchItem(array $item): ?array
    {
        $youtubeId = $item['id']['videoId'] ?? null;
        if (!$youtubeId) {
            return null;
        }

        $snippet = $item['snippet'] ?? [];

        return [
            'youtubeId' => $youtubeId,
            'title' => (string) ($snippet['title'] ?? 'Video de YouTube'),
            'thumbnailUrl' => $snippet['thumbnails']['medium']['url'] ?? $snippet['thumbnails']['default']['url'] ?? null,
            'channelTitle' => $snippet['channelTitle'] ?? null,
            'url' => sprintf('https://www.youtube.com/watch?v=%s', $youtubeId),
        ];
    }

    private function mapVideoItem(array $item): ?array
    {
        $youtubeId = $item['id'] ?? null;
        if (!$youtubeId) {
            return null;
        }

        $snippet = $item['snippet'] ?? [];

        return [
            'youtubeId' => $youtubeId,
            'title' => (string) ($snippet['title'] ?? 'Video de YouTube'),
            'thumbnailUrl' => $snippet['thumbnails']['medium']['url'] ?? $snippet['thumbnails']['default']['url'] ?? null,
            'channelTitle' => $snippet['channelTitle'] ?? null,
            'url' => sprintf('https://www.youtube.com/watch?v=%s', $youtubeId),
        ];
    }
}
