# Instalación y configuración

## Requisitos

- PHP 8.2 o superior para Laravel 12.
- PHP 8.3 o superior para Laravel 13.
- Acceso al repositorio privado de paquetes de SVR.

Instala el paquete con Composer:

```bash
composer require svr/laravel-core
```

El paquete declara `SVR\LaravelCore\SvrLaravelCoreServiceProvider` para el descubrimiento automático de Laravel. El provider carga la configuración y registra las macros de migración `publicId()` y `auditFields()`.

## Publicar la configuración

```bash
php artisan vendor:publish --tag=svr-core-config
```

El comando crea `config/svr-core.php`. No es obligatorio publicarlo si los valores predeterminados son adecuados.

```php
return [
    'public_id' => [
        'column' => 'public_id',
        'length' => 10,
        'alphabet' => '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz',
        'collation' => 'utf8mb4_bin',
    ],
    'audit' => [
        'created_by_column' => 'created_by_id',
        'updated_by_column' => 'updated_by_id',
        'users_table' => 'users',
    ],
];
```

`public_id.collation` puede ser `null` cuando el motor no admite `utf8mb4_bin`, por ejemplo en pruebas con SQLite. Define toda personalización antes de ejecutar las migraciones: los nombres y longitudes configurados deben coincidir con el esquema existente.

## Verificación

```bash
php artisan about
php artisan migrate
```

No es necesario registrar manualmente el service provider salvo que la aplicación haya desactivado el descubrimiento de paquetes.
