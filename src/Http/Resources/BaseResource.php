<?php

namespace SVR\LaravelCore\Http\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use LogicException;

class BaseResource extends JsonResource {
  public function resolve($request = null): array {
    $data = parent::resolve($request);

    if (!$this->resource instanceof Model) {
      return $data;
    }

    $data = $this->replaceOwnId($data);

    $data = $this->removeInternalForeignKeys($data);

    $data = $this->removeLoadedRelations($data);

    $data = $this->replaceRelationIds($data);

    return $data;
  }

  /**
   * Campos terminados en _id que NO son IDs internos.
   *
   * Ejemplo:
   * provider_id de OpenPay.
   */
  protected function rawIdFields(): array {
    return [];
  }

  /**
   * Relaciones que explícitamente queramos conservar
   * serializadas en un Resource personalizado.
   */
  protected function preservedRelationFields(): array {
    return [];
  }

  protected function replaceOwnId(array $data): array {
    if (!method_exists($this->resource, 'getPublicIdColumn')) {
      return $data;
    }

    $column = $this->resource->getPublicIdColumn();

    $data['id'] = $this->resource->getAttribute($column);

    unset($data[$column]);

    return $data;
  }

  protected function removeInternalForeignKeys(array $data): array {
    $publicIdColumn = method_exists(
      $this->resource,
      'getPublicIdColumn'
    )
      ? $this->resource->getPublicIdColumn()
      : null;

    foreach ($this->resource->getAttributes() as $attribute => $value) {

      if (!str_ends_with($attribute, '_id')) {
        continue;
      }

      if ($attribute === $publicIdColumn) {
        continue;
      }

      if (in_array(
        $attribute,
        $this->rawIdFields(),
        true
      )) {
        continue;
      }

      unset($data[$attribute]);
    }

    return $data;
  }

  /**
   * Evita que una relación Eloquent cargada se filtre
   * directamente con sus IDs internos.
   */
  protected function removeLoadedRelations(array $data): array {
    foreach (
      array_keys($this->resource->getRelations())
      as $relationName
    ) {

      $serializedName = Str::snake($relationName);

      if (
        in_array(
          $serializedName,
          $this->preservedRelationFields(),
          true
        )
      ) {
        continue;
      }

      unset(
        $data[$relationName],
        $data[$serializedName]
      );
    }

    return $data;
  }

  /**
   * Convierte las FK configuradas en el modelo
   * a sus respectivos public_id.
   */
  protected function replaceRelationIds(array $data): array {
    if (!method_exists($this->resource, 'publicIdMap')) {
      return $data;
    }

    foreach (
      $this->resource->publicIdMap()
      as $outputKey => $definition
    ) {

      $relationName = $definition['relation'] ?? null;

      if (!$relationName) {
        throw new LogicException(
          "La relación {$outputKey} no tiene definido 'relation'."
        );
      }

      if (
        !$this->resource->relationLoaded(
          $relationName
        )
      ) {
        continue;
      }

      $related = $this->resource
        ->getRelation($relationName);

      $data[$outputKey] =
        $this->extractPublicId($related);
    }

    return $data;
  }

  protected function extractPublicId(mixed $related): mixed {
    if ($related === null) {
      return null;
    }

    if ($related instanceof Model) {
      return $this->getModelPublicId($related);
    }

    if ($related instanceof Collection) {
      return $related
        ->map(
          fn(Model $model) =>
          $this->getModelPublicId($model)
        )
        ->values()
        ->all();
    }

    throw new LogicException(
      'La relación configurada no es un modelo o colección válida.'
    );
  }

  protected function getModelPublicId(Model $model): string {
    if (
      !method_exists(
        $model,
        'getPublicIdColumn'
      )
    ) {
      throw new LogicException(
        sprintf(
          'El modelo %s no implementa public_id.',
            $model::class
        )
      );
    }

    $column = $model->getPublicIdColumn();

    return (string) $model->getAttribute($column);
  }
}