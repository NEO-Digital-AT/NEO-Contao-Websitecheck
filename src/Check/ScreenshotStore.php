<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\Check;

use Symfony\Component\Filesystem\Filesystem;

/**
 * Writes the Lighthouse screenshots (data URLs) to the check's storage folder
 * as JPEG and cuts marked excerpts ("finding crops") around DOM rectangles for
 * the accessibility page of the report. GD only — no Imagick requirement.
 */
final class ScreenshotStore
{
    public function __construct(private readonly CheckRepository $repository)
    {
    }

    /**
     * @return array{file: string, width: int, height: int, scale: float}|null  scale = image px per CSS px of the source
     */
    public function saveDataUrl(int $checkId, string $name, string $dataUrl, int $cssWidth = 0, int $maxWidth = 1600, int $maxHeight = 6000): array|null
    {
        $binary = $this->decode($dataUrl);

        if (null === $binary) {
            return null;
        }

        $img = @imagecreatefromstring($binary);

        if (false === $img) {
            return null;
        }

        $w = imagesx($img);
        $h = imagesy($img);
        $scale = $cssWidth > 0 ? $w / $cssWidth : 1.0;

        // Keep the files small enough for e-mail/PDF; very long pages are cut.
        $factor = min(1.0, $maxWidth / max(1, $w));

        if ($h * $factor > $maxHeight) {
            $img = $this->cropRaw($img, 0, 0, $w, (int) ($maxHeight / $factor));
            $h = imagesy($img);
        }

        if ($factor < 1.0) {
            $img = imagescale($img, (int) round($w * $factor), (int) round($h * $factor), IMG_BICUBIC) ?: $img;
            $scale *= $factor;
            $w = imagesx($img);
            $h = imagesy($img);
        }

        $file = $this->write($checkId, $name, $img);
        imagedestroy($img);

        return null === $file ? null : ['file' => $file, 'width' => $w, 'height' => $h, 'scale' => $scale];
    }

    /**
     * Cuts an excerpt around a DOM rectangle (CSS px) out of a stored screenshot
     * and draws a red frame around the element.
     *
     * @param array{top: int, left: int, width: int, height: int} $rect
     *
     * @return array{file: string, width: int, height: int}|null
     */
    public function cropMarked(int $checkId, string $sourceName, float $scale, array $rect, string $name, int $padding = 60): array|null
    {
        $path = $this->repository->dir($checkId).'/'.$sourceName;

        if (!is_file($path)) {
            return null;
        }

        $img = @imagecreatefromjpeg($path);

        if (false === $img) {
            return null;
        }

        $sw = imagesx($img);
        $sh = imagesy($img);

        $x = (int) floor($rect['left'] * $scale);
        $y = (int) floor($rect['top'] * $scale);
        $w = (int) ceil(max(8, $rect['width']) * $scale);
        $h = (int) ceil(max(8, $rect['height']) * $scale);

        if ($y > $sh || $x > $sw) {
            imagedestroy($img);

            return null;
        }

        $pad = (int) ($padding * $scale);
        $cx = max(0, $x - $pad);
        $cy = max(0, $y - $pad);
        $cw = min($sw - $cx, $w + 2 * $pad);
        $ch = min($sh - $cy, $h + 2 * $pad);

        // Keep a readable minimum excerpt even for tiny elements.
        $minW = min($sw, (int) (480 * $scale));

        if ($cw < $minW) {
            $cx = max(0, min($cx - (int) (($minW - $cw) / 2), $sw - $minW));
            $cw = $minW;
        }

        $crop = $this->cropRaw($img, $cx, $cy, $cw, $ch);
        imagedestroy($img);

        $red = imagecolorallocate($crop, 255, 64, 72);
        $thickness = max(2, (int) round(3 * $scale));
        // Nested 1px frames instead of imagesetthickness (ignored by some GD builds).
        for ($i = 0; $i < $thickness; ++$i) {
            imagerectangle($crop, $x - $cx - $i - 1, $y - $cy - $i - 1, $x - $cx + $w + $i, $y - $cy + $h + $i, $red);
        }

        $file = $this->write($checkId, $name, $crop);
        $cw = imagesx($crop);
        $ch = imagesy($crop);
        imagedestroy($crop);

        return null === $file ? null : ['file' => $file, 'width' => $cw, 'height' => $ch];
    }

    public function path(int $checkId, string $name): string
    {
        return $this->repository->dir($checkId).'/'.$name;
    }

    private function decode(string $dataUrl): string|null
    {
        if ('' === $dataUrl) {
            return null;
        }

        if (str_starts_with($dataUrl, 'data:')) {
            $comma = strpos($dataUrl, ',');

            if (false === $comma) {
                return null;
            }

            $dataUrl = substr($dataUrl, $comma + 1);
        }

        $binary = base64_decode($dataUrl, true);

        return false === $binary || '' === $binary ? null : $binary;
    }

    private function cropRaw(\GdImage $img, int $x, int $y, int $w, int $h): \GdImage
    {
        $crop = imagecrop($img, ['x' => $x, 'y' => $y, 'width' => max(1, $w), 'height' => max(1, $h)]);

        return false === $crop ? $img : $crop;
    }

    private function write(int $checkId, string $name, \GdImage $img): string|null
    {
        $dir = $this->repository->dir($checkId);
        (new Filesystem())->mkdir($dir);

        $file = preg_replace('/[^a-z0-9-]/', '-', strtolower($name)).'.jpg';

        return @imagejpeg($img, $dir.'/'.$file, 82) ? $file : null;
    }
}
