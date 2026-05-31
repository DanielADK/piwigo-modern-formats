<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

final class ModernFormats_Converter
{
    public function __construct(
        private ModernFormats_Encoder $encoder,
        private array $config,
        private string $backup_dir,
    ) {}

    public function is_supported_source(string $path): bool
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return in_array($ext, ModernFormats_Config::enabled_exts($this->config), true);
    }

    public function webp_path(string $src): string
    {
        return preg_replace('/\.[^.\/\\\\]+$/', '.webp', $src);
    }

    public function backup_path(string $src): string
    {
        return rtrim($this->backup_dir, '/') . '/' . basename($src);
    }
}
