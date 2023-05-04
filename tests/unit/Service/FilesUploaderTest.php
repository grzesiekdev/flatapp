<?php

namespace App\Tests\unit\Service;

use App\Service\FilesUploader;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class FilesUploaderTest extends KernelTestCase
{

    private FilesUploader $filesUploader;
    public function setUp(): void
    {
        $kernel = self::bootKernel();
        $container = static::getContainer();
        $this->filesUploader = $container->get(FilesUploader::class);
    }

    public function testGetSpecificTempPathForValidData()
    {
        $dir = '/uploads/flats/tmp/pictures';
        $userId = 1;

        $expected = '/uploads/flats/tmp/pictures/user1';
        $actual = $this->filesUploader->getSpecificTempPath($dir, $userId);

        $this->assertEquals($expected, $actual);
    }

    public function testGetSpecificTempPathForInvalidInput()
    {
        $dir = '/uploads/flats/tmp/pictures';
        $userId = null;

        $expected = '/uploads/flats/tmp/pictures/user';
        $actual = $this->filesUploader->getSpecificTempPath($dir, $userId);

        $this->assertEquals($expected, $actual);
    }

    public function testGetSpecificTempPathForEmptyDir()
    {
        $dir = '';
        $userId = 1;

        $expected = '/user1';
        $actual = $this->filesUploader->getSpecificTempPath($dir, $userId);

        $this->assertEquals($expected, $actual);
    }

    public function testGetSpecificTempPathForZeroUserId()
    {
        $dir = '/uploads/flats/tmp/pictures';
        $userId = 0;

        $expected = '/uploads/flats/tmp/pictures/user0';
        $actual = $this->filesUploader->getSpecificTempPath($dir, $userId);

        $this->assertEquals($expected, $actual);
    }

}