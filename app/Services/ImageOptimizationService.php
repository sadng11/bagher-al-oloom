<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageOptimizationService
{
    public const DEFAULT_MAX_DIMENSION = 1920;
    public const DEFAULT_THUMB_DIMENSION = 600;
    public const DEFAULT_QUALITY = 82;

    /**
     * Optimize an image on the given disk and generate a lightweight thumbnail.
     *
     * @return array{
     *     image: string,
     *     thumbnail: string,
     *     width: int|null,
     *     height: int|null,
     *     file_size: int|null,
     *     original_extension: string|null
     * }
     */
    public function optimize(
        string $disk,
        string $sourcePath,
        string $targetDirectory = 'photos',
        int $maxDimension = self::DEFAULT_MAX_DIMENSION,
        int $thumbMaxDimension = self::DEFAULT_THUMB_DIMENSION,
        int $quality = self::DEFAULT_QUALITY
    ): array {
        $storage = Storage::disk($disk);
        $originalExtension = strtolower((string) pathinfo($sourcePath, PATHINFO_EXTENSION)) ?: null;

        if (! $storage->exists($sourcePath)) {
            return [
                'image' => $sourcePath,
                'thumbnail' => $sourcePath,
                'width' => null,
                'height' => null,
                'file_size' => null,
                'original_extension' => $originalExtension,
            ];
        }

        $contents = $storage->get($sourcePath);
        $rawSize = strlen($contents);
        $imageInfo = @getimagesizefromstring($contents);
        $rawWidth = is_array($imageInfo) ? (int) $imageInfo[0] : null;
        $rawHeight = is_array($imageInfo) ? (int) $imageInfo[1] : null;
        $imageType = is_array($imageInfo) ? (int) $imageInfo[2] : null;

        $isAnimatedGif = $imageType === IMAGETYPE_GIF && $this->isAnimatedGif($contents);

        $gd = @imagecreatefromstring($contents);
        if ($gd === false) {
            // Preserve unsupported GD codecs (e.g. WebP/AVIF when server GD lacks libwebp/libavif) safely.
            return [
                'image' => $sourcePath,
                'thumbnail' => $sourcePath,
                'width' => $rawWidth,
                'height' => $rawHeight,
                'file_size' => $rawSize,
                'original_extension' => $originalExtension,
            ];
        }

        if ($imageType === IMAGETYPE_JPEG) {
            $gd = $this->applyExifOrientation($gd, $contents);
        }

        $currentWidth = imagesx($gd);
        $currentHeight = imagesy($gd);
        $preserveAlpha = $this->hasTransparency($gd, $imageType);

        $targetFormat = $this->resolveTargetFormat($preserveAlpha);
        $baseName = Str::uuid()->toString();
        $dir = trim($targetDirectory, '/');

        if ($isAnimatedGif) {
            $imagePath = "{$dir}/{$baseName}.gif";
            if ($imagePath !== $sourcePath) {
                $storage->put($imagePath, $contents);
                $storage->delete($sourcePath);
            }
            $finalWidth = $currentWidth;
            $finalHeight = $currentHeight;
            $finalSize = $rawSize;
        } else {
            [$optimizedGd, $finalWidth, $finalHeight] = $this->resizeIfNeeded(
                $gd,
                $currentWidth,
                $currentHeight,
                $maxDimension,
                $preserveAlpha
            );
            $optimizedBytes = $this->encode($optimizedGd, $targetFormat, $quality);
            unset($optimizedGd);

            $imagePath = "{$dir}/{$baseName}.{$targetFormat}";
            $storage->put($imagePath, $optimizedBytes);
            if ($imagePath !== $sourcePath) {
                $storage->delete($sourcePath);
            }
            $finalSize = strlen($optimizedBytes);
        }

        [$thumbGd] = $this->resizeIfNeeded(
            $gd,
            $currentWidth,
            $currentHeight,
            $thumbMaxDimension,
            $preserveAlpha
        );
        $thumbBytes = $this->encode($thumbGd, $targetFormat, $quality);
        unset($thumbGd, $gd);

        $thumbnailPath = "{$dir}/thumbs/{$baseName}_thumb.{$targetFormat}";
        $storage->put($thumbnailPath, $thumbBytes);

        return [
            'image' => $imagePath,
            'thumbnail' => $thumbnailPath,
            'width' => $finalWidth,
            'height' => $finalHeight,
            'file_size' => $finalSize,
            'original_extension' => $originalExtension,
        ];
    }

    /**
     * Delete existing image and thumbnail files from the specified disk.
     */
    public function deleteFiles(string $disk, ?string ...$paths): void
    {
        $storage = Storage::disk($disk);

        foreach (array_unique(array_filter($paths)) as $path) {
            if ($storage->exists($path)) {
                $storage->delete($path);
            }
        }
    }

    private function resolveTargetFormat(bool $preserveAlpha): string
    {
        if (function_exists('imagewebp')) {
            return 'webp';
        }

        return $preserveAlpha ? 'png' : 'jpg';
    }

    /**
     * @param  \GdImage|resource  $source
     * @return array{0: \GdImage|resource, 1: int, 2: int}
     */
    private function resizeIfNeeded($source, int $srcWidth, int $srcHeight, int $maxDimension, bool $preserveAlpha): array
    {
        if ($srcWidth <= $maxDimension && $srcHeight <= $maxDimension) {
            $targetWidth = $srcWidth;
            $targetHeight = $srcHeight;
        } elseif ($srcWidth >= $srcHeight) {
            $targetWidth = $maxDimension;
            $targetHeight = max(1, (int) round($srcHeight * ($maxDimension / $srcWidth)));
        } else {
            $targetHeight = $maxDimension;
            $targetWidth = max(1, (int) round($srcWidth * ($maxDimension / $srcHeight)));
        }

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);

        if ($preserveAlpha) {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefilledrectangle($canvas, 0, 0, $targetWidth, $targetHeight, $transparent);
        } else {
            $white = imagecolorallocate($canvas, 255, 255, 255);
            imagefilledrectangle($canvas, 0, 0, $targetWidth, $targetHeight, $white);
        }

        imagecopyresampled(
            $canvas,
            $source,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $srcWidth,
            $srcHeight
        );

        return [$canvas, $targetWidth, $targetHeight];
    }

    /**
     * @param  \GdImage|resource  $gd
     */
    private function encode($gd, string $format, int $quality): string
    {
        ob_start();
        if ($format === 'webp' && function_exists('imagewebp')) {
            imagewebp($gd, null, $quality);
        } elseif ($format === 'png') {
            imagealphablending($gd, false);
            imagesavealpha($gd, true);
            imagepng($gd, null, 6);
        } else {
            imageinterlace($gd, true);
            imagejpeg($gd, null, $quality);
        }

        return (string) ob_get_clean();
    }

    /**
     * @param  \GdImage|resource  $gd
     * @return \GdImage|resource
     */
    private function applyExifOrientation($gd, string $contents)
    {
        if (! function_exists('exif_read_data')) {
            return $gd;
        }

        $stream = fopen('php://temp', 'r+b');
        if ($stream === false) {
            return $gd;
        }

        fwrite($stream, $contents);
        rewind($stream);
        $exif = @exif_read_data($stream);
        fclose($stream);

        $orientation = is_array($exif) ? ($exif['Orientation'] ?? null) : null;

        return match ($orientation) {
            3 => imagerotate($gd, 180, 0) ?: $gd,
            6 => imagerotate($gd, -90, 0) ?: $gd,
            8 => imagerotate($gd, 90, 0) ?: $gd,
            default => $gd,
        };
    }

    /**
     * @param  \GdImage|resource  $gd
     */
    private function hasTransparency($gd, ?int $imageType): bool
    {
        if (! in_array($imageType, [IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP], true)) {
            return false;
        }

        if (imagecolortransparent($gd) >= 0) {
            return true;
        }

        if (! imageistruecolor($gd)) {
            return false;
        }

        $width = imagesx($gd);
        $height = imagesy($gd);
        $stepX = max(1, (int) floor($width / 20));
        $stepY = max(1, (int) floor($height / 20));

        for ($x = 0; $x < $width; $x += $stepX) {
            for ($y = 0; $y < $height; $y += $stepY) {
                $rgba = imagecolorat($gd, $x, $y);
                $alpha = ($rgba >> 24) & 0x7F;
                if ($alpha > 0) {
                    return true;
                }
            }
        }

        return false;
    }

    private function isAnimatedGif(string $contents): bool
    {
        $frames = preg_match_all('/\x00\x21\xF9\x04.{4}\x00[\x2C\x21]/s', $contents);

        return $frames !== false && $frames > 1;
    }
}
