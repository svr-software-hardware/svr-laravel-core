# SVR Laravel Core

Librería privada con convenciones y utilidades compartidas para los proyectos Laravel de SVR.

## Compatibilidad

| Laravel | PHP |
| --- | --- |
| 12.x | 8.2 o superior |
| 13.x | 8.3 o superior |

El paquete permite PHP 8.2 para conservar compatibilidad con Laravel 12. Laravel 13 exige PHP 8.3 desde el propio framework.

## Instalación

Después de configurar acceso al repositorio privado de la organización SVR:

```bash
composer require svr/laravel-core
```

Laravel descubre automáticamente el service provider. Para publicar la configuración:

```bash
php artisan vendor:publish --tag=svr-core-config
```

## Configuración

`config/svr-core.php` permite cambiar el nombre, longitud, alfabeto y collation de `public_id`, además de las columnas y la tabla de usuarios empleadas para auditoría. La collation puede establecerse en `null` para motores que no admiten `utf8mb4_bin`.

## Modelos con public ID

```php
use SVR\LaravelCore\Models\BaseModel;

class Company extends BaseModel
{
}
```

`BaseModel` genera un `public_id`, lo usa para route model binding, serializa fechas como `Y-m-d H:i:s` y convierte `is_active` a booleano.

En la migración:

```php
Schema::create('companies', function (Blueprint $table) {
    $table->id();
    $table->publicId();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

## Modelos auditables

```php
use SVR\LaravelCore\Models\AuditableModel;

class Invoice extends AuditableModel
{
}
```

La migración puede agregar las columnas con:

```php
$table->auditFields(nullable: true);
```

## IDs públicos en relaciones

El modelo declara una sola vez el mapa usado para entrada y salida de la API:

```php
public function publicIdMap(): array
{
    return [
        'company_id' => [
            'model' => Company::class,
            'relation' => 'company',
            'many' => false,
        ],
    ];
}
```

Los Form Requests deben extender `BaseFormRequest` y señalar su modelo. Las reglas reciben el ID interno después de la resolución:

```php
use SVR\LaravelCore\Http\Requests\BaseFormRequest;

class StoreInvoiceRequest extends BaseFormRequest
{
    protected function modelClass(): string
    {
        return Invoice::class;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
        ];
    }
}
```

Para devolver IDs públicos sin exponer llaves internas:

```php
use SVR\LaravelCore\Http\Resources\BaseResource;

return new BaseResource($invoice->load('company'));
```

Las relaciones múltiples usan `'many' => true` y un arreglo de IDs públicos.

## Desarrollo

```bash
composer install
composer validate --strict
composer test
```

## Versionado

La librería usa [Semantic Versioning](https://semver.org/). Mientras la API siga en desarrollo se publicarán versiones `0.x`; la primera versión propuesta es `v0.1.0`.

## Licencia

Software propietario de SVR. No se permite su distribución pública.
