# Migraciones

El service provider agrega dos macros a `Illuminate\Database\Schema\Blueprint`.

## `publicId()`

```php
Schema::create('companies', function (Blueprint $table) {
    $table->id();
    $table->publicId();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

La macro crea una columna `string` única. El nombre, longitud y collation proceden de `svr-core.public_id`. De forma predeterminada equivale conceptualmente a:

```php
$table->string('public_id', 10)
    ->collation('utf8mb4_bin')
    ->unique();
```

La collation binaria distingue mayúsculas de minúsculas, algo necesario con el alfabeto predeterminado. Usa `null` para omitir la collation en motores que no la soportan.

## `auditFields()`

```php
Schema::create('invoices', function (Blueprint $table) {
    $table->id();
    $table->publicId();
    $table->auditFields(nullable: true);
    $table->timestamps();
});
```

La macro crea `created_by_id` y `updated_by_id` como llaves foráneas hacia `users.id`, ambas con eliminación restringida. Sus nombres y la tabla referenciada son configurables.

Por defecto las columnas son obligatorias:

```php
$table->auditFields();
```

Usa `nullable: true` si el modelo también puede crearse o actualizarse sin un usuario autenticado, como sucede en comandos, colas o procesos del sistema. La macro no agrega timestamps; incluye `$table->timestamps()` cuando los necesites.

## Cambios de configuración

Cambiar nombres, longitud o tabla de usuarios después de migrar no modifica el esquema existente. Crea una migración nueva para mantener alineadas la configuración, las columnas y sus índices.
