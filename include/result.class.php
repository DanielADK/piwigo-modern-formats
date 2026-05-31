<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

final class ModernFormats_Result
{
    const CONVERTED = 'converted';
    const SKIPPED   = 'skipped';
    const ERROR     = 'error';

    public function __construct(
        public readonly string $status,
        public readonly ?string $dest = null,
        public readonly ?string $backup = null,
        public readonly ?string $error = null,
    ) {}

    public function ok(): bool
    {
        return $this->status === self::CONVERTED;
    }
}
