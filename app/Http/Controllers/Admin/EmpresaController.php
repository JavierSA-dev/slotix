<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\EmpresaDataTableConfig;
use App\Http\Controllers\Controller;
use App\Http\Requests\ActualizarMiEmpresaRequest;
use App\Http\Requests\EmpresaRequest;
use App\Models\Empresa;
use App\Models\Modulo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class EmpresaController extends Controller
{
    public function index(): View
    {
        $config = new EmpresaDataTableConfig;
        $modulos = Modulo::where('activo', true)->get();

        return view('admin.empresas.index', compact('config', 'modulos'));
    }

    public function getAjax(Request $request): JsonResponse
    {
        $query = Empresa::query()->withCount(['modulos as modulos_activos_count' => function ($q) {
            $q->where('empresa_modulo.activo', true);
        }]);

        if ($request->filled('nombre')) {
            $query->where('nombre', 'like', '%'.$request->input('nombre').'%');
        }

        if ($request->filled('search') && $search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%");
            });
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('activo_badge', fn ($e) => $e->activo
                ? '<span class="pill-label pill-label-primary">Activa</span>'
                : '<span class="pill-label pill-label-secondary">Inactiva</span>')
            ->addColumn('modulos_count', fn ($e) => $e->modulos_activos_count ?? 0)
            ->addColumn('action', fn ($e) => $this->renderAcciones($e))
            ->rawColumns(['activo_badge', 'action'])
            ->make(true);
    }

    public function store(EmpresaRequest $request): JsonResponse
    {
        $data = $request->validated();
        $slug = $data['id'] ?? Str::slug($data['nombre'], '_');

        $empresa = Empresa::create([
            'id' => $slug,
            'nombre' => $data['nombre'],
            'tema' => $data['tema'] ?? 'neon',
            'colores' => $data['colores'] ?? null,
            'activo' => $data['activo'] ?? true,
        ]);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->storeAs(
                'logos',
                $slug.'.'.$request->file('logo')->getClientOriginalExtension(),
                'public'
            );
            $empresa->update(['logo' => $path]);
        }

        // Activar módulo "reservas" por defecto
        $moduloReservas = Modulo::where('nombre', 'reservas')->first();
        if ($moduloReservas) {
            $empresa->modulos()->attach($moduloReservas->id, ['activo' => true]);
        }

        return response()->json(['message' => 'Empresa creada correctamente.', 'id' => $empresa->id]);
    }

    public function show(Empresa $empresa): JsonResponse
    {
        return response()->json([
            'id' => $empresa->id,
            'nombre' => $empresa->nombre,
            'logo' => $empresa->logo ? Storage::url($empresa->logo) : null,
            'tema' => $empresa->tema ?? 'neon',
            'colores' => $empresa->getColoresDefecto(),
            'activo' => $empresa->activo,
            'en_mantenimiento' => $empresa->en_mantenimiento,
            'modulos' => $empresa->modulos()->withPivot('activo')->get()->map(fn ($m) => [
                'id' => $m->id,
                'nombre' => $m->nombre,
                'label' => $m->label,
                'activo' => (bool) $m->pivot->activo,
            ]),
        ]);
    }

    public function update(EmpresaRequest $request, Empresa $empresa): JsonResponse
    {
        $data = $request->validated();

        $empresa->update([
            'nombre' => $data['nombre'],
            'tema' => $data['tema'] ?? $empresa->tema,
            'colores' => $data['colores'] ?? $empresa->colores,
            'activo' => $data['activo'] ?? $empresa->activo,
        ]);

        if ($request->hasFile('logo')) {
            if ($empresa->logo) {
                Storage::disk('public')->delete($empresa->logo);
            }
            $path = $request->file('logo')->storeAs(
                'logos',
                $empresa->id.'.'.$request->file('logo')->getClientOriginalExtension(),
                'public'
            );
            $empresa->update(['logo' => $path]);
        }

        return response()->json(['message' => 'Empresa actualizada correctamente.']);
    }

    public function destroy(Empresa $empresa): JsonResponse
    {
        if ($empresa->logo) {
            Storage::disk('public')->delete($empresa->logo);
        }

        $empresa->delete();

        return response()->json(['message' => 'Empresa eliminada correctamente.']);
    }

    public function listAjax(): JsonResponse
    {
        $user = auth()->user();

        if ($user->hasRole('SuperAdmin')) {
            $empresas = Empresa::where('activo', true)->orderBy('nombre')->get(['id', 'nombre']);
        } else {
            $empresas = $user->empresas()->where('tenants.activo', true)->orderBy('nombre')->get(['tenants.id', 'tenants.nombre']);
        }

        return response()->json($empresas->map(fn ($e) => ['id' => $e->id, 'name' => $e->nombre]));
    }

    public function miEmpresa(): View
    {
        $empresaId = session('empresa_id');
        $empresa = Empresa::findOrFail($empresaId);

        return view('admin.empresas.mi-empresa', compact('empresa'));
    }

    public function actualizarMiEmpresa(ActualizarMiEmpresaRequest $request): JsonResponse
    {
        $empresaId = session('empresa_id');
        $empresa = Empresa::findOrFail($empresaId);

        if (! auth()->user()->puedeGestionarEmpresa($empresa)) {
            return response()->json(['message' => 'No tienes acceso a esta empresa.'], 403);
        }

        $data = $request->validated();

        $empresa->update([
            'nombre' => $data['nombre'],
            'tema' => $data['tema'] ?? $empresa->tema,
            'colores' => $data['colores'] ?? $empresa->colores,
        ]);

        if ($request->hasFile('logo')) {
            if ($empresa->logo) {
                Storage::disk('public')->delete($empresa->logo);
            }
            $path = $request->file('logo')->storeAs(
                'logos',
                $empresa->id.'.'.$request->file('logo')->getClientOriginalExtension(),
                'public'
            );
            $empresa->update(['logo' => $path]);
        }

        return response()->json(['message' => 'Empresa actualizada correctamente.']);
    }

    public function switchEmpresa(Request $request): JsonResponse
    {
        $request->validate(['empresa_id' => ['required', 'string', Rule::exists('central.tenants', 'id')]]);

        $empresaId = $request->input('empresa_id');
        $empresa = Empresa::findOrFail($empresaId);

        if (! auth()->user()->puedeGestionarEmpresa($empresa)) {
            return response()->json(['message' => 'No tienes acceso a esta empresa.'], 403);
        }

        session(['empresa_id' => $empresaId]);

        return response()->json(['message' => 'Empresa seleccionada.', 'empresa_id' => $empresaId]);
    }

    public function migrarTodas(): JsonResponse
    {
        $empresas = Empresa::all();
        $resultados = [];

        foreach ($empresas as $empresa) {
            try {
                tenancy()->initialize($empresa);
                \Artisan::call('migrate', ['--force' => true, '--path' => 'database/migrations/tenant', '--realpath' => false]);
                tenancy()->end();
                $resultados[] = ['empresa' => $empresa->nombre, 'estado' => 'ok'];
            } catch (\Exception $e) {
                tenancy()->end();
                $resultados[] = ['empresa' => $empresa->nombre, 'estado' => 'error', 'mensaje' => $e->getMessage()];
            }
        }

        return response()->json(['message' => 'Migraciones ejecutadas.', 'resultados' => $resultados]);
    }

    private function renderAcciones(Empresa $empresa): string
    {
        $btn = '<div class="d-flex gap-1 justify-content-center">';
        $btn .= '<button class="btn btn-sm btn-primary btn-editar-empresa" data-id="'.$empresa->id.'" title="Editar"><i class="fa fa-edit"></i></button>';
        $btn .= '<button class="btn btn-sm btn-info btn-modulos-empresa" data-id="'.$empresa->id.'" title="Módulos"><i class="bx bx-layer"></i></button>';
        $btn .= '<button class="btn btn-sm btn-danger btn-eliminar-empresa" data-id="'.$empresa->id.'" title="Eliminar"><i class="fa fa-trash"></i></button>';
        $btn .= '</div>';

        return $btn;
    }
}
