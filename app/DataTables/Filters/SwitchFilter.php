<?php

namespace App\DataTables\Filters;

class SwitchFilter extends Filter
{
    protected string $type = 'switch';
    protected string $label = '';
    protected bool $defaultOn = false;

    public function label(string $label): static
    {
        $this->label = $label;
        return $this;
    }

    public function defaultOn(bool $defaultOn = true): static
    {
        $this->defaultOn = $defaultOn;
        return $this;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'label'     => $this->label,
            'defaultOn' => $this->defaultOn,
        ]);
    }
}
