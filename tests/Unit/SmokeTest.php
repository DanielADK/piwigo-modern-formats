<?php
use PHPUnit\Framework\TestCase;

final class SmokeTest extends TestCase
{
    public function test_bootstrap_defines_root_path(): void
    {
        $this->assertTrue(defined('PHPWG_ROOT_PATH'));
    }
}
