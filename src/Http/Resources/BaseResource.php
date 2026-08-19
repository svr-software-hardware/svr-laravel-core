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

    $data = $this->transformLoadedRelations(
      $data,
      $request
    );

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
   * Relaciones sin soporte para public_id que
   * explícitamente queramos conservar sin transformar.
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

  protected function transformLoadedRelations(
    array $data,
    mixed $request = null
  ): array {
    foreach (
      $this->resource->getRelations()
      as $relationName => $related
    ) {

      $serializedName = Str::snake($relationName);

      if ($this->canTransformRelation($related)) {
        $data[$serializedName] =
          $this->transformRelation(
            $related,
            $request
          );

        $data = $this->replaceInferredForeignKey(
          $data,
          $serializedName,
          $related
        );

        continue;
      }

      if (!in_array(
        $serializedName,
        $this->preservedRelationFields(),
        true
      )) {
        unset(
          $data[$relationName],
          $data[$serializedName]
        );
      }
    }

    return $data;
  }

  protected function canTransformRelation(
    mixed $related
  ): bool {
    if ($related === null) {
      return true;
    }

    if ($related instanceof Model) {
      return method_exists(
        $related,
        'getPublicIdColumn'
      );
    }

    if ($related instanceof Collection) {
      return $related->every(
        fn(mixed $model) =>
        $model instanceof Model
          && method_exists(
            $model,
            'getPublicIdColumn'
          )
      );
    }

    return false;
  }

  protected function transformRelation(
    mixed $related,
    mixed $request = null
  ): mixed {
    if ($related === null) {
      return null;
    }

    if ($related instanceof Model) {
      return (new self($related))->resolve($request);
    }

    return $related
      ->map(
        fn(Model $model) =>
        (new self($model))->resolve($request)
      )
      ->values()
      ->all();
  }

  protected function replaceInferredForeignKey(
    array $data,
    string $relationName,
    mixed $related
  ): array {
    $foreignKey = "{$relationName}_id";

    if (!$this->resource->offsetExists($foreignKey)) {
      return $data;
    }

    if ($related === null) {
      $data[$foreignKey] = null;

      return $data;
    }

    if ($related instanceof Model) {
      $data[$foreignKey] =
        $this->getModelPublicId($related);
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
