<?php

namespace SVR\LaravelCore\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use LogicException;

trait ResolvesPublicIds {
  protected function resolvePublicIdsFor(
    string $modelClass
  ): void {

    $model = $this->makeModel($modelClass);

    if (!method_exists($model, 'publicIdMap')) {
      return;
    }

    $resolved = [];

    foreach (
      $model->publicIdMap()
      as $field => $definition
    ) {

      if (!$this->exists($field)) {
        continue;
      }

      $value = $this->input($field);

      if ($value === null) {
        continue;
      }

      $relatedModelClass =
        $definition['model'] ?? null;

      if (!$relatedModelClass) {
        throw new LogicException(
          "El campo {$field} no tiene definido 'model'."
        );
      }

      $many = (bool) (
        $definition['many'] ?? false
      );

      $resolved[$field] = $many
        ? $this->resolveManyPublicIds(
          $field,
          $value,
          $relatedModelClass
        )
        : $this->resolveOnePublicId(
          $field,
          $value,
          $relatedModelClass
        );
    }

    if ($resolved !== []) {
      $this->merge($resolved);
    }
  }

  protected function resolveOnePublicId(
    string $field,
    mixed $value,
    string $modelClass
  ): mixed {

    $model = $this->makeModel($modelClass);

    $column = $model->getPublicIdColumn();

    /*
     * Importante:
     * usamos newQuery() y NO newQueryWithoutScopes().
     *
     * Así respetamos scopes globales del proyecto,
     * por ejemplo company/tenant.
     */
    $record = $model
      ->newQuery()
      ->where($column, $value)
      ->first();

    if (!$record) {
      throw ValidationException::withMessages([
        $field => [
          "El identificador enviado en {$field} no es válido.",
        ],
      ]);
    }

    return $record->getKey();
  }

  protected function resolveManyPublicIds(
    string $field,
    mixed $value,
    string $modelClass
  ): array {

    if (!is_array($value)) {
      throw ValidationException::withMessages([
        $field => [
          "El campo {$field} debe ser un arreglo.",
        ],
      ]);
    }

    if ($value === []) {
      return [];
    }

    $model = $this->makeModel($modelClass);

    $column = $model->getPublicIdColumn();

    $records = $model
      ->newQuery()
      ->whereIn($column, $value)
      ->get()
      ->keyBy($column);

    $ids = [];
    $missing = [];

    foreach ($value as $publicId) {

      $record = $records->get($publicId);

      if (!$record) {
        $missing[] = $publicId;
        continue;
      }

      $ids[] = $record->getKey();
    }

    if ($missing !== []) {
      throw ValidationException::withMessages([
        $field => [
          'Uno o más identificadores enviados no son válidos.',
        ],
      ]);
    }

    return $ids;
  }

  protected function makeModel(
    string $modelClass
  ): Model {

    if (
      !is_subclass_of(
        $modelClass,
        Model::class
      )
    ) {
      throw new LogicException(
        "{$modelClass} no es un modelo Eloquent válido."
      );
    }

    /** @var Model $model */
    $model = new $modelClass;

    if (
      !method_exists(
        $model,
        'getPublicIdColumn'
      )
    ) {
      throw new LogicException(
        "{$modelClass} no implementa soporte para public_id."
      );
    }

    return $model;
  }
}