<?php

namespace App\DataTables;

class Column
{
    protected string $header;
    protected string $data;
    protected bool $orderable = true;
    protected bool $searchable = true;
    protected ?string $width = null;
    protected ?string $className = null;

    public function __construct(string $header, string $data)
    {
        $this->header = $header;
        $this->data = $data;
    }

    public static function make(string $header, string $data): static
    {
        return new static($header, $data);
    }

    public function orderable(bool $orderable = true): static
    {
        $this->orderable = $orderable;
        return $this;
    }

    public function searchable(bool $searchable = true): static
    {
        $this->searchable = $searchable;
        return $this;
    }

    public function width(string $width): static
    {
        $this->width = $width;
        return $this;
    }

    public function className(string $className): static
    {
        $this->className = $className;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'header' => $this->header,
            'data' => $this->data,
            'name' => $this->data, // DataTables usa 'name' para identificar la columna en el servidor
            'orderable' => $this->orderable,
            'searchable' => $this->searchable,
            'width' => $this->width,
            'className' => $this->className,
        ];
    }
}
