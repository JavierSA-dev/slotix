<?php

namespace App\Http\Controllers;

use App\DataTables\PermissionDataTableConfig;
use App\Http\Requests\PermissionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class PermissionController extends Controller
{
    public function index(): View
    {
        $config = new PermissionDataTableConfig;
        $roles = Role::all();

        return view('permissions.index', compact('config', 'roles'));
    }

    public function getAjax(Request $request): JsonResponse
    {
        $query = Permission::with('roles')->select(['id', 'name', 'guard_name', 'created_at']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('roles', function ($row) {
                $roles = $row->roles->pluck('name')->toArray();
                if (empty($roles)) {
                    return '-';
                }

                if (count($roles) > 3) {
                    $shown = array_slice($roles, 0, 3);
                    $remaining = count($roles) - 3;

                    return implode(', ', $shown)." <span class='text-muted'>+{$remaining} más</span>";
                }

                return implode(', ', $roles);
            })
            ->addColumn('action', function ($row) {
                $btn = '<div class="d-flex gap-1 justify-content-center">';
                $btn .= '<button type="button" class="btn btn-sm btn-success btn-edit-permission" '
                    .'data-url="'.route('permissions.edit', $row->id).'" '
                    .'title="'.__('botones.Editar').'">'
                    .'<i class="fa fa-edit"></i></button>';
                $btn .= '<button type="button" class="btn btn-danger btn-sm delete-button" '
                    .'data-id="'.$row->id.'" title="'.__('botones.Eliminar').'">'
                    .'<i class="fa fa-trash"></i></button>';
                $btn .= '</div>';

                return $btn;
            })
            ->rawColumns(['roles', 'action'])
            ->make(true);
    }

    public function show(Permission $permission): RedirectResponse
    {
        return Redirect::route('permissions.index');
    }

    public function create(): RedirectResponse
    {
        return Redirect::route('permissions.index');
    }

    public function store(PermissionRequest $request): JsonResponse|RedirectResponse
    {
        $permission = Permission::create(['name' => $request->validated()['name'], 'guard_name' => 'web']);
        $permission->syncRoles($request->input('roles', []));

        if ($request->expectsJson()) {
            return response()->json(['message' => __('messages.creado')]);
        }

        return Redirect::route('permissions.index')->with('success', 'Permiso '.__('messages.creado'));
    }

    public function edit(Permission $permission): JsonResponse|RedirectResponse
    {
        if (request()->expectsJson()) {
            return response()->json([
                'id' => $permission->id,
                'name' => $permission->name,
                'roles' => $permission->roles->pluck('name'),
            ]);
        }

        return Redirect::route('permissions.index');
    }

    public function update(PermissionRequest $request, Permission $permission): JsonResponse|RedirectResponse
    {
        $permission->update(['name' => $request->validated()['name']]);
        $permission->syncRoles($request->input('roles', []));

        if ($request->expectsJson()) {
            return response()->json(['message' => __('messages.actualizado')]);
        }

        return Redirect::route('permissions.index')->with('success', 'Permiso '.__('messages.actualizado'));
    }

    public function destroy(Permission $permission): JsonResponse|RedirectResponse
    {
        $permission->delete();

        if (request()->expectsJson()) {
            return response()->json(['message' => __('messages.eliminado')]);
        }

        return Redirect::route('permissions.index')->with('success', 'Permiso '.__('messages.eliminado'));
    }
}
