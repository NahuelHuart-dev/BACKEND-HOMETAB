<?php

namespace App\Controller\Api;

use App\Entity\MultimediaPlaylist;
use App\Entity\MultimediaVideo;
use App\Entity\User;
use App\Repository\MultimediaPlaylistRepository;
use App\Service\HouseholdAccessService;
use App\Service\YouTubeVideoService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controlador de API para la gestión multimedia en un hogar.
 * Permite buscar videos en YouTube y gestionar listas de reproducción.
 */
#[Route('/api/households/{homeId}/multimedia')]
#[IsGranted('ROLE_USER')]
class ApiMultimediaController extends AbstractController
{
    /**
     * Obtiene las listas de reproducción de un hogar.
     * 
     * @param int $homeId ID del hogar.
     * @param HouseholdAccessService $householdAccess Servicio de acceso al hogar.
     * @param MultimediaPlaylistRepository $playlistRepository Repositorio de listas de reproducción.
     * @return JsonResponse Respuesta JSON con las listas de reproducción.
     */
    #[Route('/playlists', name: 'api_multimedia_playlists', methods: ['GET'])]
    public function playlists(
        int $homeId,
        HouseholdAccessService $householdAccess,
        MultimediaPlaylistRepository $playlistRepository,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $household = $householdAccess->getMemberHousehold($homeId, $user);
        if (!$household) {
            return $this->json(['error' => 'Accés denegat'], 403);
        }

        return $this->json([
            'playlists' => array_map([$this, 'mapPlaylist'], $playlistRepository->findForHousehold($household)),
        ]);
    }

    /**
     * Crea una nueva lista de reproducción en un hogar.
     * 
     * @param int $homeId ID del hogar.
     * @param Request $request Petición HTTP con el nombre de la playlist.
     * @param EntityManagerInterface $em Manejador de entidades.
     * @param HouseholdAccessService $householdAccess Servicio de acceso al hogar.
     * @return JsonResponse Respuesta JSON confirmando la creación.
     */
    #[Route('/playlists', name: 'api_multimedia_playlist_create', methods: ['POST'])]
    public function createPlaylist(
        int $homeId,
        Request $request,
        EntityManagerInterface $em,
        HouseholdAccessService $householdAccess,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $household = $householdAccess->getMemberHousehold($homeId, $user);
        if (!$household) {
            return $this->json(['error' => 'Accés denegat'], 403);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '' || mb_strlen($name) > 120) {
            return $this->json(['error' => 'El nombre de la playlist es obligatorio y no puede superar 120 caracteres.'], 400);
        }

        $playlist = new MultimediaPlaylist();
        $playlist->setHousehold($household);
        $playlist->setCreatedBy($user);
        $playlist->setName($name);

        $em->persist($playlist);
        $em->flush();

        return $this->json([
            'message' => 'Playlist creada correctamente.',
            'playlist' => $this->mapPlaylist($playlist),
        ], 201);
    }

    /**
     * Busca videos en YouTube por término de búsqueda.
     * 
     * @param int $homeId ID del hogar.
     * @param Request $request Petición HTTP con el término a buscar (q).
     * @param HouseholdAccessService $householdAccess Servicio de acceso al hogar.
     * @param YouTubeVideoService $youTubeVideoService Servicio de YouTube.
     * @return JsonResponse Respuesta JSON con los resultados de la búsqueda.
     */
    #[Route('/search', name: 'api_multimedia_youtube_search', methods: ['GET'])]
    public function search(
        int $homeId,
        Request $request,
        HouseholdAccessService $householdAccess,
        YouTubeVideoService $youTubeVideoService,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $household = $householdAccess->getMemberHousehold($homeId, $user);
        if (!$household) {
            return $this->json(['error' => 'Accés denegat'], 403);
        }

        $query = trim((string) $request->query->get('q', ''));
        if (mb_strlen($query) < 2) {
            return $this->json(['error' => 'Escribe al menos 2 caracteres para buscar.'], 400);
        }

        try {
            return $this->json(['videos' => $youTubeVideoService->search($query, 5)]);
        } catch (\Throwable $exception) {
            return $this->json(['error' => $exception->getMessage()], 503);
        }
    }

    /**
     * Añade un video a una lista de reproducción.
     * 
     * @param int $homeId ID del hogar.
     * @param int $playlistId ID de la lista de reproducción.
     * @param Request $request Petición HTTP con los datos del video o URL.
     * @param EntityManagerInterface $em Manejador de entidades.
     * @param HouseholdAccessService $householdAccess Servicio de acceso al hogar.
     * @param MultimediaPlaylistRepository $playlistRepository Repositorio de listas de reproducción.
     * @param YouTubeVideoService $youTubeVideoService Servicio de YouTube.
     * @return JsonResponse Respuesta JSON confirmando que el video fue añadido.
     */
    #[Route('/playlists/{playlistId}/videos', name: 'api_multimedia_video_add', methods: ['POST'])]
    public function addVideo(
        int $homeId,
        int $playlistId,
        Request $request,
        EntityManagerInterface $em,
        HouseholdAccessService $householdAccess,
        MultimediaPlaylistRepository $playlistRepository,
        YouTubeVideoService $youTubeVideoService,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $household = $householdAccess->getMemberHousehold($homeId, $user);
        if (!$household) {
            return $this->json(['error' => 'Accés denegat'], 403);
        }

        $playlist = $playlistRepository->find($playlistId);
        if (!$playlist || $playlist->getHousehold()?->getId() !== $household->getId()) {
            return $this->json(['error' => 'Playlist no encontrada.'], 404);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $video = null;
        if (!empty($data['youtubeId'])) {
            $video = [
                'youtubeId' => (string) $data['youtubeId'],
                'title' => trim((string) ($data['title'] ?? 'Video de YouTube')),
                'thumbnailUrl' => $data['thumbnailUrl'] ?? null,
                'channelTitle' => $data['channelTitle'] ?? null,
            ];
        } elseif (!empty($data['url'])) {
            $video = $youTubeVideoService->videoFromUrlOrId((string) $data['url']);
        }

        if (!$video || !preg_match('/^[A-Za-z0-9_-]{11}$/', $video['youtubeId'])) {
            return $this->json(['error' => 'No se ha podido reconocer el video de YouTube.'], 400);
        }

        $item = new MultimediaVideo();
        $item->setPlaylist($playlist);
        $item->setAddedBy($user);
        $item->setYoutubeId($video['youtubeId']);
        $item->setTitle(mb_substr($video['title'] ?: 'Video de YouTube', 0, 255));
        $item->setThumbnailUrl($video['thumbnailUrl'] ?? null);
        $item->setChannelTitle($video['channelTitle'] ?? null);
        $item->setPosition(count($playlist->getVideos()) + 1);

        $em->persist($item);
        $em->flush();

        return $this->json([
            'message' => 'Video añadido a la playlist.',
            'video' => $this->mapVideo($item),
        ], 201);
    }

    private function mapPlaylist(MultimediaPlaylist $playlist): array
    {
        $creator = $playlist->getCreatedBy();

        return [
            'id' => $playlist->getId(),
            'name' => $playlist->getName(),
            'createdAt' => $playlist->getCreatedAt()->format('c'),
            'createdBy' => $creator ? [
                'id' => $creator->getId(),
                'fullName' => $creator->getFullName(),
                'avatar' => $creator->getAvatar(),
                'avatarIcon' => $creator->getAvatarIcon(),
            ] : null,
            'videos' => array_map([$this, 'mapVideo'], $playlist->getVideos()->toArray()),
        ];
    }

    private function mapVideo(MultimediaVideo $video): array
    {
        $creator = $video->getAddedBy();

        return [
            'id' => $video->getId(),
            'youtubeId' => $video->getYoutubeId(),
            'title' => $video->getTitle(),
            'thumbnailUrl' => $video->getThumbnailUrl(),
            'channelTitle' => $video->getChannelTitle(),
            'url' => sprintf('https://www.youtube.com/watch?v=%s', $video->getYoutubeId()),
            'embedUrl' => sprintf('https://www.youtube.com/embed/%s', $video->getYoutubeId()),
            'createdAt' => $video->getCreatedAt()->format('c'),
            'addedBy' => $creator ? [
                'id' => $creator->getId(),
                'fullName' => $creator->getFullName(),
                'avatar' => $creator->getAvatar(),
                'avatarIcon' => $creator->getAvatarIcon(),
            ] : null,
        ];
    }
}
