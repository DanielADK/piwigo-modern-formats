<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

interface ModernFormats_Encoder
{
    // Encode $src into a WebP file at $dest. Return false on a known failure.
    public function encode(string $src, string $dest, int $quality): bool;
}

// Production encoder: reuses Piwigo's pwg_image (GD/Imagick auto-selected).
final class ModernFormats_PwgImageEncoder implements ModernFormats_Encoder
{
    public function encode(string $src, string $dest, int $quality): bool
    {
        $img = new pwg_image($src);
        $img->set_compression_quality($quality);

        // Bake EXIF rotation into pixels; the DB row's rotation is then reset to
        // 0 (WebP carries no EXIF orientation, so this avoids double rotation).
        $angle = pwg_image::get_rotation_angle($src);
        if (!empty($angle)) {
            $img->rotate($angle);
        }

        $img->write($dest);
        $img->destroy();

        // write() return value is unreliable under GD; verify the output file.
        return is_file($dest) && filesize($dest) > 0;
    }
}
