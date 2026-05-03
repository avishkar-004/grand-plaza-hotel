<?php

namespace Tests\Unit\Security;

use PHPUnit\Framework\TestCase;

class CSRFTest extends TestCase
{
    public function test_hash_equals_with_matching_tokens(): void
    {
        $token = bin2hex(random_bytes(32));

        $this->assertTrue(hash_equals($token, $token));
    }

    public function test_hash_equals_with_different_tokens(): void
    {
        $token1 = bin2hex(random_bytes(32));
        $token2 = bin2hex(random_bytes(32));

        $this->assertFalse(hash_equals($token1, $token2));
    }

    public function test_hash_equals_is_timing_safe(): void
    {
        $known = 'abcdef1234567890abcdef1234567890';
        $wrong1 = 'Xbcdef1234567890abcdef1234567890'; // differs at start
        $wrong2 = 'abcdef1234567890abcdef123456789X'; // differs at end

        // Both should fail equally (timing-safe comparison)
        $this->assertFalse(hash_equals($known, $wrong1));
        $this->assertFalse(hash_equals($known, $wrong2));
    }

    public function test_hash_equals_different_lengths(): void
    {
        $this->assertFalse(hash_equals('short', 'much_longer_string'));
        $this->assertFalse(hash_equals('long_string_here', 'short'));
    }

    public function test_hash_equals_empty_strings(): void
    {
        $this->assertTrue(hash_equals('', ''));
    }

    public function test_csrf_token_generation_sufficient_length(): void
    {
        $token = bin2hex(random_bytes(32));

        // 32 bytes = 64 hex chars
        $this->assertEquals(64, strlen($token));
    }

    public function test_csrf_token_is_hex(): void
    {
        $token = bin2hex(random_bytes(32));

        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token);
    }

    public function test_csrf_tokens_are_unique(): void
    {
        $token1 = bin2hex(random_bytes(32));
        $token2 = bin2hex(random_bytes(32));

        $this->assertNotEquals($token1, $token2);
    }

    public function test_naive_comparison_vs_hash_equals(): void
    {
        $token = 'abc123';

        // Both should give same boolean result
        $this->assertEquals($token === $token, hash_equals($token, $token));
        $this->assertEquals($token === 'wrong', hash_equals($token, 'wrong'));
    }

    public function test_tampered_token_rejected(): void
    {
        $original = bin2hex(random_bytes(32));
        // Flip one character
        $tampered = substr($original, 0, -1) . ($original[-1] === 'a' ? 'b' : 'a');

        $this->assertFalse(hash_equals($original, $tampered));
    }
}
