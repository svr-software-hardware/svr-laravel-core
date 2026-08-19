# Form Requests y Resources

El mismo mapa de relaciones permite aceptar IDs públicos en solicitudes y devolverlos en respuestas sin exponer llaves internas.

## Declarar `publicIdMap()`

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

## Serializar respuestas

```php
use SVR\LaravelCore\Http\Resources\BaseResource;

return new BaseResource(
    $invoice->load('company', 'tags')
);
```

`BaseResource` aplica estas transformaciones:

1. Reemplaza el `id` propio por el ID público y elimina el atributo `public_id`.
2. Elimina llaves internas terminadas en `_id`.
3. Conserva y transforma recursivamente las relaciones cargadas que soportan IDs públicos.
4. Si una relación singular cargada coincide con una FK, reemplaza esa FK por el ID público relacionado; por ejemplo, `created_by_id` usa la relación `created_by`.
5. Agrega los campos de `publicIdMap()` con los IDs públicos de sus relaciones.

El resource nunca carga relaciones por sí mismo. Una relación debe cargarse explícitamente para aparecer y para convertir su FK. Una relación singular nula produce `null`; una colección produce un arreglo. Los modelos relacionados deben soportar IDs públicos para transformarse de forma segura.

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

`rawIdFields()` conserva atributos terminados en `_id` que no son llaves internas. `preservedRelationFields()` permite conservar sin transformación relaciones cuyos modelos no soportan IDs públicos. Usa esta última excepción solamente cuando la estructura serializada sea segura y no pueda filtrar llaves internas.
