<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

final class ModernFormats_Config
{
    const PARAM = 'modern_formats';

    public static function defaults(): array
    {
        return [
            'quality'      => 80,
            'convert_jpeg' => true,
            'convert_png'  => true,
            'auto_convert' => true,
            'backup_mode'  => 'keep', // keep|delete
        ];
    }

    public static function sanitize(array $input): array
    {
        $d = self::defaults();
        $merged = array_merge($d, array_intersect_key($input, $d));

        $merged['quality']      = max(1, min(100, (int) $merged['quality']));
        $merged['convert_jpeg'] = (bool) $merged['convert_jpeg'];
        $merged['convert_png']  = (bool) $merged['convert_png'];
        $merged['auto_convert'] = (bool) $merged['auto_convert'];
        $merged['backup_mode']  = $merged['backup_mode'] === 'delete' ? 'delete' : 'keep';

        return $merged;
    }

    public static function from_post(array $post): array
    {
        return self::sanitize([
            'quality'      => $post['quality'] ?? 80,
            'convert_jpeg' => isset($post['convert_jpeg']),
            'convert_png'  => isset($post['convert_png']),
            'auto_convert' => isset($post['auto_convert']),
            'backup_mode'  => $post['backup_mode'] ?? 'keep',
        ]);
    }

    public static function enabled_exts(array $cfg): array
    {
        $exts = [];
        if (!empty($cfg['convert_jpeg'])) { $exts[] = 'jpg'; $exts[] = 'jpeg'; }
        if (!empty($cfg['convert_png']))  { $exts[] = 'png'; }
        return $exts;
    }

    // I/O wrappers (integration-tested; thin by design).
    public static function load(): array
    {
        return self::sanitize((array) safe_unserialize(conf_get_param(self::PARAM, [])));
    }

    public static function save(array $cfg): void
    {
        conf_update_param(self::PARAM, self::sanitize($cfg), true);
    }
}
