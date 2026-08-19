# Campos de auditoría

`AuditableModel` incorpora `HasAuditFields` para registrar al usuario autenticado que crea o actualiza un registro.

## Esquema y modelo

```php
Schema::create('invoices', function (Blueprint $table) {
    $table->id();
    $table->publicId();
    $table->auditFields(nullable: true);
    $table->timestamps();
});
```

```php
use SVR\LaravelCore\Models\AuditableModel;

class Invoice extends AuditableModel
{
    protected $guarded = [];
}
```

## Comportamiento

Al crear un modelo con un usuario autenticado, el trait asigna su llave a ambos campos:

- `created_by_id` se completa si aún no tiene un valor.
- `updated_by_id` se completa si aún no tiene un valor.

Esto permite establecer valores explícitos durante la creación sin que sean reemplazados. Al actualizar, `updated_by_id` siempre se cambia por el ID del usuario autenticado; `created_by_id` permanece intacto.

Cuando no existe una sesión autenticada, el trait no asigna valores. Usa columnas nullable o asigna los campos manualmente en procesos sin usuario, de acuerdo con las reglas de tu aplicación.

## Configuración

```php
'audit' => [
    'created_by_column' => 'created_by_id',
    'updated_by_column' => 'updated_by_id',
    'users_table' => 'users',
],
```

Los métodos `getCreatedByColumn()` y `getUpdatedByColumn()` leen estos valores. Si cambias los nombres, actualiza también el esquema mediante una migración.

El paquete no define relaciones Eloquent hacia el usuario. Cada aplicación puede declararlas con los nombres y el modelo de usuario que necesite:

```php
public function createdBy(): BelongsTo
{
    return $this->belongsTo(User::class, $this->getCreatedByColumn());
}
```
