<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

require_once __DIR__ . '/classes.inc.php';
require_once __DIR__ . '/db.inc.php';

function modern_formats_log(string $message): void
{
    global $logger;
    if (isset($logger) && method_exists($logger, 'error')) {
        $logger->error('[modern_formats] ' . $message);
    }
}

// Hooked on loc_end_add_uploaded_file. Must never throw — a failure here must
// not break the upload.
function modern_formats_on_upload($image_infos): void
{
    try {
        $cfg = ModernFormats_Config::load();
        $cap = ModernFormats_Capability::detect();
        if (empty($cfg['auto_convert']) || !$cap['ok']) {
            return;
        }

        $old_rel = $image_infos['path'];
        $src_abs  = PHPWG_ROOT_PATH . preg_replace('#^\./#', '', $old_rel);

        $converter = new ModernFormats_Converter(
            new ModernFormats_PwgImageEncoder($cap['library']),
            $cfg,
            MODERN_FORMATS_BACKUP_DIR
        );
        if (!$converter->is_supported_source($src_abs)) {
            return;
        }

        $result = $converter->convert($src_abs);
        if ($result->ok()) {
            modern_formats_update_image((int) $image_infos['id'], $old_rel, $result->dest);
        } elseif ($result->status === ModernFormats_Result::ERROR) {
            modern_formats_log($result->error ?? 'unknown error');
        }
    } catch (\Throwable $e) {
        modern_formats_log('on_upload exception: ' . $e->getMessage());
    }
}
