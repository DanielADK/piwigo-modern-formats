<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

require_once __DIR__ . '/include/config.class.php';

class ModernFormats_Maintain extends PluginMaintain
{
    function install($plugin_version, &$errors = [])
    {
        global $conf;
        if (empty($conf[ModernFormats_Config::PARAM])) {
            conf_update_param(ModernFormats_Config::PARAM, ModernFormats_Config::defaults(), true);
        }
        $this->ensure_backup_dir();
    }

    function activate($plugin_version, &$errors = [])
    {
        $this->ensure_backup_dir();
    }

    function update($old_version, $new_version, &$errors = [])
    {
        // Re-sanitize so new config keys get their defaults across upgrades.
        $current = safe_unserialize(conf_get_param(ModernFormats_Config::PARAM, []));
        conf_update_param(ModernFormats_Config::PARAM, ModernFormats_Config::sanitize((array) $current), true);
    }

    function uninstall()
    {
        // Remove config only. Backups under _data are intentionally kept so
        // originals are never destroyed by uninstalling.
        conf_delete_param(ModernFormats_Config::PARAM);
    }

    private function ensure_backup_dir(): void
    {
        global $conf;
        $dir = $conf['data_location'] . 'modern_formats_backup/';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        // Deny web access to the backup originals.
        $htaccess = $dir . '.htaccess';
        if (is_dir($dir) && !file_exists($htaccess)) {
            @file_put_contents($htaccess, "Require all denied\nDeny from all\n");
        }
    }
}

$mf_maintain_class = str_replace('-', '_', basename(__DIR__)) . '_maintain';
if (!class_exists($mf_maintain_class, false)) {
    class_alias('ModernFormats_Maintain', $mf_maintain_class);
}