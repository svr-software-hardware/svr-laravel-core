<?php

return [

  'public_id' => [

    'column' => 'public_id',

    'length' => 10,

    'alphabet' => '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz',

    'collation' => 'utf8mb4_bin',

  ],

  'audit' => [

    'created_by_column' => 'created_by_id',

    'updated_by_column' => 'updated_by_id',

    'users_table' => 'users',

  ],

];
