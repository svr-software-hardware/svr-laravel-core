# Modelos base

## `BaseModel`

Extiende `SVR\LaravelCore\Models\BaseModel` para adoptar las convenciones comunes:

```php
namespace App\Models;

use SVR\LaravelCore\Models\BaseModel;

class Company extends BaseModel
{
    protected $fillable = ['name', 'is_active'];
}
```

`BaseModel`:

- genera un ID público al crear el registro;
- usa ese ID para route model binding;
- impide modificarlo después de crear el registro;
- convierte `is_active` a booleano;
- serializa fechas con el formato `Y-m-d H:i:s`.

La tabla debe contener la columna correspondiente, normalmente mediante `$table->publicId()`. Las reglas normales de asignación masiva de Eloquent siguen vigentes.

## `AuditableModel`

Para agregar además auditoría de usuario:

```php
namespace App\Models;

use SVR\LaravelCore\Models\AuditableModel;

class Invoice extends AuditableModel
{
    protected $fillable = ['company_id', 'total'];
}
```

La tabla debe incluir `$table->auditFields()`. Consulta [Campos de auditoría](audit-fields.md) para el comportamiento en creación y actualización.

## Elegir una clase base

| Necesidad | Clase |
| --- | --- |
| ID público, binding y convenciones de serialización | `BaseModel` |
| Lo anterior más `created_by_id` y `updated_by_id` automáticos | `AuditableModel` |

Si una aplicación ya tiene una clase base propia, puede reutilizar los traits `HasPublicId` y `HasAuditFields`; deberá conservar las mismas expectativas de esquema y configuración.
