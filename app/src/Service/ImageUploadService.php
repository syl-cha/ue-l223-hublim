<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class ImageUploadService
{
    private const SIZES = [
        'thumb'  => [400, 300],    // mosaïque
        'medium' => [800, 600],    // page carte
        'full'   => [1920, 1080],  // galerie / lightbox
    ];

    private const WEBP_QUALITY = 88;
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

        // Nom de base sécurisé et unique
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeName = $this->slugger->slug($originalName);
        $baseName = $safeName . '-' . uniqid();

        // Génère les 3 variantes
        foreach (self::SIZES as $suffix => [$maxW, $maxH]) {
            $filename = $baseName . '-' . $suffix . '.webp';
            $this->processAndSave(
                $file->getPathname(),
                $this->uploadDir . '/' . $filename,
                $maxW,
                $maxH
            );
        }

        return [
            'filename' => $baseName, // stocké en base SANS suffix ni extension
            'size'     => filesize($this->uploadDir . '/' . $baseName . '-full.webp'),
        ];
    }

    private function processAndSave(string $sourcePath, string $destPath, int $maxW, int $maxH): void
    {
        $mime = mime_content_type($sourcePath);

        $source = match($mime) {
            'image/jpeg' => imagecreatefromjpeg($sourcePath),
            'image/png'  => imagecreatefrompng($sourcePath),
            'image/webp' => imagecreatefromwebp($sourcePath),
            default      => throw new \InvalidArgumentException('Format non supporté.')
        };

        [$width, $height] = getimagesize($sourcePath);
        [$newWidth, $newHeight] = $this->calculateDimensions($width, $height, $maxW, $maxH);

        $resized = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        imagewebp($resized, $destPath, self::WEBP_QUALITY);

        imagedestroy($source);
        imagedestroy($resized);
    }

    private function calculateDimensions(int $width, int $height, int $maxW, int $maxH): array
    {
        if ($width <= $maxW && $height <= $maxH) {
            return [$width, $height];
        }
        $ratio = min($maxW / $width, $maxH / $height);
        return [(int)($width * $ratio), (int)($height * $ratio)];
    }

    public function delete(string $baseName): void
    {
        foreach (array_keys(self::SIZES) as $suffix) {
            $path = $this->uploadDir . '/' . $baseName . '-' . $suffix . '.webp';
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }
}