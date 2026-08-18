<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /** Roles definidos en el Documento Maestro de APZ Motor's. */
    public const ROLES = [
        'Admin',
        'Vendedor',
        'Inventario',
        'Soporte',
        'Cliente',
    ];

    /**
     * Matriz de permisos (Documento Maestro §4).
     *
     * @var array<string, array<int, string>>
     */
    private const PERMISOS_POR_ROL = [
        'Admin' => ['*'],
        'Vendedor' => [
            'ver productos',
            'ver ventas',
            'crear ventas',
            'ver clientes',
            'crear clientes',
            'ver reportes',
            'ver devoluciones',
            'crear devoluciones',
            'ver cierres de caja',
            'crear cierres de caja',
            'ver pedidos',
            'confirmar pedidos',
        ],
        'Inventario' => [
            'ver productos',
            'crear productos',
            'editar productos',
            'eliminar productos',
            'ver categorias',
            'crear categorias',
            'editar categorias',
            'eliminar categorias',
            'ver clientes',
            'ver reportes',
        ],
        'Soporte' => [],
        'Cliente' => [],
    ];

    private const RECURSOS = [
        'productos' => ['ver', 'crear', 'editar', 'eliminar'],
        'categorias' => ['ver', 'crear', 'editar', 'eliminar'],
        'ventas' => ['ver', 'crear', 'editar', 'eliminar'],
        'clientes' => ['ver', 'crear', 'editar', 'eliminar'],
        'reportes' => ['ver'],
        'usuarios' => ['ver', 'crear', 'editar', 'eliminar'],
        'devoluciones' => ['ver', 'crear', 'aprobar'],
        'cierres de caja' => ['ver', 'crear'],
        'pedidos' => ['ver', 'confirmar'],
        'respaldos' => ['ver'],
    ];

    private const PERMISOS_EXTRA = [
        'ver todos los cierres',
    ];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear todos los permisos posibles.
        foreach (self::RECURSOS as $recurso => $acciones) {
            foreach ($acciones as $accion) {
                Permission::firstOrCreate(['name' => "{$accion} {$recurso}"]);
            }
        }

        foreach (self::PERMISOS_EXTRA as $permiso) {
            Permission::firstOrCreate(['name' => $permiso]);
        }

        foreach (self::ROLES as $rol) {
            $role = Role::firstOrCreate(['name' => $rol]);

            if ($rol === 'Admin') {
                // Admin recibe todos los permisos explícitamente; además se
                // refuerza con Gate::before en AppServiceProvider.
                $role->syncPermissions(Permission::all());
            } else {
                $role->syncPermissions(self::PERMISOS_POR_ROL[$rol]);
            }
        }

        $this->crearUsuario('Admin', 'admin@apzmotors.com', 'Administrador del sistema');
        $this->crearUsuario('Vendedor', 'vendedor@apzmotors.com', 'Vendedor de tienda');
        $this->crearUsuario('Inventario', 'inventario@apzmotors.com', 'Encargado de almacén');
    }

    private function crearUsuario(string $rol, string $email, string $nombre): void
    {
        $usuario = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $nombre,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        if (! $usuario->hasRole($rol)) {
            $usuario->assignRole($rol);
        }
    }
}
