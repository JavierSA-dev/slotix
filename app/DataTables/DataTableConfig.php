<?php

namespace App\DataTables;

use JsonSerializable;

abstract class DataTableConfig implements JsonSerializable
{
    protected string $tableId = 'yajra-datatable';
    protected string $languageUrl = 'build/json/datatable_es-ES.json';

    abstract protected function ajaxRoute(): string;
    abstract protected function urlBase(): string;
    abstract protected function columns(): array;

    protected function filters(): array
    {
        return [];
    }

    protected function actionButtons(): array
    {
        return [];
    }

    public function toArray(): array
    {
        return [
            'urls' => [
                'ajax' => route($this->ajaxRoute()),
                'idioma' => asset($this->languageUrl),
                'data' => url($this->urlBase()),
                'urlBase' => $this->urlBase(),
            ],
            'table' => [
                'id' => $this->tableId,
                'columns' => $this->resolveColumns(),
            ],
            'filters' => $this->resolveFilters(),
            'actionButtons' => $this->resolveActionButtons(),
        ];
    }

    protected function resolveColumns(): array
    {
        return array_map(
            fn($col) => $col instanceof Column ? $col->toArray() : $col,
            $this->columns()
        );
    }

    protected function resolveFilters(): array
    {
        return array_map(
            fn($filter) => $filter instanceof Filters\Filter ? $filter->toArray() : $filter,
            $this->filters()
        );
    }

    protected function resolveActionButtons(): array
    {
        return array_map(
            fn($btn) => $btn instanceof ActionButton ? $btn->toArray() : $btn,
            $this->actionButtons()
        );
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
