<?php

namespace Arhey\FaceDetection\Facades;

use Illuminate\Support\Facades\Facade;

class FaceDetection extends Facade
{

  protected static function getFacadeAccessor()
  {
    return 'FaceDetection';
  }

}
