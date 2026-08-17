<?php

namespace SVR\LaravelCore;

use Illuminate\Support\ServiceProvider;
use SVR\LaravelCore\Database\BlueprintMacros;

class SvrLaravelCoreServiceProvider extends ServiceProvider {
  public function register(): void {
    $this->mergeConfigFrom(
      __DIR__ . '/../config/svr-core.php',
      'svr-core'
    );
  }

  public function boot(): void {
    BlueprintMacros::register();

    $this->publishes([
      __DIR__ . '/../config/svr-core.php' => config_path('svr-core.php'),
    ], 'svr-core-config');
  }
}
