<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class ImageUploadService
{
    private const MAX_WIDTH = 1200;
    private const MAX_HEIGHT = 900;
    private const WEBP_QUALITY = 82;
    private const ALLOWED_MIME = ['image/jpeg', 'image/png', 'image/webp'];
    private const MAX_SIZE = 5 * 1024 * 1024; // 5MB

    public function __construct(
        private string $uploadDir,
        private SluggerInterface $slugger
    ) {}

    public function upload(UploadedFile $file): array
    {
        // Vérifie le vrai type MIME (pas celui déclaré par le navigateur)
        $realMime = mime_content_type($file->getPathname());
        if (!in_array($realMime, self::ALLOWED_MIME)) {
            throw new \InvalidArgumentException('Type de fichier non autorisé. Formats acceptés : JPG, PNG, WebP.');
        }

        if ($file->getSize() > self::MAX_SIZE) {
            throw new \InvalidArgumentException('Fichier trop volumineux (maximum 5MB).');
        }

        // Nom sécurisé unique
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeName = $this->slugger->slug($originalName);
        $newFilename = $safeName . '-' . uniqid() . '.webp';

        $this->processAndSave($file->getPathname(), $this->uploadDir . '/' . $newFilename);

        return [
            'filename' => $newFilename,
            'size'     => filesize($this->uploadDir . '/' . $newFilename),
        ];
    }

    private function processAndSave(string $sourcePath, string $destPath): void
    {
        $mime = mime_content_type($sourcePath);

        $source = match($mime) {
            'image/jpeg' => imagecreatefromjpeg($sourcePath),
            'image/png'  => imagecreatefrompng($sourcePath),
            'image/webp' => imagecreatefromwebp($sourcePath),
            default      => throw new \InvalidArgumentException('Format non supporté.')
        };

        [$width, $height] = getimagesize($sourcePath);
        [$newWidth, $newHeight] = $this->calculateDimensions($width, $height);

        $resized = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        imagewebp($resized, $destPath, self::WEBP_QUALITY);

        imagedestroy($source);
        imagedestroy($resized);
    }

    private function calculateDimensions(int $width, int $height): array
    {
        if ($width <= self::MAX_WIDTH && $height <= self::MAX_HEIGHT) {
            return [$width, $height];
        }
        $ratio = min(self::MAX_WIDTH / $width, self::MAX_HEIGHT / $height);
        return [(int)($width * $ratio), (int)($height * $ratio)];
    }

    public function delete(string $filename): void
    {
        $path = $this->uploadDir . '/' . $filename;
        if (file_exists($path)) {
            unlink($path);
        }
    }
}