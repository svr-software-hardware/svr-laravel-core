<?php

namespace SVR\LaravelCore\Models;

use SVR\LaravelCore\Models\Traits\HasAuditFields;

abstract class AuditableModel extends BaseModel {
  use HasAuditFields;
}