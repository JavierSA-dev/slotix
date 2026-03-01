<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;

class UsersExport implements FromCollection
{
    protected $search;
    protected $role;
    protected $active;
    protected $date;

    public function __construct($search = null, $role = null, $active = null, $date = null)
    {
        $this->search = $search;
        $this->role = $role;
        $this->active = $active;
        $this->date = $date;
    }

    public function collection()
    {
        $query = User::query();

        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%");
            });
        }

        if ($this->role) {
            $query->whereHas('roles', function($q) {
                $q->where('name', $this->role);
            });
        }

        if (!is_null($this->active)) {
            $query->where('activo', $this->active);
        }

        if ($this->date) {
    if (strpos($this->date, ' - ') !== false) {
        [$start, $end] = explode(' - ', $this->date);
        $query->whereBetween('created_at', [
            $start . ' 00:00:00',
            $end . ' 23:59:59'
        ]);
    } else {
        $query->whereDate('created_at', $this->date);
    }
}

        return $query->get();
    }
}
