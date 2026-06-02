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

    public function convert(string $src): ModernFormats_Result
    {
        if (!is_file($src) || !$this->is_supported_source($src)) {
            return new ModernFormats_Result(ModernFormats_Result::SKIPPED);
        }

        $dest = $this->webp_path($src);
        try {
            $ok = $this->encoder->encode($src, $dest, (int) $this->config['quality']);
        } catch (\Throwable $e) {
            $ok = false;
        }
        if (!$ok || !is_file($dest) || filesize($dest) === 0) {
            if (is_file($dest)) {
                @unlink($dest);
            }
            return new ModernFormats_Result(ModernFormats_Result::ERROR, error: 'encode failed: ' . $src);
        }

        $backup = null;
        if (($this->config['backup_mode'] ?? 'keep') === 'keep') {
            $backup = $this->unique_backup_path($src);
            if (!@rename($src, $backup)) {
                $backup = null;
            }
        } else {
            @unlink($src);
        }

        return new ModernFormats_Result(ModernFormats_Result::CONVERTED, dest: $dest, backup: $backup);
    }

    private function unique_backup_path(string $src): string
    {
        $base = $this->backup_path($src);
        if (!file_exists($base)) {
            return $base;
        }
        $dir  = dirname($base);
        $name = pathinfo($base, PATHINFO_FILENAME);
        $ext  = pathinfo($base, PATHINFO_EXTENSION);
        $i = 1;
        do {
            $candidate = "$dir/$name-$i.$ext";
            $i++;
        } while (file_exists($candidate));
        return $candidate;
    }
}
