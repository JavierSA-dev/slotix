<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Jerarquía de Roles
    |--------------------------------------------------------------------------
    |
    | Define el nivel de cada rol. Nivel más bajo = más privilegios.
    | Un usuario solo puede asignar roles de nivel >= al suyo.
    |
    | Ejemplo:
    | - SuperAdmin (nivel 1) puede asignar: SuperAdmin, Admin, User
    | - Admin (nivel 2) puede asignar: Admin, User
    | - User (nivel 3) no puede crear usuarios
    |
    */
    'hierarchy' => [
        'SuperAdmin' => 1,
        'Admin'      => 2,
        'User'       => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Roles que pueden gestionar usuarios
    |--------------------------------------------------------------------------
    |
    | Lista de roles que tienen permitido crear/editar usuarios.
    | Otros roles no verán las opciones de gestión de usuarios.
    |
    */
    'can_manage_users' => ['SuperAdmin', 'Admin'],
];
