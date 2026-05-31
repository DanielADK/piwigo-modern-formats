<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

require_once PHPWG_ROOT_PATH . 'admin/include/functions.php';        // delete_element_derivatives()
require_once PHPWG_ROOT_PATH . 'admin/include/functions_upload.inc.php'; // pwg_image_infos()

// Purge the old (.jpg) derivative cache, then rewrite the image row to the
// new .webp file. Order matters: the cache is keyed off the OLD path.
function modern_formats_update_image(int $image_id, string $old_rel_path, string $new_abs_path): void
{
    delete_element_derivatives(['path' => $old_rel_path]);

    $info = pwg_image_infos($new_abs_path); // width, height, filesize (kB)
    // Swap only the extension on the stored path so the original './' / dir
    // prefix style is preserved exactly (don't round-trip via absolute).
    $new_rel = preg_replace('/\.[^.\/\\\\]+$/', '.webp', $old_rel_path);

    single_update(
        IMAGES_TABLE,
        [
            'path'     => $new_rel,
            'file'     => basename($new_rel),
            'md5sum'   => md5_file($new_abs_path),
            'filesize' => $info['filesize'],
            'width'    => $info['width'],
            'height'   => $info['height'],
            'rotation' => 0, // rotation baked into pixels at encode time
        ],
        ['id' => $image_id]
    );
}

function modern_formats_pending_where(array $exts): string
{
    if (empty($exts)) {
        return '0';
    }
    $likes = array_map(
        static fn($e) => "LOWER(path) LIKE '%." . pwg_db_real_escape_string($e) . "'",
        $exts
    );
    return '(' . implode(' OR ', $likes) . ')';
}

function modern_formats_count_pending(array $exts): int
{
    $query = 'SELECT COUNT(*) AS c FROM ' . IMAGES_TABLE . ' WHERE ' . modern_formats_pending_where($exts) . ';';
    $row = pwg_db_fetch_assoc(pwg_query($query));
    return (int) $row['c'];
}

function modern_formats_pending_rows(int $start_id, int $limit, array $exts): array
{
    if (empty($exts)) {
        return [];
    }
    $where = modern_formats_pending_where($exts);
    if ($start_id > 0) {
        $where .= ' AND id < ' . $start_id;
    }
    $query = 'SELECT id, path FROM ' . IMAGES_TABLE . ' WHERE ' . $where . ' ORDER BY id DESC LIMIT ' . $limit . ';';

    $rows = [];
    $result = pwg_query($query);
    while ($row = pwg_db_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}
