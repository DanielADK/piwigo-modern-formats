<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

require_once __DIR__ . '/classes.inc.php';
require_once __DIR__ . '/db.inc.php';

// Hooked on ws_add_methods; the service is passed by reference as $arr[0].
function modern_formats_add_ws_methods($arr): void
{
    $service = &$arr[0];

    $service->addMethod(
        'pwg.modernFormats.getPending',
        'ws_modern_formats_get_pending',
        [],
        'Counts existing photos still pending WebP conversion.',
        '',
        ['admin_only' => true]
    );

    $service->addMethod(
        'pwg.modernFormats.convert',
        'ws_modern_formats_convert',
        [
            'start_id'  => ['type' => WS_TYPE_INT | WS_TYPE_POSITIVE, 'default' => 0],
            'limit'     => ['type' => WS_TYPE_INT | WS_TYPE_POSITIVE, 'default' => 50, 'maxValue' => 200],
            'pwg_token' => [],
        ],
        'Converts a chunk of existing photos to WebP and returns a cursor.',
        '',
        ['admin_only' => true, 'post_only' => true]
    );
}

function ws_modern_formats_get_pending($params, &$service)
{
    $cfg = ModernFormats_Config::load();
    return ['pending' => modern_formats_count_pending(ModernFormats_Config::enabled_exts($cfg))];
}

function ws_modern_formats_convert($params, &$service)
{
    if (get_pwg_token() != $params['pwg_token']) {
        return new PwgError(403, 'Invalid security token');
    }

    $cfg = ModernFormats_Config::load();
    $cap = ModernFormats_Capability::detect();
    if (!$cap['ok']) {
        return new PwgError(500, $cap['reason']);
    }

    $exts  = ModernFormats_Config::enabled_exts($cfg);
    $limit = (int) $params['limit'];
    $rows  = modern_formats_pending_rows((int) $params['start_id'], $limit, $exts);

    $encoder = new ModernFormats_PwgImageEncoder($cap['library']);
    $converter = new ModernFormats_Converter($encoder, $cfg, MODERN_FORMATS_BACKUP_DIR);

    $converted = 0;
    $errors = [];
    $next_id = null;
    foreach ($rows as $row) {
        // Guard each photo: a single bad file must not abort the whole batch.
        // $next_id still advances so the cursor makes progress past it.
        try {
            $src_abs = PHPWG_ROOT_PATH . preg_replace('#^\./#', '', $row['path']);
            $result = $converter->convert($src_abs);
            if ($result->ok()) {
                modern_formats_update_image((int) $row['id'], $row['path'], $result->dest);
                $converted++;
            } elseif ($result->status === ModernFormats_Result::ERROR) {
                $errors[] = (int) $row['id'];
                modern_formats_log('bulk: could not convert image ' . $row['id'] . ' (' . $row['path'] . ') — ' . ($encoder->lastError ?? 'unreadable or unsupported image'));
            }
        } catch (\Throwable $e) {
            $errors[] = (int) $row['id'];
            modern_formats_log('bulk: error on image ' . $row['id'] . ' (' . $row['path'] . ') — ' . $e->getMessage());
        }
        $next_id = (int) $row['id'];
    }

    return [
        'processed' => count($rows),
        'converted' => $converted,
        'errors'    => $errors,
        'next_id'   => count($rows) < $limit ? null : $next_id, // null => done
        'remaining' => modern_formats_count_pending($exts),
    ];
}
