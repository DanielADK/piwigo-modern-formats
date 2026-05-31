<?php
use PHPUnit\Framework\TestCase;

final class ConverterPathTest extends TestCase
{
    private function make(array $cfg = []): ModernFormats_Converter
    {
        $encoder = new class implements ModernFormats_Encoder {
            public function encode(string $src, string $dest, int $quality): bool { return true; }
        };
        return new ModernFormats_Converter($encoder, ModernFormats_Config::sanitize($cfg), '/tmp/backup');
    }

    public function test_is_supported_source_respects_config(): void
    {
        $c = $this->make();
        $this->assertTrue($c->is_supported_source('/x/a.jpg'));
        $this->assertTrue($c->is_supported_source('/x/a.JPG'));
        $this->assertTrue($c->is_supported_source('/x/a.jpeg'));
        $this->assertTrue($c->is_supported_source('/x/a.png'));
        $this->assertFalse($c->is_supported_source('/x/a.webp'));
        $this->assertFalse($c->is_supported_source('/x/a.gif'));

        $noPng = $this->make(['convert_png' => false]);
        $this->assertFalse($noPng->is_supported_source('/x/a.png'));
    }

    public function test_webp_path_swaps_extension(): void
    {
        $c = $this->make();
        $this->assertSame('/x/y/a.webp', $c->webp_path('/x/y/a.jpg'));
        $this->assertSame('/x/y/a.webp', $c->webp_path('/x/y/a.JPEG'));
        $this->assertSame('/x/y.dir/a.webp', $c->webp_path('/x/y.dir/a.png'));
    }

    public function test_backup_path_joins_basename(): void
    {
        $c = $this->make();
        $this->assertSame('/tmp/backup/a.jpg', $c->backup_path('/x/y/a.jpg'));
    }
}
