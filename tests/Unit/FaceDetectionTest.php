<?php

namespace Arhey\FaceDetection\Tests;

use PHPUnit\Framework\TestCase;
use Arhey\FaceDetection\FaceDetection;
use Intervention\Image\ImageManager;

class FaceDetectionTest extends TestCase 
{
  private $normalFilePath;
  
  private $tmpFilePath;

  public function setUp(): void
  {
    $this->normalFilePath = dirname(__FILE__).'/../Data/lena512color.jpg';
    $this->largeFilePath = dirname(__FILE__).'/../Data/lena1024color.jpg';
    $this->tmpFilePath = dirname(__FILE__).'/../Data/tmp/face.jpg';
  }

  public function tearDown(): void
  {
    if(file_exists($this->tmpFilePath)) {
      unlink($this->tmpFilePath);
    }
  }

  public function testDetectFace() 
  {
    $detector = new FaceDetection();

    $face = $detector->extract($this->normalFilePath);

    $this->assertEquals(true, $face->found);

    $this->assertEquals([
      'x' => 192.0,
      'y' => 192.0,
      'w' => 204.8,
      'h' => 204.8,
    ], $face->bounds);
  }

  public function testDetectFaceInLargeImage() 
  {
    $detector = new FaceDetection();

    $face = $detector->extract($this->largeFilePath);

    $this->assertEquals(true, $face->found);

    $this->assertEquals([
      'x' => 454.4,
      'y' => 268.8,
      'w' => 137.6,
      'h' => 137.6,
    ], $face->bounds);
  }

  public function testSaveFaceImage() 
  {
    $detector = new FaceDetection();

    $face = $detector->extract($this->normalFilePath);

    $face->save($this->tmpFilePath);

    $this->assertFileExists($this->tmpFilePath);
  }

  public function testSetDifferentDriver() 
  {
    $detector = new FaceDetection();

    $detector->driver = new ImageManager(['driver' => 'imagick']);

    $face = $detector->extract($this->normalFilePath);

    $this->assertEquals(true, $face->found);

    $this->assertEquals([
      'x' => 192.0,
      'y' => 192.0,
      'w' => 204.8,
      'h' => 204.8,
    ], $face->bounds);
  }
}
