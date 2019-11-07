<?php

namespace Arhey\FaceDetection\Tests;

use PHPUnit\Framework\TestCase;
use Arhey\FaceDetection\FaceDetection;

class FaceDetectionTest extends TestCase 
{
  private $testFilePath;
  
  private $tmpFilePath;

  public function setUp(): void
  {
    $this->testFilePath = 'tests/Data/lena512color.jpg';
    $this->tmpFilePath = 'tests/Data/tmp/face.jpg';
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

    $face = $detector->extract($this->testFilePath);

    $this->assertEquals(true, $face->found);

    $this->assertEquals([
      'x' => 192.0,
      'y' => 192.0,
      'w' => 204.8,
      'h' => 204.8,
    ], $face->bounds);
  }

  public function testSaveFaceImage() 
  {
    $detector = new FaceDetection();

    $face = $detector->extract($this->testFilePath);

    $face->save($this->tmpFilePath);

    $this->assertFileExists($this->tmpFilePath);
  }
}
