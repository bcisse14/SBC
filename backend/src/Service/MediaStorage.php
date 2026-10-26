<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediaStorage
{
    private const MIME_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'video/mp4' => 'mp4',
    ];

    private const VARIANT_WIDTHS = [480, 800, 1200, 1600];

    public function __construct(private readonly string $projectDir)
    {
    }

    public function deleteFiles(string $path, array $variants): void
    {
        $paths = [$path, ...array_column($variants, 'path')];
        $root = realpath($this->projectDir . '/var/share/media');
        foreach ($paths as $relative) {
            $absolute = $this->projectDir . '/' . $relative;
            if (!file_exists($absolute)) {
                continue;
            }
            $resolved = realpath($absolute);
            if (!$root || !$resolved || !str_starts_with($resolved, $root . '/') || !is_file($resolved)) {
                throw new \RuntimeException('Chemin de média invalide. Suppression interrompue.');
            }
            if (!is_writable(dirname($resolved))) {
                throw new \RuntimeException('Suppression impossible : vérifiez les droits du stockage.');
            }
        }
        foreach ($paths as $relative) {
            $absolute = $this->projectDir . '/' . $relative;
            if (is_file($absolute) && !unlink($absolute)) {
                throw new \RuntimeException('Un fichier ne peut pas être supprimé. Réessayez la suppression.');
            }
        }
    }

    /** @return array{path: string, originalName: string, mimeType: string, altText: ?string, width: ?int, height: ?int, variants: list<array{w: int, format: string, path: string}>} */
    public function store(UploadedFile $file, ?string $altText = null): array
    {
        if (!$file->isValid() || ($file->getSize() ?? 0) > 30 * 1024 * 1024) {
            throw new \InvalidArgumentException('Le fichier dépasse 30 Mo ou est invalide.');
        }

        $mimeType = $file->getMimeType();
        if (!is_string($mimeType)) {
            throw new \InvalidArgumentException('Type de fichier non autorisé.');
        }

        return $this->ingest($file->getPathname(), $file->getClientOriginalName(), $mimeType, $altText);
    }

    /** @return array{path: string, originalName: string, mimeType: string, altText: ?string, width: ?int, height: ?int, variants: list<array{w: int, format: string, path: string}>} */
    public function ingest(string $sourcePath, string $originalName, string $mimeType, ?string $altText = null): array
    {
        if (!isset(self::MIME_EXTENSIONS[$mimeType])) {
            throw new \InvalidArgumentException('Type de fichier non autorisé.');
        }

        $relativeDirectory = 'var/share/media/' . date('Y/m');
        $directory = $this->projectDir . '/' . $relativeDirectory;
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException('Dossier de stockage indisponible.');
        }

        $base = bin2hex(random_bytes(16));
        $extension = self::MIME_EXTENSIONS[$mimeType];
        $destination = $directory . '/' . $base . '.' . $extension;

        if (!str_starts_with($mimeType, 'image/')) {
            if (!copy($sourcePath, $destination)) {
                throw new \RuntimeException('Copie du fichier impossible.');
            }

            return $this->result($relativeDirectory . '/' . $base . '.' . $extension, $originalName, $mimeType, $altText, null, null, []);
        }

        if (!class_exists(\Imagick::class)) {
            throw new \RuntimeException('Imagick est requis pour traiter les images.');
        }

        $image = new \Imagick($sourcePath);
        if (method_exists($image, 'autoOrient')) {
            $image->autoOrient();
        }
        $image->stripImage();
        $width = $image->getImageWidth();
        $height = $image->getImageHeight();

        $original = clone $image;
        $original->setImageFormat($extension);
        $original->writeImage($destination);
        $original->clear();

        $variants = $this->createVariants($image, $directory, $relativeDirectory, $base, $width);
        $image->clear();

        return $this->result($relativeDirectory . '/' . $base . '.' . $extension, $originalName, $mimeType, $altText, $width, $height, $variants);
    }

    /** @return list<array{w: int, format: string, path: string}> */
    private function createVariants(\Imagick $image, string $directory, string $relativeDirectory, string $base, int $sourceWidth): array
    {
        $widths = array_values(array_filter(self::VARIANT_WIDTHS, static fn (int $w) => $w < $sourceWidth));
        if ($sourceWidth <= max(self::VARIANT_WIDTHS)) {
            $widths[] = $sourceWidth;
        }

        $supported = array_map('strtoupper', \Imagick::queryFormats());
        $formats = ['webp'];
        if (in_array('AVIF', $supported, true)) {
            $formats[] = 'avif';
        }

        $variants = [];
        foreach ($widths as $w) {
            foreach ($formats as $format) {
                $copy = clone $image;
                if ($copy->getImageWidth() !== $w) {
                    $copy->resizeImage($w, 0, \Imagick::FILTER_LANCZOS, 1);
                }
                $copy->setImageFormat($format);
                $copy->setImageCompressionQuality($format === 'avif' ? 55 : 82);
                $file = sprintf('%s-%d.%s', $base, $w, $format);
                try {
                    $copy->writeImage($directory . '/' . $file);
                } catch (\ImagickException) {
                    // Encodeur AVIF absent de l'image : on garde WebP seul.
                    $formats = array_values(array_diff($formats, [$format]));
                    $copy->clear();
                    continue;
                }
                $copy->clear();
                $variants[] = ['w' => $w, 'format' => $format, 'path' => $relativeDirectory . '/' . $file];
            }
        }

        return $variants;
    }

    /** @param list<array{w: int, format: string, path: string}> $variants */
    private function result(string $path, string $originalName, string $mimeType, ?string $altText, ?int $width, ?int $height, array $variants): array
    {
        return [
            'path' => $path,
            'originalName' => $originalName,
            'mimeType' => $mimeType,
            'altText' => $altText,
            'width' => $width,
            'height' => $height,
            'variants' => $variants,
        ];
    }
}
