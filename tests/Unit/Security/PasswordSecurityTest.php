<?php

namespace Tests\Unit\Security;

use PHPUnit\Framework\TestCase;

class PasswordSecurityTest extends TestCase
{
    public function test_bcrypt_hash_is_secure(): void
    {
        $password = 'TestPassword123';
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        $this->assertStringStartsWith('$2y$12$', $hash);
        $this->assertTrue(password_verify($password, $hash));
    }

    public function test_default_bcrypt_cost(): void
    {
        $password = 'TestPassword123';
        $hash = password_hash($password, PASSWORD_BCRYPT);

        // Default cost is 10
        $this->assertStringStartsWith('$2y$', $hash);
        $this->assertTrue(password_verify($password, $hash));
    }

    public function test_wrong_password_fails(): void
    {
        $hash = password_hash('correct', PASSWORD_BCRYPT, ['cost' => 12]);

        $this->assertFalse(password_verify('wrong', $hash));
        $this->assertFalse(password_verify('', $hash));
        $this->assertFalse(password_verify('Correct', $hash)); // case sensitive
    }

    public function test_same_password_different_hashes(): void
    {
        $password = 'SamePassword123';
        $hash1 = password_hash($password, PASSWORD_BCRYPT);
        $hash2 = password_hash($password, PASSWORD_BCRYPT);

        // Salt makes each hash unique
        $this->assertNotEquals($hash1, $hash2);
        // Both still verify correctly
        $this->assertTrue(password_verify($password, $hash1));
        $this->assertTrue(password_verify($password, $hash2));
    }

    public function test_password_policy_strong(): void
    {
        $good = 'Password1';
        $this->assertTrue(strlen($good) >= 8);
        $this->assertTrue((bool) preg_match('/[A-Z]/', $good));
        $this->assertTrue((bool) preg_match('/[a-z]/', $good));
        $this->assertTrue((bool) preg_match('/[0-9]/', $good));
    }

    public function test_password_policy_too_short(): void
    {
        $weak = 'pass';
        $this->assertFalse(strlen($weak) >= 8);
    }

    public function test_password_policy_no_uppercase(): void
    {
        $noUpper = 'password123';
        $this->assertFalse((bool) preg_match('/[A-Z]/', $noUpper));
    }

    public function test_password_policy_no_lowercase(): void
    {
        $noLower = 'PASSWORD123';
        $this->assertFalse((bool) preg_match('/[a-z]/', $noLower));
    }

    public function test_password_policy_no_digit(): void
    {
        $noDigit = 'PasswordOnly';
        $this->assertFalse((bool) preg_match('/[0-9]/', $noDigit));
    }

    public function test_hash_length(): void
    {
        $hash = password_hash('test', PASSWORD_BCRYPT);
        // bcrypt hashes are always 60 characters
        $this->assertEquals(60, strlen($hash));
    }

    public function test_plaintext_password_not_stored(): void
    {
        $password = 'SecretPassword123';
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $this->assertStringNotContainsString($password, $hash);
    }
}
