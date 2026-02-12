<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_login_and_fetch_me(): void
    {
        $register = $this->postJson('/api/register', [
            'name' => 'API User',
            'email' => 'api-user@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $register->assertCreated()
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email'],
                'token',
            ]);

        $login = $this->postJson('/api/login', [
            'email' => 'api-user@example.com',
            'password' => 'password123',
        ]);

        $login->assertOk()->assertJsonStructure(['user', 'token']);

        $token = $login->json('token');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('user.email', 'api-user@example.com');
    }
}
