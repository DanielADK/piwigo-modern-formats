<?php
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    public function test_defaults(): void
    {
        $d = ModernFormats_Config::defaults();
        $this->assertSame(80, $d['quality']);
        $this->assertTrue($d['convert_jpeg']);
        $this->assertTrue($d['convert_png']);
        $this->assertTrue($d['auto_convert']);
        $this->assertSame('keep', $d['backup_mode']);
    }

    public function test_sanitize_clamps_quality_and_casts_types(): void
    {
        $c = ModernFormats_Config::sanitize(['quality' => '0', 'convert_png' => 0]);
        $this->assertSame(1, $c['quality']);                 // clamped up to 1
        $this->assertFalse($c['convert_png']);
        $this->assertSame(100, ModernFormats_Config::sanitize(['quality' => 999])['quality']); // clamped down to 100
        $this->assertSame(85, ModernFormats_Config::sanitize(['quality' => '85'])['quality']); // numeric string cast
    }

    public function test_sanitize_rejects_unknown_backup_mode_and_keys(): void
    {
        $c = ModernFormats_Config::sanitize(['backup_mode' => 'nuke', 'bogus' => 1]);
        $this->assertSame('keep', $c['backup_mode']);
        $this->assertArrayNotHasKey('bogus', $c);
    }

    public function test_sanitize_accepts_delete_mode(): void
    {
        $this->assertSame('delete', ModernFormats_Config::sanitize(['backup_mode' => 'delete'])['backup_mode']);
    }

    public function test_from_post_reads_checkboxes_by_presence(): void
    {
        $c = ModernFormats_Config::from_post(['quality' => '70', 'convert_jpeg' => 'on', 'backup_mode' => 'delete']);
        $this->assertSame(70, $c['quality']);
        $this->assertTrue($c['convert_jpeg']);
        $this->assertFalse($c['convert_png']);   // absent checkbox => false
        $this->assertFalse($c['auto_convert']);  // absent checkbox => false
        $this->assertSame('delete', $c['backup_mode']);
    }

    public function test_enabled_exts(): void
    {
        $this->assertSame(['jpg', 'jpeg', 'png'], ModernFormats_Config::enabled_exts(ModernFormats_Config::defaults()));
        $this->assertSame(['png'], ModernFormats_Config::enabled_exts(['convert_jpeg' => false, 'convert_png' => true]));
        $this->assertSame([], ModernFormats_Config::enabled_exts(['convert_jpeg' => false, 'convert_png' => false]));
    }
}
