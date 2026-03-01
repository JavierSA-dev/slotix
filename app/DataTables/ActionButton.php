<?php

namespace App\DataTables;

class ActionButton
{
    protected string $text;
    protected ?string $url = null;
    protected ?string $id = null;
    protected string $class = 'btn btn-primary';
    protected ?string $icon = null;

    public function __construct(string $text)
    {
        $this->text = $text;
    }

    public static function make(string $text): static
    {
        return new static($text);
    }

    public function url(string $url): static
    {
        $this->url = $url;
        return $this;
    }

    public function route(string $route, array $params = []): static
    {
        $this->url = route($route, $params);
        return $this;
    }

    public function id(string $id): static
    {
        $this->id = $id;
        return $this;
    }

    public function class(string $class): static
    {
        $this->class = $class;
        return $this;
    }

    public function icon(string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    public function success(): static
    {
        $this->class = 'btn btn-success';
        return $this;
    }

    public function primary(): static
    {
        $this->class = 'btn btn-primary';
        return $this;
    }

    public function danger(): static
    {
        $this->class = 'btn btn-danger';
        return $this;
    }

    public function toArray(): array
    {
        return [
            'text' => $this->text,
            'url' => $this->url,
            'id' => $this->id,
            'class' => $this->class,
            'icon' => $this->icon,
        ];
    }
}
