<?php

namespace SVR\LaravelCore\Models\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait HasAuditFields {
  protected static function bootHasAuditFields(): void {
    static::creating(function (Model $model) {

      if (!Auth::check()) {
        return;
      }

      $createdByColumn = $model->getCreatedByColumn();
      $updatedByColumn = $model->getUpdatedByColumn();

      /*
       * Si ya fueron asignados manualmente,
       * no los reemplazamos.
       */
      $model->{$createdByColumn} ??= Auth::id();
      $model->{$updatedByColumn} ??= Auth::id();
    });

    static::updating(function (Model $model) {

      if (!Auth::check()) {
        return;
      }

      $updatedByColumn = $model->getUpdatedByColumn();

      $model->{$updatedByColumn} = Auth::id();
    });
  }

  public function getCreatedByColumn(): string {
    return config(
      'svr-core.audit.created_by_column',
      'created_by_id'
    );
  }

  public function getUpdatedByColumn(): string {
    return config(
      'svr-core.audit.updated_by_column',
      'updated_by_id'
    );
  }
}