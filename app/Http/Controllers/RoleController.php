<?php

namespace App\Http\Controllers;

use App\DataTables\RoleDataTableConfig;
use App\Http\Requests\RoleRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class RoleController extends Controller
{
    public function index(): View
    {
        $config = new RoleDataTableConfig;

        $permissions = Permission::orderBy('name')->get()->groupBy(
            fn ($p) => explode('.', $p->name)[0]
        )->sortKeys();

        $permissionLabels = [
            'index' => 'Listar',
            'show' => 'Ver',
            'create' => 'Crear',
            'edit' => 'Editar',
            'delete' => 'Eliminar',
        ];

        return view('roles.index', compact('config', 'permissions', 'permissionLabels'));
    }

    public function getAjax(Request $request): JsonResponse
    {
        $query = Role::with('permissions')->select(['id', 'name', 'guard_name', 'created_at']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('permissions', function ($row) {
                $permissions = $row->permissions->pluck('name')->toArray();
                if (empty($permissions)) {
                    return '-';
                }
                if (count($permissions) > 3) {
                    $shown = array_slice($permissions, 0, 3);
                    $remaining = count($permissions) - 3;

                    return implode(', ', $shown)." <span class='text-muted'>+{$remaining} más</span>";
                }

                return implode(', ', $permissions);
            })
            ->addColumn('action', function ($row) {
                $editUrl = route('roles.edit', $row->id);
                $btn = '<div class="d-flex gap-1 justify-content-center">';
                $btn .= '<button type="button" class="btn btn-sm btn-success btn-edit-role" '
                    .'data-url="'.$editUrl.'" title="'.__('botones.Editar').'">'
                    .'<i class="fa fa-edit"></i></button>';
                $btn .= '<button type="button" class="btn btn-danger btn-sm delete-button" '
                    .'data-id="'.$row->id.'" title="'.__('botones.Eliminar').'">'
                    .'<i class="fa fa-trash"></i></button>';
                $btn .= '</div>';

                return $btn;
            })
            ->rawColumns(['permissions', 'action'])
            ->make(true);
    }

    public function create(): RedirectResponse
    {
        return Redirect::route('roles.index');
    }

    public function store(RoleRequest $request): JsonResponse|RedirectResponse
    {
        $role = Role::create(['name' => $request->validated()['name']]);
        $role->syncPermissions($request->input('permissions', []));

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Rol '.__('messages.creado')]);
        }

        return Redirect::route('roles.index')->with('success', 'Rol '.__('messages.creado'));
    }

    public function show(Role $role): RedirectResponse
    {
        return Redirect::route('roles.index');
    }

    public function edit(Role $role): JsonResponse|RedirectResponse
    {
        if (request()->expectsJson()) {
            return response()->json([
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name'),
            ]);
        }

        return Redirect::route('roles.index');
    }

    public function update(RoleRequest $request, Role $role): JsonResponse|RedirectResponse
    {
        $role->update(['name' => $request->validated()['name']]);
        $role->syncPermissions($request->input('permissions', []));

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Rol '.__('messages.actualizado')]);
        }

        return Redirect::route('roles.index')->with('success', 'Rol '.__('messages.actualizado'));
    }

    public function destroy(Role $role): JsonResponse|RedirectResponse
    {
        $role->delete();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Rol '.__('messages.eliminado')]);
        }

        return Redirect::route('roles.index')->with('success', 'Rol '.__('messages.eliminado'));
    }

    public function getPermissions(string $id): JsonResponse
    {
        $role = Role::where('name', $id)->firstOrFail();

        return response()->json([
            'permissions' => Permission::all(),
            'permissionsRole' => $role->permissions,
        ]);
    }

    public function getRolesAjax(Request $request): JsonResponse
    {
        return response()->json(Role::all(['id', 'name']));
    }
}
