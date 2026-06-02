<?php
/*
Plugin Name: Modern Formats
Version: 1.1.1
Description: Automatically converts uploaded JPEG/PNG photos to WebP (configurable quality) and bulk-converts existing photos.
Plugin URI: https://github.com/DanielADK/piwigo-modern-formats
Author: Daniel Adámek
Author URI: https://github.com/DanielADK
*/

if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

global $conf;

define('MODERN_FORMATS_ID', basename(dirname(__FILE__)));
define('MODERN_FORMATS_PATH', PHPWG_PLUGINS_PATH . MODERN_FORMATS_ID . '/');
define('MODERN_FORMATS_URL', get_root_url() . 'plugins/' . MODERN_FORMATS_ID . '/');
define('MODERN_FORMATS_BACKUP_DIR', $conf['data_location'] . 'modern_formats_backup/');

// Convert each newly uploaded photo (file lazy-loaded only when the event fires).
add_event_handler('loc_end_add_uploaded_file', 'modern_formats_on_upload',
    EVENT_HANDLER_PRIORITY_NEUTRAL, MODERN_FORMATS_PATH . 'include/events.inc.php');

// Register bulk-conversion web service methods.
add_event_handler('ws_add_methods', 'modern_formats_add_ws_methods',
    EVENT_HANDLER_PRIORITY_NEUTRAL, MODERN_FORMATS_PATH . 'include/ws.inc.php');

// Settings link (folder-name-agnostic; the "Has Settings" header's alias breaks on hyphens).
add_event_handler('get_admin_plugin_menu_links', 'modern_formats_admin_menu');
function modern_formats_admin_menu($menu)
{
    $menu[] = [
        'NAME' => 'Modern Formats',
        'URL'  => get_root_url() . 'admin.php?page=plugin&section=' . MODERN_FORMATS_ID . '/admin.php',
    ];
    return $menu;
}
