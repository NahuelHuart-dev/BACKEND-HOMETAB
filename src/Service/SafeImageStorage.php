<?php

namespace App\Service;

class SafeImageStorage
{
    private const MAX_BYTES = 2 * 1024 * 1024;
    private const ALLOWED_EXTENSIONS = ['png', 'jpg', 'webp'];

    public function __construct(private string $projectDir)
    {
    }

    public function storeDataUrl(string $dataUrl, string $folder): ?string
    {
        if (!preg_match('/^data:image\/(png|jpeg|jpg|webp);base64,([A-Za-z0-9+\/=]+)$/', $dataUrl, $matches)) {
            return null;
        }

        $extension = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
        $bytes = base64_decode($matches[2], true);

        return $this->storeBytes($bytes, $extension, $folder);
    }

    public function deletePublicFile(?string $publicPath, string $folder): void
    {
        $prefix = '/uploads/'.$folder.'/';
        if (!$publicPath || !str_starts_with($publicPath, $prefix)) {
            return;
        }

        $directory = realpath($this->projectDir.'/public/uploads/'.$folder);
        $file = realpath($this->projectDir.'/public'.$publicPath);
        if ($directory && $file && str_starts_with($file, $directory) && is_file($file)) {
            unlink($file);
        }
    }

    private function storeBytes(string|false $bytes, string $extension, string $folder): ?string
    {
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            return null;
        }

        if ($bytes === false || strlen($bytes) > self::MAX_BYTES || !@getimagesizefromstring($bytes)) {
            return null;
        }

        $directory = $this->projectDir.'/public/uploads/'.$folder;
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $filename = bin2hex(random_bytes(16)).'.'.$extension;
        if (file_put_contents($directory.'/'.$filename, $bytes) === false) {
            return null;
        }

        chmod($directory.'/'.$filename, 0664);

        return '/uploads/'.$folder.'/'.$filename;
    }
}
