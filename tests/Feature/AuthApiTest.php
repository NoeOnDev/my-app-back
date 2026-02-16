<?php

namespace Tests\Feature;

use Database\Seeders\RolesAndPermissionsSeeder;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_authenticated_user_can_get_profile_with_me_endpoint(): void
    {
        $user = User::factory()->create([
            'password' => 'Password123!',
        ]);

        $loginResponse = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ]);

        $token = $loginResponse->json('token');

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/me');

        $response->assertOk()
            ->assertJson([
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                ],
            ])
            ->assertJsonStructure([
                'user',
                'roles',
                'permissions',
            ]);
    }

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Noe',
            'email' => 'noe@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'message',
                'user' => ['id', 'name', 'email'],
                'roles',
                'permissions',
                'token',
                'token_type',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'noe@example.com',
        ]);

        $user = User::where('email', 'noe@example.com')->firstOrFail();
        $this->assertTrue($user->hasRole('usuario'));
    }

    public function test_user_can_login(): void
    {
        User::factory()->create([
            'email' => 'noe@example.com',
            'password' => 'Password123!',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'noe@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'user' => ['id', 'name', 'email'],
                'roles',
                'permissions',
                'token',
                'token_type',
            ]);
    }

    public function test_login_is_rate_limited_after_too_many_attempts(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->postJson('/api/login', [
                'email' => 'rate@example.com',
                'password' => 'WrongPassword123!',
            ])->assertStatus(422);
        }

        $this->postJson('/api/login', [
            'email' => 'rate@example.com',
            'password' => 'WrongPassword123!',
        ])->assertStatus(429)
            ->assertJson([
                'error' => 'too_many_requests',
            ])
            ->assertJsonStructure([
                'message',
                'error',
                'retry_after',
            ]);
    }

    public function test_authenticated_user_can_change_password(): void
    {
        $user = User::factory()->create([
            'password' => 'OldPassword123!',
        ]);

        $loginResponse = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'OldPassword123!',
        ]);

        $token = $loginResponse->json('token');

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/change-password', [
                'current_password' => 'OldPassword123!',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'token',
                'token_type',
            ]);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create([
            'password' => 'Password123!',
        ]);

        $loginResponse = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ]);

        $token = $loginResponse->json('token');

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/logout');

        $response->assertOk()
            ->assertJson([
                'message' => 'Sesión cerrada correctamente.',
            ]);
    }

    public function test_authenticated_user_can_refresh_token(): void
    {
        $user = User::factory()->create([
            'password' => 'Password123!',
        ]);

        $loginResponse = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ]);

        $token = $loginResponse->json('token');

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/refresh-token');

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'token',
                'token_type',
            ]);
    }

    public function test_usuario_cannot_access_admin_role_endpoint(): void
    {
        $user = User::factory()->create([
            'password' => 'Password123!',
        ]);
        $user->assignRole('usuario');

        $loginResponse = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ]);

        $token = $loginResponse->json('token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/admin/ping')
            ->assertStatus(403);
    }

    public function test_admin_can_access_admin_role_endpoint(): void
    {
        $admin = User::factory()->create([
            'password' => 'Password123!',
        ]);
        $admin->assignRole('admin');

        $loginResponse = $this->postJson('/api/login', [
            'email' => $admin->email,
            'password' => 'Password123!',
        ]);

        $token = $loginResponse->json('token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/admin/ping')
            ->assertOk()
            ->assertJson([
                'message' => 'Acceso de administrador concedido.',
            ]);
    }

    public function test_usuario_without_permission_cannot_access_admin_users_endpoint(): void
    {
        $user = User::factory()->create([
            'password' => 'Password123!',
        ]);
        $user->assignRole('usuario');

        $loginResponse = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ]);

        $token = $loginResponse->json('token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/admin/users')
            ->assertStatus(403);
    }

    public function test_admin_users_endpoint_returns_standard_paginated_structure(): void
    {
        $admin = User::factory()->create([
            'password' => 'Password123!',
        ]);
        $admin->assignRole('admin');

        User::factory()->count(5)->create();

        $token = $this->postJson('/api/login', [
            'email' => $admin->email,
            'password' => 'Password123!',
        ])->json('token');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/admin/users?per_page=2&page=1');

        $response->assertOk()
            ->assertJsonStructure([
                'items',
                'pagination' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                    'from',
                    'to',
                    'has_more_pages',
                ],
                'links' => [
                    'first',
                    'last',
                    'prev',
                    'next',
                ],
            ])
            ->assertJsonPath('pagination.current_page', 1)
            ->assertJsonPath('pagination.per_page', 2);

        $this->assertCount(2, $response->json('items'));
    }

    public function test_admin_users_endpoint_can_filter_by_search(): void
    {
        $admin = User::factory()->create([
            'password' => 'Password123!',
            'name' => 'Admin Root',
            'email' => 'admin@example.com',
        ]);
        $admin->assignRole('admin');

        User::factory()->create([
            'name' => 'Carlos Perez',
            'email' => 'carlos@example.com',
        ]);

        User::factory()->create([
            'name' => 'Lucia Mora',
            'email' => 'lucia@example.com',
        ]);

        $token = $this->postJson('/api/login', [
            'email' => 'admin@example.com',
            'password' => 'Password123!',
        ])->json('token');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/admin/users?search=carlos');

        $response->assertOk();

        $emails = collect($response->json('items'))->pluck('email')->all();

        $this->assertContains('carlos@example.com', $emails);
        $this->assertNotContains('lucia@example.com', $emails);
    }

    public function test_admin_users_endpoint_can_sort_by_name_desc(): void
    {
        $admin = User::factory()->create([
            'password' => 'Password123!',
            'name' => 'Admin Root',
            'email' => 'admin-sort@example.com',
        ]);
        $admin->assignRole('admin');

        User::factory()->create(['name' => 'Ana', 'email' => 'ana-sort@example.com']);
        User::factory()->create(['name' => 'Zoe', 'email' => 'zoe-sort@example.com']);

        $token = $this->postJson('/api/login', [
            'email' => 'admin-sort@example.com',
            'password' => 'Password123!',
        ])->json('token');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/admin/users?search=@example.com&sort_by=name&sort_dir=desc');

        $response->assertOk();

        $names = collect($response->json('items'))->pluck('name')->all();

        $this->assertSame(['Zoe', 'Ana', 'Admin Root'], $names);
    }

    public function test_admin_users_endpoint_uses_safe_default_sort_when_column_is_not_allowed(): void
    {
        $admin = User::factory()->create([
            'password' => 'Password123!',
            'name' => 'Admin Root',
            'email' => 'admin-default-sort@example.com',
        ]);
        $admin->assignRole('admin');

        $u1 = User::factory()->create(['name' => 'Luis', 'email' => 'luis-default@example.com']);
        $u2 = User::factory()->create(['name' => 'Alma', 'email' => 'alma-default@example.com']);

        $token = $this->postJson('/api/login', [
            'email' => 'admin-default-sort@example.com',
            'password' => 'Password123!',
        ])->json('token');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/admin/users?search=default@example.com&sort_by=not_allowed_column&sort_dir=desc');

        $response->assertOk();

        $ids = collect($response->json('items'))->pluck('id')->all();
        $this->assertSame([$u2->id, $u1->id], $ids);
    }

    public function test_admin_roles_endpoint_returns_standard_paginated_structure(): void
    {
        $admin = User::factory()->create([
            'password' => 'Password123!',
        ]);
        $admin->assignRole('admin');

        $token = $this->postJson('/api/login', [
            'email' => $admin->email,
            'password' => 'Password123!',
        ])->json('token');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/admin/roles?per_page=2&page=1');

        $response->assertOk()
            ->assertJsonStructure([
                'items',
                'pagination' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                    'from',
                    'to',
                    'has_more_pages',
                ],
                'links' => [
                    'first',
                    'last',
                    'prev',
                    'next',
                ],
            ])
            ->assertJsonPath('pagination.current_page', 1)
            ->assertJsonPath('pagination.per_page', 2);
    }

    public function test_admin_permissions_endpoint_can_filter_by_search(): void
    {
        $admin = User::factory()->create([
            'password' => 'Password123!',
        ]);
        $admin->assignRole('admin');

        $this->withHeader('Authorization', 'Bearer '.$this->postJson('/api/login', [
            'email' => $admin->email,
            'password' => 'Password123!',
        ])->json('token'))
            ->postJson('/api/admin/permissions', [
                'name' => 'reports.export',
            ])
            ->assertCreated();

        $token = $this->postJson('/api/login', [
            'email' => $admin->email,
            'password' => 'Password123!',
        ])->json('token');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/admin/permissions?search=reports.export');

        $response->assertOk();

        $names = collect($response->json('items'))->pluck('name')->all();
        $this->assertContains('reports.export', $names);
    }

    public function test_admin_can_assign_and_remove_role_to_user(): void
    {
        $admin = User::factory()->create([
            'password' => 'Password123!',
        ]);
        $admin->assignRole('admin');

        $targetUser = User::factory()->create();
        $targetUser->assignRole('usuario');

        $loginResponse = $this->postJson('/api/login', [
            'email' => $admin->email,
            'password' => 'Password123!',
        ]);

        $token = $loginResponse->json('token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/admin/users/'.$targetUser->id.'/assign-role', [
                'role' => 'admin',
            ])
            ->assertOk();

        $this->assertTrue($targetUser->fresh()->hasRole('admin'));

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/admin/users/'.$targetUser->id.'/remove-role', [
                'role' => 'admin',
            ])
            ->assertOk();

        $this->assertFalse($targetUser->fresh()->hasRole('admin'));
    }

    public function test_admin_can_give_and_revoke_permission_to_user(): void
    {
        $admin = User::factory()->create([
            'password' => 'Password123!',
        ]);
        $admin->assignRole('admin');

        $targetUser = User::factory()->create();
        $targetUser->assignRole('usuario');

        $loginResponse = $this->postJson('/api/login', [
            'email' => $admin->email,
            'password' => 'Password123!',
        ]);

        $token = $loginResponse->json('token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/admin/users/'.$targetUser->id.'/give-permission', [
                'permission' => 'users.read',
            ])
            ->assertOk();

        $this->assertTrue($targetUser->fresh()->hasPermissionTo('users.read'));

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/admin/users/'.$targetUser->id.'/revoke-permission', [
                'permission' => 'users.read',
            ])
            ->assertOk();

        $this->assertFalse($targetUser->fresh()->hasPermissionTo('users.read'));
    }

    public function test_admin_can_sync_roles_and_permissions_for_user(): void
    {
        $admin = User::factory()->create([
            'password' => 'Password123!',
        ]);
        $admin->assignRole('admin');

        $targetUser = User::factory()->create();
        $targetUser->assignRole('usuario');

        $loginResponse = $this->postJson('/api/login', [
            'email' => $admin->email,
            'password' => 'Password123!',
        ]);

        $token = $loginResponse->json('token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/admin/users/'.$targetUser->id.'/sync-roles', [
                'roles' => ['admin'],
            ])
            ->assertOk();

        $this->assertTrue($targetUser->fresh()->hasRole('admin'));

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/admin/users/'.$targetUser->id.'/sync-permissions', [
                'permissions' => ['users.manage'],
            ])
            ->assertOk();

        $this->assertTrue($targetUser->fresh()->hasPermissionTo('users.manage'));
    }

    public function test_usuario_cannot_manage_roles_or_permissions(): void
    {
        $user = User::factory()->create([
            'password' => 'Password123!',
        ]);
        $user->assignRole('usuario');

        $targetUser = User::factory()->create();

        $loginResponse = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ]);

        $token = $loginResponse->json('token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/admin/users/'.$targetUser->id.'/assign-role', [
                'role' => 'admin',
            ])
            ->assertStatus(403);
    }

    public function test_admin_can_create_update_and_delete_custom_role(): void
    {
        $admin = User::factory()->create([
            'password' => 'Password123!',
        ]);
        $admin->assignRole('admin');

        $token = $this->postJson('/api/login', [
            'email' => $admin->email,
            'password' => 'Password123!',
        ])->json('token');

        $createResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/admin/roles', [
                'name' => 'editor',
            ])
            ->assertCreated();

        $roleId = $createResponse->json('data.id');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/admin/roles/'.$roleId, [
                'name' => 'editor-jr',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'editor-jr');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/admin/roles/'.$roleId)
            ->assertOk();
    }

    public function test_admin_can_create_update_and_delete_custom_permission(): void
    {
        $admin = User::factory()->create([
            'password' => 'Password123!',
        ]);
        $admin->assignRole('admin');

        $token = $this->postJson('/api/login', [
            'email' => $admin->email,
            'password' => 'Password123!',
        ])->json('token');

        $createResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/admin/permissions', [
                'name' => 'posts.publish',
            ])
            ->assertCreated();

        $permissionId = $createResponse->json('data.id');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/admin/permissions/'.$permissionId, [
                'name' => 'posts.publish.own',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'posts.publish.own');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/admin/permissions/'.$permissionId)
            ->assertOk();
    }

    public function test_admin_can_sync_permissions_for_role(): void
    {
        $admin = User::factory()->create([
            'password' => 'Password123!',
        ]);
        $admin->assignRole('admin');

        $token = $this->postJson('/api/login', [
            'email' => $admin->email,
            'password' => 'Password123!',
        ])->json('token');

        $createRoleResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/admin/roles', [
                'name' => 'analyst',
            ])
            ->assertCreated();

        $roleId = $createRoleResponse->json('data.id');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/admin/roles/'.$roleId.'/sync-permissions', [
                'permissions' => ['users.read'],
            ])
            ->assertOk()
            ->assertJsonFragment([
                'name' => 'analyst',
            ]);
    }

    public function test_base_role_and_permission_cannot_be_deleted(): void
    {
        $admin = User::factory()->create([
            'password' => 'Password123!',
        ]);
        $admin->assignRole('admin');

        $token = $this->postJson('/api/login', [
            'email' => $admin->email,
            'password' => 'Password123!',
        ])->json('token');

        $rolesResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/admin/roles')
            ->assertOk();

        $adminRoleId = collect($rolesResponse->json('items'))->firstWhere('name', 'admin')['id'];

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/admin/roles/'.$adminRoleId)
            ->assertStatus(422);

        $permissionsResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/admin/permissions')
            ->assertOk();

        $usersReadPermissionId = collect($permissionsResponse->json('items'))->firstWhere('name', 'users.read')['id'];

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/admin/permissions/'.$usersReadPermissionId)
            ->assertStatus(422);
    }

    public function test_user_can_request_password_reset_link(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'noe@example.com',
        ]);

        $response = $this->postJson('/api/forgot-password', [
            'email' => 'noe@example.com',
        ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Si el correo existe, se enviaron instrucciones de recuperación.',
            ]);

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_forgot_password_is_rate_limited_after_too_many_attempts(): void
    {
        Notification::fake();

        User::factory()->create([
            'email' => 'rate-forgot@example.com',
        ]);

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $this->postJson('/api/forgot-password', [
                'email' => 'rate-forgot@example.com',
            ])->assertOk();
        }

        $this->postJson('/api/forgot-password', [
            'email' => 'rate-forgot@example.com',
        ])->assertStatus(429)
            ->assertJson([
                'error' => 'too_many_requests',
            ])
            ->assertJsonStructure([
                'message',
                'error',
                'retry_after',
            ]);
    }

    public function test_user_can_reset_password_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'reset@example.com',
            'password' => 'OldPassword123!',
        ]);

        $this->postJson('/api/forgot-password', [
            'email' => 'reset@example.com',
        ])->assertOk();

        $resetToken = null;

        Notification::assertSentTo(
            $user,
            ResetPasswordNotification::class,
            function (ResetPasswordNotification $notification) use (&$resetToken): bool {
                $resetToken = $notification->token;

                return true;
            }
        );

        $response = $this->postJson('/api/reset-password', [
            'token' => $resetToken,
            'email' => 'reset@example.com',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'token',
                'token_type',
            ]);

        $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
    }
}
