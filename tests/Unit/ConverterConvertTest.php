<?php
use PHPUnit\Framework\TestCase;

// Test doubles: write controllable bytes / simulate failures.
final class WritingEncoder implements ModernFormats_Encoder
{
    public function __construct(private string $bytes = 'RIFF....WEBP') {}
    public function encode(string $src, string $dest, int $quality): bool
    {
        file_put_contents($dest, $this->bytes);
        return true;
    }
}
final class FailEncoder implements ModernFormats_Encoder
{
    public function encode(string $src, string $dest, int $quality): bool { return false; }
}
final class LyingEncoder implements ModernFormats_Encoder
{
    // Claims success but writes nothing (must be treated as an error).
    public function encode(string $src, string $dest, int $quality): bool { return true; }
}

final class ConverterConvertTest extends TestCase
{
    private string $work;
    private string $backup;

    protected function setUp(): void
    {
        $this->work   = sys_get_temp_dir() . '/mf_' . uniqid();
        $this->backup = $this->work . '/backup';
        mkdir($this->work, 0777, true);
        mkdir($this->backup, 0777, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->backup . '/*') ?: [] as $f) { if (is_file($f)) unlink($f); }
        foreach (glob($this->work . '/*') ?: [] as $f) { if (is_file($f)) unlink($f); }
        @rmdir($this->backup);
        @rmdir($this->work);
    }

    private function srcJpeg(): string
    {
        $src = $this->work . '/photo.jpg';
        file_put_contents($src, 'fake-jpeg-bytes');
        return $src;
    }

    private function converter(ModernFormats_Encoder $enc, array $cfg = []): ModernFormats_Converter
    {
        return new ModernFormats_Converter($enc, ModernFormats_Config::sanitize($cfg), $this->backup);
    }

    public function test_skips_unsupported_source(): void
    {
        $gif = $this->work . '/a.gif';
        file_put_contents($gif, 'x');
        $r = $this->converter(new WritingEncoder())->convert($gif);
        $this->assertSame(ModernFormats_Result::SKIPPED, $r->status);
        $this->assertFileExists($gif);
    }

    public function test_error_when_encoder_fails(): void
    {
        $src = $this->srcJpeg();
        $r = $this->converter(new FailEncoder())->convert($src);
        $this->assertSame(ModernFormats_Result::ERROR, $r->status);
        $this->assertFileExists($src); // original untouched on failure
    }

    public function test_error_when_output_missing_despite_true(): void
    {
        $src = $this->srcJpeg();
        $r = $this->converter(new LyingEncoder())->convert($src);
        $this->assertSame(ModernFormats_Result::ERROR, $r->status);
        $this->assertFileExists($src);
    }

    public function test_converted_keeps_backup(): void
    {
        $src = $this->srcJpeg();
        $r = $this->converter(new WritingEncoder(), ['backup_mode' => 'keep'])->convert($src);
        $this->assertSame(ModernFormats_Result::CONVERTED, $r->status);
        $this->assertSame($this->work . '/photo.webp', $r->dest);
        $this->assertFileExists($r->dest);
        $this->assertFileDoesNotExist($src);                 // original moved away
        $this->assertSame($this->backup . '/photo.jpg', $r->backup);
        $this->assertFileExists($r->backup);
    }

    public function test_converted_deletes_when_mode_delete(): void
    {
        $src = $this->srcJpeg();
        $r = $this->converter(new WritingEncoder(), ['backup_mode' => 'delete'])->convert($src);
        $this->assertSame(ModernFormats_Result::CONVERTED, $r->status);
        $this->assertNull($r->backup);
        $this->assertFileDoesNotExist($src);
        $this->assertFileDoesNotExist($this->backup . '/photo.jpg');
    }

    public function test_backup_collision_gets_unique_name(): void
    {
        file_put_contents($this->backup . '/photo.jpg', 'pre-existing');
        $src = $this->srcJpeg();
        $r = $this->converter(new WritingEncoder(), ['backup_mode' => 'keep'])->convert($src);
        $this->assertSame(ModernFormats_Result::CONVERTED, $r->status);
        $this->assertSame($this->backup . '/photo-1.jpg', $r->backup);
        $this->assertFileExists($this->backup . '/photo-1.jpg');
    }

    public function test_skips_missing_file(): void
    {
        $r = $this->converter(new WritingEncoder())->convert($this->work . '/nope.jpg');
        $this->assertSame(ModernFormats_Result::SKIPPED, $r->status);
    }

    public function test_error_when_output_is_empty_file(): void
    {
        $src = $this->srcJpeg();
        $r = $this->converter(new WritingEncoder(''))->convert($src); // encoder writes a 0-byte file
        $this->assertSame(ModernFormats_Result::ERROR, $r->status);
        $this->assertFileExists($src);                              // original intact
        $this->assertFileDoesNotExist($this->work . '/photo.webp'); // partial output cleaned up
    }

    public function test_rename_failure_keeps_original_and_nulls_backup(): void
    {
        $src = $this->srcJpeg();
        $bad = $this->work . '/not-a-dir';
        file_put_contents($bad, 'x'); // a file, so rename into it must fail
        $conv = new ModernFormats_Converter(
            new WritingEncoder(),
            ModernFormats_Config::sanitize(['backup_mode' => 'keep']),
            $bad
        );
        $r = $conv->convert($src);
        $this->assertSame(ModernFormats_Result::CONVERTED, $r->status);
        $this->assertNull($r->backup);                          // backup failed -> null
        $this->assertFileExists($src);                          // original NOT lost when backup fails
        $this->assertFileExists($this->work . '/photo.webp');
    }
}
