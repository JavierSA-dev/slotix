<?php

namespace App\DataTables\Filters;

class InputFilter extends Filter
{
    protected string $type = 'input';
    protected string $inputType = 'text';
    protected string $event = 'keyup';

    public function inputType(string $type): static
    {
        $this->inputType = $type;
        return $this;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'inputType' => $this->inputType,
        ]);
    }
}
