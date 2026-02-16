<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PaginatedIndexRequest;
use App\Models\User;
use App\Support\Queries\BaseAdminIndexQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminController extends Controller
{
    public function __construct(private readonly BaseAdminIndexQuery $baseAdminIndexQuery)
    {
    }

    public function ping(): JsonResponse
    {
        return response()->json([
            'message' => 'Acceso de administrador concedido.',
        ]);
    }

    public function users(PaginatedIndexRequest $request): JsonResponse
    {
        $query = User::query()
            ->select(['id', 'name', 'email']);

        return response()->json($this->baseAdminIndexQuery->execute(
            query: $query,
            request: $request,
            searchColumns: ['name', 'email'],
            allowedSortColumns: ['id', 'name', 'email'],
            defaultSortColumn: 'id',
            mapper: fn (User $user): array => $this->mapUserAuthorizationData($user)
        ));
    }

    public function roles(PaginatedIndexRequest $request): JsonResponse
    {
        $query = Role::query()
            ->with('permissions:id,name');

        return response()->json($this->baseAdminIndexQuery->execute(
            query: $query,
            request: $request,
            searchColumns: ['name'],
            allowedSortColumns: ['id', 'name'],
            defaultSortColumn: 'name',
            mapper: fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name')->values(),
            ]
        ));
    }

    public function permissions(PaginatedIndexRequest $request): JsonResponse
    {
        $query = Permission::query();

        return response()->json($this->baseAdminIndexQuery->execute(
            query: $query,
            request: $request,
            searchColumns: ['name'],
            allowedSortColumns: ['id', 'name'],
            defaultSortColumn: 'name',
            mapper: fn (Permission $permission): array => [
                'id' => $permission->id,
                'name' => $permission->name,
            ]
        ));
    }

    public function createRole(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        return response()->json([
            'message' => 'Rol creado correctamente.',
            'data' => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => [],
            ],
        ], 201);
    }

    public function updateRole(Request $request, Role $role): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($role->id)],
        ]);

        $role->update([
            'name' => $validated['name'],
        ]);

        return response()->json([
            'message' => 'Rol actualizado correctamente.',
            'data' => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions()->pluck('name')->values(),
            ],
        ]);
    }

    public function deleteRole(Role $role): JsonResponse
    {
        if (in_array($role->name, ['admin', 'usuario'], true)) {
            return response()->json([
                'message' => 'No se puede eliminar un rol base del sistema.',
            ], 422);
        }

        $role->delete();

        return response()->json([
            'message' => 'Rol eliminado correctamente.',
        ]);
    }

    public function syncRolePermissions(Request $request, Role $role): JsonResponse
    {
        $validated = $request->validate([
            'permissions' => ['required', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role->syncPermissions($validated['permissions']);

        return response()->json([
            'message' => 'Permisos del rol sincronizados correctamente.',
            'data' => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions()->pluck('name')->values(),
            ],
        ]);
    }

    public function createPermission(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:permissions,name'],
        ]);

        $permission = Permission::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        return response()->json([
            'message' => 'Permiso creado correctamente.',
            'data' => [
                'id' => $permission->id,
                'name' => $permission->name,
            ],
        ], 201);
    }

    public function updatePermission(Request $request, Permission $permission): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('permissions', 'name')->ignore($permission->id)],
        ]);

        $permission->update([
            'name' => $validated['name'],
        ]);

        return response()->json([
            'message' => 'Permiso actualizado correctamente.',
            'data' => [
                'id' => $permission->id,
                'name' => $permission->name,
            ],
        ]);
    }

    public function deletePermission(Permission $permission): JsonResponse
    {
        if (in_array($permission->name, ['profile.read', 'users.read', 'users.manage'], true)) {
            return response()->json([
                'message' => 'No se puede eliminar un permiso base del sistema.',
            ], 422);
        }

        $permission->delete();

        return response()->json([
            'message' => 'Permiso eliminado correctamente.',
        ]);
    }

    public function assignRole(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'string', 'exists:roles,name'],
        ]);

        $user->assignRole($validated['role']);

        return response()->json([
            'message' => 'Rol asignado correctamente.',
            'data' => $this->mapUserAuthorizationData($user->fresh()),
        ]);
    }

    public function removeRole(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'string', 'exists:roles,name'],
        ]);

        $user->removeRole($validated['role']);

        return response()->json([
            'message' => 'Rol removido correctamente.',
            'data' => $this->mapUserAuthorizationData($user->fresh()),
        ]);
    }

    public function syncRoles(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'roles' => ['required', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ]);

        $user->syncRoles($validated['roles']);

        return response()->json([
            'message' => 'Roles sincronizados correctamente.',
            'data' => $this->mapUserAuthorizationData($user->fresh()),
        ]);
    }

    public function givePermission(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'permission' => ['required', 'string', 'exists:permissions,name'],
        ]);

        $user->givePermissionTo($validated['permission']);

        return response()->json([
            'message' => 'Permiso asignado correctamente.',
            'data' => $this->mapUserAuthorizationData($user->fresh()),
        ]);
    }

    public function revokePermission(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'permission' => ['required', 'string', 'exists:permissions,name'],
        ]);

        $user->revokePermissionTo($validated['permission']);

        return response()->json([
            'message' => 'Permiso removido correctamente.',
            'data' => $this->mapUserAuthorizationData($user->fresh()),
        ]);
    }

    public function syncPermissions(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'permissions' => ['required', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $user->syncPermissions($validated['permissions']);

        return response()->json([
            'message' => 'Permisos sincronizados correctamente.',
            'data' => $this->mapUserAuthorizationData($user->fresh()),
        ]);
    }

    private function mapUserAuthorizationData(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->getRoleNames()->values(),
            'permissions' => $user->getAllPermissions()->pluck('name')->values(),
        ];
    }
}
