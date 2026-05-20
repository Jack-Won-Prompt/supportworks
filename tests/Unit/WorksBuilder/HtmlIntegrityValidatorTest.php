<?php

namespace Tests\Unit\WorksBuilder;

use App\Models\WorksBuilder\GeneratedHtml;
use App\Models\WorksBuilder\ReviewSession;
use App\Services\WorksBuilder\Review\HtmlIntegrityValidator;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class HtmlIntegrityValidatorTest extends TestCase
{
    private HtmlIntegrityValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new HtmlIntegrityValidator();
    }

    public function test_hash_returns_sha256_hex(): void
    {
        $hash = $this->validator->hash('hello');

        $this->assertSame(64, strlen($hash));
        $this->assertSame('2cf24dba5fb0a30e26e83b2ac5b9e29e1b161e5c1fa7425e73043362938b9824', $hash);
    }

    public function test_hash_is_deterministic_for_same_input(): void
    {
        $html = '<!DOCTYPE html><html><body><p>x</p></body></html>';
        $this->assertSame($this->validator->hash($html), $this->validator->hash($html));
    }

    public function test_hash_differs_for_whitespace_change(): void
    {
        $a = '<div>x</div>';
        $b = '<div>x </div>';
        $this->assertNotSame($this->validator->hash($a), $this->validator->hash($b));
    }

    public function test_verify_passes_when_stored_hash_matches_content(): void
    {
        $html = new GeneratedHtml();
        $html->html_content = '<p>ok</p>';
        $html->html_hash    = $this->validator->hash('<p>ok</p>');

        $this->assertSame($html->html_hash, $this->validator->verify($html));
    }

    public function test_verify_throws_when_content_mutated(): void
    {
        $html = new GeneratedHtml();
        $html->html_content = '<p>ok</p>';
        $html->html_hash    = $this->validator->hash('<p>original</p>'); // 다른 원본의 hash

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('HTML integrity check failed');
        $this->validator->verify($html);
    }

    public function test_verify_session_returns_true_only_when_hashes_equal(): void
    {
        $hash = $this->validator->hash('<div>same</div>');

        $session = new ReviewSession();
        $session->start_hash = $hash;
        $session->end_hash   = $hash;

        $this->assertTrue($this->validator->verifySession($session));
    }

    public function test_verify_session_returns_false_when_end_hash_null(): void
    {
        $session = new ReviewSession();
        $session->start_hash = $this->validator->hash('<x/>');
        $session->end_hash   = null;

        $this->assertFalse($this->validator->verifySession($session));
    }

    public function test_verify_session_returns_false_when_hashes_differ(): void
    {
        $session = new ReviewSession();
        $session->start_hash = $this->validator->hash('<a/>');
        $session->end_hash   = $this->validator->hash('<b/>');

        $this->assertFalse($this->validator->verifySession($session));
    }
}
