<?php

namespace SVR\LaravelCore\Models\Traits;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;
use SVR\LaravelCore\Support\PublicIdGenerator;

trait HasPublicId {
  protected static function bootHasPublicId(): void {
    static::creating(function (Model $model) {

      $column = $model->getPublicIdColumn();

      // Si ya viene asignado, no hacemos nada.
      if (!empty($model->getAttribute($column))) {
        return;
      }

      $generator = app(PublicIdGenerator::class);

      for ($attempt = 0; $attempt < 10; $attempt++) {

        $publicId = $generator->generate();

        $exists = $model
          ->newQueryWithoutScopes()
          ->where($column, $publicId)
          ->exists();

        if (!$exists) {
          $model->setAttribute($column, $publicId);

          return;
        }
      }

      throw new RuntimeException(
        'No fue posible generar un public_id único.'
      );
    });

    /*
     * Un public_id nunca debe modificarse
     * después de haber sido creado.
     */
    static::updating(function (Model $model) {

      $column = $model->getPublicIdColumn();

      if ($model->isDirty($column)) {
        throw new RuntimeException(
          "El campo {$column} no puede modificarse."
        );
      }
    });
  }

  public function getPublicIdColumn(): string {
    return config(
      'svr-core.public_id.column',
      'public_id'
    );
  }

  public function getRouteKeyName(): string {
    return $this->getPublicIdColumn();
  }
  
  /**
   * Define las relaciones que utilizan public_id
   * en la frontera de la API.
   */
  public function publicIdMap(): array {
    return [];
  }
}