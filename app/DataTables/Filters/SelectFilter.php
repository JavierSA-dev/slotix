<?php

namespace App\DataTables\Filters;

class SelectFilter extends Filter
{
    protected string $type = 'select';
    protected array $options = [];
    protected ?string $url = null;
    protected mixed $selected = null;
    protected bool $multiple = false;

    public function options(array $options): static
    {
        $this->options = $options;
        return $this;
    }

    public function url(string $url): static
    {
        $this->url = $url;
        $this->type = 'select'; // Select con AJAX
        return $this;
    }

    public function fromRoute(string $route, array $params = []): static
    {
        $this->url = route($route, $params);
        return $this;
    }

    public function selected(mixed $value): static
    {
        $this->selected = $value;
        return $this;
    }

    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;
        $this->type = 'select2';
        return $this;
    }

    public function select2(): static
    {
        $this->type = 'select2';
        return $this;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'options' => $this->options,
            'url' => $this->url,
            'selected' => $this->selected,
            'multiple' => $this->multiple,
        ]);
    }
}
