@push('style')
    <!--datatable css-->
    <link rel="stylesheet" href="{{ URL::asset('build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" />
    <link rel="stylesheet" href="{{ URL::asset('build/libs/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ URL::asset('build/libs/daterangepicker/daterangepicker.css') }}" />
    <!--select2 css-->
    <link href="{{ URL::asset('build/libs/select2/css/select2.min.css') }}" rel="stylesheet" type="text/css" />
    <!--sweetalert2 css-->
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
    @vite(['resources/scss/custom/views/datatables-filtros.scss'])
    @vite(['resources/scss/custom/components/select2-custom.scss'])
@endpush

<div class="datatable-wrapper">
    {{-- Header: Filtros a la izquierda, Botones a la derecha (Responsive) --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">

        {{-- Grupo 1: Filtros (Izquierda) --}}
        <div class="d-flex flex-wrap align-items-center gap-2">
            @if(isset($config['filters']) && count($config['filters']) > 0)
                @foreach($config['filters'] as $filter)
                    @if(isset($filter['html']) && $filter['html'] === false)
                        @continue
                    @endif

                    @php
                        $filterId = $filter['id'];
                        if (isset($filter['selector']) && strpos($filter['selector'], '#') === 0) {
                            $filterId = substr($filter['selector'], 1);
                        }
                    @endphp

                    @switch($filter['type'] ?? 'input')
                        @case('input')
                            <div class="filter-item">
                                <input
                                    type="{{ $filter['inputType'] ?? 'text' }}"
                                    id="{{ $filterId }}"
                                    class="form-control"
                                    placeholder="{{ $filter['placeholder'] ?? '' }}"
                                    style="{{ $filter['style'] ?? '' }};"
                                >
                            </div>
                            @break

                        @case('select')
                            <div class="filter-item">
                                <select
                                    class="form-select"
                                    id="{{ $filterId }}"
                                    style="{{ $filter['style'] ?? '' }};"
                                >
                                    @foreach(($filter['options'] ?? []) as $value => $label)
                                        <option value="{{ $value }}" {{ ($filter['selected'] ?? '') == $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @break

                        @case('select2')
                            <div class="filter-item">
                                <select
                                    class="form-select select2"
                                    id="{{ $filterId }}"
                                    style="{{ $filter['style'] ?? 'min-width: 150px;' }};"
                                    {{ isset($filter['multiple']) && $filter['multiple'] ? 'multiple="multiple"' : '' }}
                                    data-placeholder="{{ $filter['placeholder'] ?? 'Seleccionar...' }}"
                                >
                                    <option></option>

                                    @foreach(($filter['options'] ?? []) as $value => $label)
                                        @php
                                            $isSelected = false;
                                            $selectedValues = $filter['selected'] ?? [];
                                            if (!is_array($selectedValues)) {
                                                $selectedValues = [$selectedValues];
                                            }
                                            if (in_array((string)$value, array_map('strval', $selectedValues))) {
                                                $isSelected = true;
                                            }
                                        @endphp
                                        <option value="{{ $value }}" {{ $isSelected ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @break

                        @case('daterange')
                            <div class="filter-item">
                                <input
                                    type="text"
                                    id="{{ $filterId }}"
                                    class="form-control daterange-filter"
                                    placeholder="{{ $filter['placeholder'] ?? 'Seleccionar rango...' }}"
                                    style="{{ $filter['style'] ?? '' }};"
                                    autocomplete="off"
                                >
                            </div>
                            @break

                        @case('switch')
                            <div class="filter-item">
                                <div class="form-check form-switch form-check-reverse mb-0">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        role="switch"
                                        id="{{ $filterId }}"
                                        style="{{ $filter['style'] ?? '' }}"
                                        {{ !empty($filter['defaultOn']) ? 'checked' : '' }}
                                    >
                                    @if(!empty($filter['label']))
                                        <label class="form-check-label text-nowrap" for="{{ $filterId }}">{{ $filter['label'] }}</label>
                                    @endif
                                </div>
                            </div>
                            @break
                    @endswitch
                @endforeach

                {{-- Botón Limpiar --}}
                <div class="filter-item">
                    <button type="button" class="btn btn-secondary btn-clear-filters text-nowrap" title="Limpiar todos los filtros">
                        <i class="bx bx-eraser font-size-16 align-middle me-1"></i> Limpiar
                    </button>
                </div>
            @endif
        </div>

        {{-- Grupo 2: Botones de Acción (Derecha por defecto, Izquierda al saltar linea) --}}
        <div class="d-flex align-items-center gap-2">
            @foreach(($config['actionButtons'] ?? []) as $button)
                @if(isset($button['dropdown']) && $button['dropdown'])
                    {{-- Dropdown button --}}
                    <div class="btn-group">
                        <button type="button"
                                class="{{ $button['class'] ?? 'btn btn-primary dropdown-toggle' }}"
                                id="{{ $button['id'] ?? '' }}"
                                data-bs-toggle="dropdown"
                                aria-expanded="false">
                            @if(isset($button['icon']))
                                <i class="{{ $button['icon'] }} font-size-16 align-middle me-2"></i>
                            @endif
                            {{ $button['text'] ?? '' }}
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="{{ $button['id'] ?? '' }}">
                            @foreach(($button['items'] ?? []) as $item)
                                @if(isset($item['divider']) && $item['divider'])
                                    <li><hr class="dropdown-divider"></li>
                                @else
                                    <li>
                                        @php
                                            $itemAttributes = $item['attributes'] ?? [];
                                            $itemClass = $itemAttributes['class'] ?? '';
                                            $finalClass = trim('dropdown-item ' . $itemClass);
                                            unset($itemAttributes['class']);
                                        @endphp
                                        <a class="{{ $finalClass }}" href="{{ $item['url'] ?? '#' }}" {!! collect($itemAttributes)->map(fn($v, $k) => "$k=\"$v\"")->implode(' ') !!}>
                                            @if(isset($item['icon']))
                                                <i class="{{ $item['icon'] }} me-2"></i>
                                            @endif
                                            {{ $item['text'] ?? '' }}
                                        </a>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                @else
                    {{-- Normal button --}}
                    <a href="{{ $button['url'] ?? '#' }}"
                       class="{{ $button['class'] ?? 'btn btn-primary' }}"
                       id="{{ $button['id'] ?? '' }}"
                       {!! isset($button['attributes']) ? collect($button['attributes'])->map(fn($v, $k) => "$k=\"$v\"")->implode(' ') : '' !!}>
                        @if(isset($button['icon']))
                            <i class="{{ $button['icon'] }} font-size-16 align-middle me-2"></i>
                        @endif
                        {{ $button['text'] ?? '' }}
                    </a>
                @endif
            @endforeach
        </div>
    </div>

    {{-- Tabla: DataTables con scrollY crea su propio wrapper de scroll --}}
    <table class="table table-bordered yajra-datatable w-100" id="{{ $config['table']['id'] }}">
        <thead>
            <tr>
                @foreach($config['table']['columns'] as $col)
                    <th>{{ $col['header'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>

    {{-- Slots para modales u contenido extra --}}
    {{ $slot ?? '' }}
</div>

@push('scripts')
    <script src="{{ URL::asset('build/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('build/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ URL::asset('build/libs/datatables.net-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ URL::asset('build/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ URL::asset('build/libs/moment/min/moment.min.js') }}"></script>
    <script src="{{ URL::asset('build/libs/daterangepicker/daterangepicker.min.js') }}"></script>
    <script src="{{ URL::asset('build/libs/select2/js/select2.min.js') }}"></script>
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.all.min.js') }}"></script>
    @vite(['resources/js/components/yajra-datatable.js'])
    <script type="text/javascript">
        $(function() {
            const tableConfig = @json($config);
            window.crudTable = new CrudTable(tableConfig);
        });
    </script>
@endpush
