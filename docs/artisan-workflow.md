# Flujo de trabajo con Artisan

SVR Laravel Core no reemplaza los generadores de Laravel. Usa los comandos `make:*` habituales y después adapta las clases generadas para que extiendan las bases del paquete.

## Crear un modelo y su migración

```bash
php artisan make:model Invoice -m
```

Artisan crea `app/Models/Invoice.php` y una migración. Cambia la clase base generada según las necesidades del modelo:

```php
namespace App\Models;

use SVR\LaravelCore\Models\BaseModel;

class Invoice extends BaseModel
{
    protected $fillable = [
        'company_id',
        'total',
        'is_active',
    ];
}
```

Usa `AuditableModel` cuando también necesites registrar al usuario que crea o actualiza el registro:

```php
use SVR\LaravelCore\Models\AuditableModel;

class Invoice extends AuditableModel
{
    // ...
}
```

Completa la migración con las macros del paquete:

```php
Schema::create('invoices', function (Blueprint $table) {
    $table->id();
    $table->publicId();
    $table->foreignId('company_id')->constrained()->restrictOnDelete();
    $table->decimal('total', 12, 2);
    $table->boolean('is_active')->default(true);
    $table->auditFields(nullable: true);
    $table->timestamps();
});
```

Incluye `auditFields()` únicamente si el modelo usa `AuditableModel`. Consulta [Migraciones](migrations.md) para decidir si las columnas deben ser nullable.

Ejecuta la migración:

```bash
php artisan migrate
```

## Crear un Form Request

```bash
php artisan make:request StoreInvoiceRequest
```

Reemplaza `FormRequest` por `BaseFormRequest` e indica el modelo de la operación:

```php
namespace App\Http\Requests;

use App\Models\Invoice;
use SVR\LaravelCore\Http\Requests\BaseFormRequest;

class StoreInvoiceRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function modelClass(): string
    {
        return Invoice::class;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'total' => ['required', 'numeric', 'min:0'],
        ];
    }
}
```

Si `company_id` se declara en `Invoice::publicIdMap()`, el cliente envía el ID público y el request lo convierte a la llave interna antes de ejecutar estas reglas.

## Crear un API Resource

```bash
php artisan make:resource InvoiceResource
```

Para aplicar automáticamente el reemplazo y ocultamiento de IDs, extiende `BaseResource`:

```php
namespace App\Http\Resources;

use SVR\LaravelCore\Http\Resources\BaseResource;

class InvoiceResource extends BaseResource
{
}
```

Las relaciones declaradas en `publicIdMap()` deben estar cargadas para aparecer como IDs públicos:

```php
return new InvoiceResource(
    $invoice->load('company')
);
```

Consulta [Form Requests y Resources](requests-and-resources.md) para relaciones múltiples y campos que deben conservarse.

## Crear un controlador API

```bash
php artisan make:controller Api/InvoiceController --api
```

Un método de creación típico conecta las piezas anteriores:

```php
public function store(StoreInvoiceRequest $request): InvoiceResource
{
    $invoice = Invoice::create($request->validated());

    return new InvoiceResource(
        $invoice->load('company')
    );
}
```

## Factory y seeder

```bash
php artisan make:factory InvoiceFactory --model=Invoice
php artisan make:seeder InvoiceSeeder
php artisan db:seed --class=InvoiceSeeder
```

No es necesario asignar `public_id` en la factory: `BaseModel` lo genera durante `creating`. Si el modelo es auditable y el seeder no autentica a un usuario, las columnas de auditoría deben aceptar `null` o establecerse explícitamente.

## Secuencia recomendada

Para un recurso nuevo:

```bash
php artisan make:model Invoice -m
php artisan make:request StoreInvoiceRequest
php artisan make:request UpdateInvoiceRequest
php artisan make:resource InvoiceResource
php artisan make:controller Api/InvoiceController --api
php artisan make:factory InvoiceFactory --model=Invoice
php artisan migrate
```

Después de generar los archivos:

1. Elige `BaseModel` o `AuditableModel`.
2. Agrega `publicId()` y, si corresponde, `auditFields()` a la migración.
3. Declara relaciones y `publicIdMap()` en el modelo.
4. Extiende `BaseFormRequest` en los requests.
5. Extiende `BaseResource` en el resource.
6. Carga las relaciones necesarias antes de construir la respuesta.
7. Ejecuta las pruebas de la aplicación.
