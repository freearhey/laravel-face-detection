# Face Detection [![Build Status](https://travis-ci.org/freearhey/laravel-face-detection.svg?branch=master)](https://travis-ci.org/freearhey/laravel-face-detection)

A Laravel Package for Face Detection.

## Installation

1. Install the package via composer:

```sh
composer require freearhey/laravel-face-detection
```

2. Add the service provider to the `config/app.php` file in Laravel:

```php
'providers' => [
  ...
  'Arhey\FaceDetection\FaceDetectionServiceProvider', 
  ...
],
```

3. Add an alias for the Facade to the `config/app.php` file in Laravel:

```php
'aliases' => [
  ...
  'FaceDetection' => 'Arhey\FaceDetection\Facades\FaceDetection', 
  ...
],
```

4. Publish the config file by running:

```sh
php artisan vendor:publish
```

## Usage

```php
use FaceDetection;

$face = FaceDetection::extract('path/to/image.jpg');
```

To detect if face is found in a image:

```php
if($face->found) {
  // face found
} else {
  // face not found
}
```

To get the facial boundaries:

```php
var_dump($face->bounds);

/*
  array(
    'x' => 292.0,
    'y' => 167.0,
    'w' => 204.8,
    'h' => 204.8,
  )
*/
```

To save the found face image:

```php
$face->save('path/to/output.jpg');
```

## Testing

```sh
vendor/bin/phpunit
```

## License

[MIT](LICENSE)
