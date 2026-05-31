<?php
use PHPUnit\Framework\TestCase;

final class ResultTest extends TestCase
{
    public function test_converted_is_ok(): void
    {
        $r = new ModernFormats_Result(ModernFormats_Result::CONVERTED, dest: '/x/a.webp', backup: '/b/a.jpg');
        $this->assertTrue($r->ok());
        $this->assertSame('/x/a.webp', $r->dest);
        $this->assertSame('/b/a.jpg', $r->backup);
    }

    public function test_skipped_and_error_are_not_ok(): void
    {
        $this->assertFalse((new ModernFormats_Result(ModernFormats_Result::SKIPPED))->ok());
        $this->assertFalse((new ModernFormats_Result(ModernFormats_Result::ERROR, error: 'boom'))->ok());
    }
}
