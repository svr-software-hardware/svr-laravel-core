# Form Requests y Resources

El mismo mapa de relaciones permite aceptar IDs públicos en solicitudes y devolverlos en respuestas sin exponer llaves internas.

## Declarar `publicIdMap()`

> **Obligatorio:** declara este mapa en toda entidad que reciba IDs públicos relacionados. La existencia de una relación Eloquent por sí sola no permite que `BaseFormRequest` conozca qué modelo debe consultar.

Define el mapa en el modelo propietario:

```php
class Invoice extends AuditableModel
{
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function publicIdMap(): array
    {
        return [
            'company_id' => [
                'model' => Company::class,
                'relation' => 'company',
                'many' => false,
            ],
            'tag_ids' => [
                'model' => Tag::class,
                'relation' => 'tags',
                'many' => true,
            ],
        ];
    }
}
```

La clave exterior (`company_id` o `tag_ids`) es el campo usado tanto en la entrada como en la salida. `model` se usa para resolver la solicitud, `relation` para construir la respuesta y `many` indica si el campo contiene un arreglo.

## Resolver solicitudes

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
            'tag_ids' => ['array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
        ];
    }
}
```

Antes de validar, `BaseFormRequest` reemplaza cada ID público por la llave primaria interna. Por eso las reglas de `rules()` validan enteros internos, aunque el cliente envíe cadenas públicas.

- Los campos ausentes y los valores `null` no se transforman.
- Un campo con `many: true` debe ser un arreglo; se conserva su orden y `[]` sigue siendo `[]`.
- Un ID desconocido genera un error de validación en el campo correspondiente.
- Las consultas respetan los scopes globales del modelo relacionado, incluidos los de tenant o compañía.

Para preparar valores adicionales, implementa el hook sin reemplazar `prepareForValidation()`:

```php
protected function afterPublicIdResolution(): void
{
    $this->merge(['name' => trim((string) $this->input('name'))]);
}
```

El modelo devuelto por `modelClass()` debe ser Eloquent y soportar `getPublicIdColumn()`. Cada definición de entrada necesita `model`; una configuración inválida genera `LogicException`.

## Errores de validación en APIs

Un Form Request se valida antes de ejecutar el método del controlador. Si la petición no espera JSON, Laravel trata el fallo como un formulario web y redirige a la ubicación anterior. Los clientes deben enviar:

```text
Accept: application/json
Content-Type: application/json
```

Para garantizar JSON en todas las rutas API de Laravel 12 o 13, configura `bootstrap/app.php`:

```php
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;

->withExceptions(function (Exceptions $exceptions): void {
    $exceptions->shouldRenderJsonWhen(
        fn(Request $request, \Throwable $exception): bool =>
            $request->is('api/*') || $request->expectsJson()
    );
})
```

El error tendrá estado `422`. Personaliza sus textos mediante `messages()` en el Form Request o mediante los archivos de idioma de validación de la aplicación.


## Serializar respuestas

```php
use SVR\LaravelCore\Http\Resources\BaseResource;

return new BaseResource(
    $invoice->load('company', 'tags')
);
```

`BaseResource` aplica estas transformaciones:

1. Reemplaza el `id` propio por el ID público y elimina el atributo `public_id`.
2. Elimina llaves internas terminadas en `_id` cuando están declaradas en `publicIdMap()`, corresponden a auditoría o tienen una relación Eloquent.
3. Conserva y transforma recursivamente las relaciones cargadas que soportan IDs públicos.
4. Si una relación singular cargada coincide con una FK, reemplaza esa FK por el ID público relacionado; por ejemplo, `created_by_id` usa la relación `created_by`.
5. Agrega los campos de `publicIdMap()` con los IDs públicos de sus relaciones.

El resource nunca carga relaciones por sí mismo. Una relación debe cargarse explícitamente para aparecer y para convertir su FK. Una relación singular nula produce `null`; una colección produce un arreglo. Los modelos relacionados deben soportar IDs públicos para transformarse de forma segura.

Los campos terminados en `_id` que no representan relaciones se conservan sin configuración adicional. Por ejemplo, `tax_id`, `provider_id` o un identificador de un sistema externo permanecen en la respuesta cuando el modelo no declara una relación correspondiente. Por seguridad, toda FK interna debe estar declarada en `publicIdMap()` o mediante una relación Eloquent.

Para excepciones controladas, crea un resource propio:

```php
class InvoiceResource extends BaseResource
{
    protected function rawIdFields(): array
    {
        return ['provider_id'];
    }

    protected function preservedRelationFields(): array
    {
        return ['line_items'];
    }
}
```

`rawIdFields()` sigue disponible por compatibilidad para forzar que un atributo sea tratado como dato externo. Normalmente ya no es necesario porque los campos `_id` sin relación se conservan automáticamente. `preservedRelationFields()` permite conservar sin transformación relaciones cuyos modelos no soportan IDs públicos. Usa esta última excepción solamente cuando la estructura serializada sea segura y no pueda filtrar llaves internas.
