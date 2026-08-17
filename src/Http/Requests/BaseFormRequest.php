<?php

namespace SVR\LaravelCore\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class BaseFormRequest extends FormRequest {
  use ResolvesPublicIds;

  /**
   * Modelo al que pertenece esta operación.
   */
  abstract protected function modelClass(): string;

  /**
   * Prepara automáticamente todos los public_id
   * antes de ejecutar rules().
   */
  protected function prepareForValidation(): void {
    $this->resolvePublicIdsFor(
      $this->modelClass()
    );

    $this->afterPublicIdResolution();
  }

  /**
   * Hook opcional para que un Request pueda
   * preparar otros valores sin reemplazar
   * nuestra resolución de public_ids.
   */
  protected function afterPublicIdResolution(): void {
    //
  }
}