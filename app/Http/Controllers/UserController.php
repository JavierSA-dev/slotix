<?php

namespace App\Http\Controllers;

use App\DataTables\UserDataTableConfig;
use App\Exports\UsersExport;
use App\Http\Requests\UserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {
        // Autoriza automaticamente todos los metodos del resource
        // Mapeo: index->viewAny, show->view, create/store->create, edit/update->update, destroy->delete
        $this->authorizeResource(User::class, 'user');
    }

    public function index(): View
    {
        $config = new UserDataTableConfig;
        $roles = auth()->user()->getAssignableRoleModels();

        return view('users.index', compact('config', 'roles'));
    }

    public function getAjax(Request $request): JsonResponse
    {
        $query = User::with('roles')->select(['id', 'name', 'email', 'avatar', 'activo', 'created_at']);

        $this->applyFilters($query, $request);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('avatar', function ($row) {
                $src = $row->avatar
                    ? asset('storage/avatares/'.$row->avatar)
                    : '/build/images/users/avatar.png';

                return '<img src="'.$src.'" width="40" height="40" class="rounded-circle"/>';
            })
            ->addColumn('rol', function ($row) {
                return $row->roles->pluck('name')->implode(', ') ?: '-';
            })
            ->addColumn('activo', function ($row) {
                return $row->activo
                    ? '<span class="pill-label pill-label-primary">Sí</span>'
                    : '<span class="pill-label pill-label-secondary">No</span>';
            })
            ->addColumn('action', function ($row) {
                $btn = '<div class="d-flex gap-1 justify-content-center">';

                if (Gate::allows('update', $row)) {
                    $btn .= '<button type="button" class="btn btn-sm btn-success btn-edit-user" '
                        .'data-url="'.route('users.edit', $row->id).'" '
                        .'title="'.__('botones.Editar').'">'
                        .'<i class="fa fa-edit"></i></button>';
                }

                if (Gate::allows('delete', $row)) {
                    $btn .= '<button type="button" class="btn btn-danger btn-sm delete-button" '
                        .'data-id="'.$row->id.'" title="'.__('botones.Eliminar').'">'
                        .'<i class="fa fa-trash"></i></button>';
                }

                $btn .= '</div>';

                return $btn;
            })
            ->rawColumns(['avatar', 'activo', 'action'])
            ->make(true);
    }

    public function export(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        return Excel::download(
            new UsersExport(
                $request->input('search'),
                $request->input('role'),
                $request->input('active'),
                $request->input('date'),
            ),
            'usuarios.xlsx'
        );
    }

    public function show(User $user): RedirectResponse
    {
        return Redirect::route('users.index');
    }

    public function create(): RedirectResponse
    {
        return Redirect::route('users.index');
    }

    public function store(UserRequest $request): JsonResponse|RedirectResponse
    {
        $user = $this->userService->create($request->validated());

        if ($request->hasFile('avatar')) {
            $result = $this->userService->processAvatar($user, $request->file('avatar'));

            if (! $result['success']) {
                $this->userService->delete($user);

                if ($request->expectsJson()) {
                    return response()->json(['message' => $result['error']], 422);
                }

                return Redirect::back()->with('error', $result['error']);
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => __('messages.user_created')]);
        }

        return Redirect::route('users.index')->with('success', __('messages.user_created'));
    }

    public function edit(User $user): JsonResponse|RedirectResponse
    {
        if (request()->expectsJson()) {
            return response()->json([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'activo' => $user->activo,
                'roles' => $user->roles->pluck('name'),
                'avatar' => $user->avatar
                    ? asset('storage/avatares/'.$user->avatar)
                    : null,
            ]);
        }

        return Redirect::route('users.index');
    }

    public function update(UserRequest $request, User $user): JsonResponse|RedirectResponse
    {
        $this->userService->update($user, $request->all());

        if ($request->hasFile('avatar')) {
            $result = $this->userService->processAvatar($user, $request->file('avatar'));

            if (! $result['success']) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => $result['error']], 422);
                }

                return Redirect::back()->with('error', $result['error']);
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => __('messages.user_updated')]);
        }

        return Redirect::route('users.index')->with('success', __('messages.user_updated'));
    }

    public function destroy(User $user): JsonResponse|RedirectResponse
    {
        $this->userService->delete($user);

        if (request()->expectsJson()) {
            return response()->json(['message' => __('messages.user_deleted')]);
        }

        return Redirect::route('users.index')->with('success', __('messages.user_deleted'));
    }

    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $roleId = $request->input('role');
            $query->whereHas('roles', fn ($q) => $q->where('id', $roleId));
        }

        if ($request->filled('active')) {
            $query->where('activo', $request->input('active'));
        }

        if ($request->filled('date')) {
            $date = $request->input('date');
            if (strpos($date, ' - ') !== false) {
                [$start, $end] = explode(' - ', $date);
                $query->whereBetween('created_at', [$start.' 00:00:00', $end.' 23:59:59']);
            } else {
                $query->whereDate('created_at', $date);
            }
        }
    }
}
