<?php

namespace SVR\LaravelCore\Support;

class PublicIdGenerator {
  public function generate(?int $length = null): string {
    $length ??= config('svr-core.public_id.length', 10);

    $alphabet = config(
      'svr-core.public_id.alphabet',
      '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'
    );

    $max = strlen($alphabet) - 1;

    $publicId = '';

    for ($i = 0; $i < $length; $i++) {
      $publicId .= $alphabet[random_int(0, $max)];
    }

    return $publicId;
  }
}