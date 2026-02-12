<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AdminPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_can_manage_admin_resources(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $customer = User::factory()->create(['is_admin' => false]);

        $this->assertTrue(Gate::forUser($admin)->allows('viewAny', Product::class));
        $this->assertFalse(Gate::forUser($customer)->allows('viewAny', Product::class));
    }
}
