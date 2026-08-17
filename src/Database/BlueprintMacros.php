<?php

namespace SVR\LaravelCore\Database;

use Illuminate\Database\Schema\Blueprint;

class BlueprintMacros {
  public static function register(): void {
    self::registerPublicId();
    self::registerAuditFields();
  }

  protected static function registerPublicId(): void {
    Blueprint::macro('publicId', function () {

      /** @var Blueprint $this */

      $column = config(
        'svr-core.public_id.column',
        'public_id'
      );

      $length = config(
        'svr-core.public_id.length',
        10
      );

      $definition = $this->string($column, $length);

      $collation = config('svr-core.public_id.collation');

      if ($collation) {
        $definition->collation($collation);
      }

      return $definition->unique();
    });
  }

  protected static function registerAuditFields(): void {
    Blueprint::macro('auditFields', function (bool $nullable = false) {

      /** @var Blueprint $this */

      $createdByColumn = config(
        'svr-core.audit.created_by_column',
        'created_by_id'
      );

      $updatedByColumn = config(
        'svr-core.audit.updated_by_column',
        'updated_by_id'
      );

      $usersTable = config(
        'svr-core.audit.users_table',
        'users'
      );

      $createdBy = $this->foreignId($createdByColumn);

      $updatedBy = $this->foreignId($updatedByColumn);

      if ($nullable) {
        $createdBy->nullable();
        $updatedBy->nullable();
      }

      $createdBy
        ->constrained($usersTable)
        ->restrictOnDelete();

      $updatedBy
        ->constrained($usersTable)
        ->restrictOnDelete();

      return $this;
    });
  }
}
