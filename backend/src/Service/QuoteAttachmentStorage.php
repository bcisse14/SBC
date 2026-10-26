<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class QuoteAttachmentStorage
{
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public function __construct(private readonly string $projectDir)
    {
    }

    /** @return array{storagePath: string, originalName: string, mimeType: string, sizeBytes: int} */
    public function store(UploadedFile $file): array
    {
        if (!$file->isValid() || $file->getSize() > 8 * 1024 * 1024) {
            throw new \InvalidArgumentException('Chaque photo doit faire 8 Mo maximum.');
        }

        $mimeType = $file->getMimeType();
        if (!is_string($mimeType) || !isset(self::ALLOWED_MIME_TYPES[$mimeType])) {
            throw new \InvalidArgumentException('Format photo non autorise.');
        }

        $relativeDirectory = 'var/share/quotes/' . date('Y/m');
        $directory = $this->projectDir . '/' . $relativeDirectory;
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new \RuntimeException('Dossier de stockage indisponible.');
        }

        $filename = bin2hex(random_bytes(16)) . '.' . self::ALLOWED_MIME_TYPES[$mimeType];
        $absolutePath = $directory . '/' . $filename;
        $this->sanitizeImage($file->getPathname(), $absolutePath, $mimeType);

        return [
            'storagePath' => $relativeDirectory . '/' . $filename,
            'originalName' => $file->getClientOriginalName(),
            'mimeType' => $mimeType,
            'sizeBytes' => (int) filesize($absolutePath),
        ];
    }

    private function sanitizeImage(string $source, string $destination, string $mimeType): void
    {
        if (class_exists(\Imagick::class)) {
            $image = new \Imagick($source);
            $image->stripImage();
            $image->setImageFormat(self::ALLOWED_MIME_TYPES[$mimeType]);
            $image->writeImage($destination);
            $image->clear();
            $image->destroy();

            return;
        }

        if (function_exists('imagecreatefromstring')) {
            $image = imagecreatefromstring((string) file_get_contents($source));
            if ($image === false) {
                throw new \InvalidArgumentException('Image illisible.');
            }

            $success = match ($mimeType) {
                'image/jpeg' => imagejpeg($image, $destination, 90),
                'image/png' => imagepng($image, $destination, 6),
                'image/webp' => imagewebp($image, $destination, 90),
            };
            imagedestroy($image);
            if (!$success) {
                throw new \RuntimeException('Nettoyage des metadonnees impossible.');
            }

            return;
        }

        throw new \RuntimeException('Un moteur de traitement image est requis.');
    }
}
