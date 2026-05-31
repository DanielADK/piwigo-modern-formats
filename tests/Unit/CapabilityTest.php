<?php
use PHPUnit\Framework\TestCase;

final class CapabilityTest extends TestCase
{
    public function test_detect_returns_well_formed_contract(): void
    {
        $cap = ModernFormats_Capability::detect();
        $this->assertIsBool($cap['ok']);
        $this->assertArrayHasKey('library', $cap);
        $this->assertIsString($cap['reason']);
        if ($cap['ok']) {
            $this->assertContains($cap['library'], ['gd', 'imagick']);
            $this->assertSame('', $cap['reason']);
        } else {
            $this->assertNull($cap['library']);
            $this->assertNotSame('', $cap['reason']);
        }
    }

    public function test_detect_true_when_gd_has_webp(): void
    {
        if (!function_exists('gd_info') || empty(gd_info()['WebP Support'])) {
            $this->markTestSkipped('GD WebP not available on this runner.');
        }
        $this->assertTrue(ModernFormats_Capability::detect()['ok']);
    }
}
