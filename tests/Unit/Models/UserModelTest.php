<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use App\Models\User;

class UserModelTest extends TestCase
{
    public function test_user_from_array(): void
    {
        $data = [
            'id' => 1,
            'username' => 'testuser',
            'email' => 'test@test.com',
            'role' => 'user',
            'full_name' => 'Test User',
            'is_active' => 1,
        ];
        $user = User::fromArray($data);
        $this->assertEquals(1, $user->id);
        $this->assertEquals('testuser', $user->username);
        $this->assertEquals('test@test.com', $user->email);
        $this->assertEquals('user', $user->role);
        $this->assertEquals('Test User', $user->full_name);
    }

    public function test_user_to_array(): void
    {
        $data = [
            'id' => 5,
            'username' => 'john',
            'email' => 'john@test.com',
            'full_name' => 'John Doe',
            'role' => 'admin',
        ];
        $user = User::fromArray($data);
        $arr = $user->toArray();

        $this->assertIsArray($arr);
        $this->assertEquals(5, $arr['id']);
        $this->assertEquals('john', $arr['username']);
        $this->assertEquals('admin', $arr['role']);
    }

    public function test_is_admin(): void
    {
        $admin = User::fromArray(['id' => 1, 'role' => 'admin']);
        $user = User::fromArray(['id' => 2, 'role' => 'user']);

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($user->isAdmin());
    }

    public function test_is_user(): void
    {
        $user = User::fromArray(['id' => 1, 'role' => 'user']);
        $admin = User::fromArray(['id' => 2, 'role' => 'admin']);

        $this->assertTrue($user->isUser());
        $this->assertFalse($admin->isUser());
    }

    public function test_is_locked_with_future_date(): void
    {
        $lockedUser = User::fromArray([
            'id' => 1,
            'locked_until' => date('Y-m-d H:i:s', strtotime('+1 hour')),
        ]);

        $this->assertTrue($lockedUser->isLocked());
    }

    public function test_is_not_locked_with_null(): void
    {
        $normalUser = User::fromArray(['id' => 2, 'locked_until' => null]);

        $this->assertFalse($normalUser->isLocked());
    }

    public function test_is_not_locked_with_past_date(): void
    {
        $expiredLock = User::fromArray([
            'id' => 3,
            'locked_until' => date('Y-m-d H:i:s', strtotime('-1 hour')),
        ]);

        $this->assertFalse($expiredLock->isLocked());
    }

    public function test_has_verified_email(): void
    {
        $verified = User::fromArray([
            'id' => 1,
            'email_verified_at' => '2026-01-01 00:00:00',
        ]);
        $unverified = User::fromArray([
            'id' => 2,
            'email_verified_at' => null,
        ]);

        $this->assertTrue($verified->hasVerifiedEmail());
        $this->assertFalse($unverified->hasVerifiedEmail());
    }

    public function test_get_display_name_with_full_name(): void
    {
        $user = User::fromArray([
            'id' => 1,
            'username' => 'testuser',
            'full_name' => 'Test User Full',
        ]);

        $this->assertEquals('Test User Full', $user->getDisplayName());
    }

    public function test_get_display_name_falls_back_to_username(): void
    {
        $user = User::fromArray([
            'id' => 1,
            'username' => 'testuser',
            'full_name' => '',
        ]);

        $this->assertEquals('testuser', $user->getDisplayName());
    }

    public function test_default_role_is_user(): void
    {
        $user = new User();

        $this->assertEquals('user', $user->role);
    }

    public function test_default_is_active(): void
    {
        $user = new User();

        $this->assertTrue($user->is_active);
    }

    public function test_default_failed_login_attempts_is_zero(): void
    {
        $user = new User();

        $this->assertEquals(0, $user->failed_login_attempts);
    }

    public function test_from_array_ignores_unknown_keys(): void
    {
        $data = [
            'id' => 1,
            'username' => 'testuser',
            'nonexistent_field' => 'should_be_ignored',
        ];
        $user = User::fromArray($data);

        $this->assertEquals(1, $user->id);
        $this->assertEquals('testuser', $user->username);
        $this->assertFalse(property_exists($user, 'nonexistent_field'));
    }
}
