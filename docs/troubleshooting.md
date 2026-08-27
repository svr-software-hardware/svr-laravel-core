# Diagnóstico de integración

Esta guía reúne los fallos más frecuentes al integrar SVR Laravel Core en un CRUD.

## El request redirige al inicio

La validación ocurre antes de entrar al controlador. Laravel devuelve una redirección cuando la petición no se reconoce como JSON.

1. Envía `Accept: application/json` y `Content-Type: application/json`.
2. Configura `shouldRenderJsonWhen()` para `api/*` como se explica en [Form Requests y Resources](requests-and-resources.md#errores-de-validación-en-apis).
3. Revisa la respuesta `422`; el controlador y su `try/catch` no reciben una petición cuyo Form Request falló.

## Un ID público falla como `integer`

El modelo propietario probablemente no declaró `publicIdMap()`:

```php
public function publicIdMap(): array
{
    return [
        'category_id' => [
            'model' => Category::class,
            'relation' => 'category',
            'many' => false,
        ],
    ];
}
```

`BaseFormRequest` usa el mapa antes de `rules()`. El cliente manda una cadena pública, pero las reglas reciben la llave interna y por eso deben conservar `integer` y `exists:categories,id`.

Si el mapa existe, comprueba que el ID público sea válido y visible bajo los scopes globales de `Category`.

## `show` o `update` no resuelve el modelo

El nombre del parámetro del método debe coincidir con el generado por la ruta:

```text
Ruta:       /products/{product}
Correcto:   show(Product $product)
Incorrecto: show(Product $item)
```

`HasPublicId` hace que el binding busque por la columna pública; no es necesario convertirla manualmente.

## Una relación o su FK desaparece

`BaseResource` solo transforma relaciones que ya están cargadas y cuyos modelos soportan IDs públicos:

```php
$product->load([
    'category:id,public_id,name',
    'created_by:id,public_id,email',
    'updated_by:id,public_id,email',
]);
```

Para modelos que no pueden extender `BaseModel`, como `User`, agrega el trait `HasPublicId`. Si una relación no se carga, su FK interna se elimina para evitar filtrarla.

Los identificadores externos como `tax_id` se conservan cuando no aparecen en `publicIdMap()` y no existe una relación Eloquent `tax()`. Si una FK interna se filtra como entero, declara su relación o agrégala a `publicIdMap()`.

## El mismo valor único falla durante la actualización

Una regla `unique` simple encuentra el propio registro. En el request de actualización ignora el modelo resuelto por la ruta:

```php
use Illuminate\Validation\Rule;

$product = $this->route('product');

Rule::unique('products', 'sku')->ignore($product);
```

Usa un request de actualización separado y reglas `sometimes` si aceptas cambios parciales. La función de guardado también debe asignar solamente las claves presentes para no reemplazar campos omitidos por `null`, `0` o `false`.

## El index expone IDs internos

No devuelvas modelos Eloquent directamente. Para colecciones usa:

```php
BaseResource::collection(Product::getItems($request));
```

Para un registro:

```php
new BaseResource($product);
```

Al responder después de crear, devuelve `$model->getRouteKey()` o aplica `BaseResource`; no uses `$model->id`.
