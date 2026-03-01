<?php

namespace App\DataTables\Filters;

abstract class Filter
{
    protected string $id;
    protected string $type;
    protected ?string $placeholder = null;
    protected ?string $style = null;
    protected string $event = 'change';

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public static function make(string $id): static
    {
        return new static($id);
    }

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;
        return $this;
    }

    public function style(string $style): static
    {
        $this->style = $style;
        return $this;
    }

    public function event(string $event): static
    {
        $this->event = $event;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'selector' => '#' . $this->id,
            'type' => $this->type,
            'placeholder' => $this->placeholder,
            'style' => $this->style,
            'event' => $this->event,
        ];
    }
}
