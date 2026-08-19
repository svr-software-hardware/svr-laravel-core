# IDs públicos

Los IDs públicos permiten exponer identificadores opacos sin revelar las llaves primarias internas.

## Generación

Los modelos que usan `HasPublicId` generan el valor durante el evento `creating`. De forma predeterminada es una cadena aleatoria de 10 caracteres alfanuméricos y distingue mayúsculas de minúsculas.

```php
$company = Company::create(['name' => 'SVR']);

$company->id;        // llave interna
$company->public_id; // identificador para la API
```

Si el atributo ya tiene un valor al crear el modelo, el paquete lo conserva. En caso contrario intenta generar uno que no exista hasta 10 veces y consulta sin scopes globales para detectar colisiones en toda la tabla. El índice único de la base de datos sigue siendo la garantía final.

Una vez persistido, el ID público es inmutable; intentar cambiarlo produce una `RuntimeException`.

## Route model binding

`HasPublicId::getRouteKeyName()` devuelve la columna configurada. Por ello, el binding implícito busca por `public_id`:

```php
Route::get('/companies/{company}', function (Company $company) {
    return $company;
});
```

Una URL válida tendría la forma `/companies/aB3xY91kLm`, no `/companies/42`.

## Configuración

| Clave | Predeterminado | Uso |
| --- | --- | --- |
| `svr-core.public_id.column` | `public_id` | Atributo del modelo y columna del esquema |
| `svr-core.public_id.length` | `10` | Longitud generada y longitud de la macro |
| `svr-core.public_id.alphabet` | `0-9A-Za-z` | Caracteres disponibles para la generación |
| `svr-core.public_id.collation` | `utf8mb4_bin` | Comparación sensible a mayúsculas en la columna |

El alfabeto debe contener al menos un carácter. Si se personaliza, revisa que la longitud y el espacio de combinaciones sean suficientes para el volumen esperado.

## Generación directa

Para casos excepcionales puede usarse el generador registrado en el contenedor:

```php
use SVR\LaravelCore\Support\PublicIdGenerator;

$id = app(PublicIdGenerator::class)->generate();
$shortId = app(PublicIdGenerator::class)->generate(8);
```

Pasar una longitud explícita reemplaza la longitud configurada solo para esa llamada. El generador produce cadenas aleatorias; no comprueba unicidad por sí mismo.
