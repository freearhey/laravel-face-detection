<?php

return [
 
  /*
    |--------------------------------------------------------------------------
    | Image Driver
    |--------------------------------------------------------------------------
    |
    | Face Detection supports "GD Library" and "Imagick" to process images
    | internally. You may choose one of them according to your PHP
    | configuration. By default PHP's "GD Library" implementation is used.
    |
    | Supported: "gd", "imagick"
    |
    */
  'driver' => 'gd',

  /*
    |--------------------------------------------------------------------------
    | Face Padding
    |--------------------------------------------------------------------------
    |
    | You can add padding around the face. By default, there
    | is no indentation.
    |
    */
  'padding_width' => 0,
  'padding_height' => 0,

];
