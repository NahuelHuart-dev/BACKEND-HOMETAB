<?php

namespace App\Controller\twig;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/documentacio')]
#[IsGranted('ROLE_SUPER_ADMIN')]
class AdminDocumentationController extends AbstractController
{
    #[Route('', name: 'app_admin_documentation', methods: ['GET'])]
    public function index(): RedirectResponse
    {
        return $this->redirectToRoute('app_admin_documentation_file', ['path' => 'index.html']);
    }

    #[Route('/{path}', name: 'app_admin_documentation_file', requirements: ['path' => '.+'], methods: ['GET'])]
    public function asset(string $path): Response
    {
        return $this->serve($path);
    }

    private function serve(string $path): Response
    {
        $projectDir = (string) $this->getParameter('kernel.project_dir');
        $siteDir = $projectDir.'/docs/mkdocs/site';
        $realSiteDir = realpath($siteDir);

        if (!$realSiteDir || !is_dir($realSiteDir)) {
            return $this->render('admin/documentation_missing.html.twig', [
                'mkdocsPath' => 'docs/mkdocs',
                'buildCommand' => 'python -m mkdocs build -f docs/mkdocs/mkdocs.yml',
            ], new Response('', Response::HTTP_SERVICE_UNAVAILABLE));
        }

        $normalizedPath = trim($path, "/\\") ?: 'index.html';
        $targetPath = $siteDir.'/'.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $normalizedPath);

        if (is_dir($targetPath)) {
            $targetPath .= DIRECTORY_SEPARATOR.'index.html';
        }

        $realTargetPath = realpath($targetPath);
        if (!$realTargetPath || !is_file($realTargetPath)) {
            throw new NotFoundHttpException('Documentacio no trobada.');
        }

        $insideSite = $realTargetPath === $realSiteDir
            || str_starts_with($realTargetPath, $realSiteDir.DIRECTORY_SEPARATOR);

        if (!$insideSite) {
            throw new NotFoundHttpException('Ruta de documentacio no valida.');
        }

        $response = new BinaryFileResponse($realTargetPath);
        $response->headers->set('Content-Type', $this->contentTypeFor($realTargetPath));

        return $response;
    }

    private function contentTypeFor(string $path): string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'html' => 'text/html; charset=UTF-8',
            'css' => 'text/css; charset=UTF-8',
            'js' => 'application/javascript; charset=UTF-8',
            'json' => 'application/json; charset=UTF-8',
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            default => 'application/octet-stream',
        };
    }
}
