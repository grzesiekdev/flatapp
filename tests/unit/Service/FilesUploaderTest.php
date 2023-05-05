<?php

namespace App\Tests\unit\Service;

use App\Service\FilesUploader;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FilesUploaderTest extends KernelTestCase
{

    private FilesUploader $filesUploader;
    private ParameterBagInterface $parameterBag;
    public function setUp(): void
    {
        $kernel = self::bootKernel();
        $container = static::getContainer();

        $this->filesUploader = $container->get(FilesUploader::class);
        $this->parameterBag =  $container->get(ParameterBagInterface::class);
    }

    public function moveAndDeleteImage($file) : void
    {
        $newName = preg_replace('/-(.*)/', '', $file);
        copy($this->parameterBag->get('test_images') . '/uploaded/' . $file, $this->parameterBag->get('test_images') . '/' . $newName . '.png');
        unlink($this->parameterBag->get('test_images') . '/uploaded/' . $file);
    }

    public function testGetSpecificTempPathForValidInput()
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

    public function testGetSpecificTempPathForDirEndedWithSlash()
    {
        $dir = '/uploads/flats/tmp/pictures/';
        $userId = 0;

        $expected = '/uploads/flats/tmp/pictures/user0';
        $actual = $this->filesUploader->getSpecificTempPath($dir, $userId);

        $this->assertEquals($expected, $actual);
    }

    public function testCreateTempDirForValidInput()
    {
        $dir = '/uploads/flats/tmp/pictures/';
        $userId = 1;

        $expected = '/uploads/flats/tmp/pictures/user1';
        $actual = $this->filesUploader->getSpecificTempPath($dir, $userId);

        $this->assertEquals($expected, $actual);
    }

    public function testCreateTempDirForDirectoryExists()
    {
        $dir = $this->parameterBag->get('temp_pictures');
        $userId = 0;

        $actual = $this->filesUploader->createTempDir($dir, $userId);

        $this->assertDirectoryExists($actual);
        rmdir($actual);
    }

    public function testCreateTempDirForSettingPermissions()
    {
        $dir = $this->parameterBag->get('temp_pictures');
        $userId = 0;

        $actual = $this->filesUploader->createTempDir($dir, $userId);

        $this->assertEquals('755', decoct(fileperms($actual) & 0777));
        rmdir($actual);
    }

    public function testCreateTempDirForReturningString()
    {
        $dir = $this->parameterBag->get('temp_pictures');
        $userId = 0;

        $actual = $this->filesUploader->createTempDir($dir, $userId);

        $this->assertIsString($actual);
        rmdir($actual);
    }

    public function testUploadForNotEmptyString()
    {
        $file = new UploadedFile($this->parameterBag->get('test_images') . '/logo.png', 'logo.png', 'image/png', null, true);
        $actual = $this->filesUploader->upload($file, $this->parameterBag->get('test_images') . '/uploaded');

        $this->assertNotEmpty($actual);
        $this->moveAndDeleteImage($actual);
    }

    public function testUploadForCorrectFileName()
    {
        $file = new UploadedFile($this->parameterBag->get('test_images') . '/logo.png', 'logo.png', 'image/png', null, true);
        $actual = $this->filesUploader->upload($file, $this->parameterBag->get('test_images') . '/uploaded');

        $this->assertMatchesRegularExpression('/^[a-z0-9]+-[a-z0-9]+\.png$/', $actual);
        $this->moveAndDeleteImage($actual);
    }

    public function testMoveTempPicturesForValidData()
    {
        $pictures = [];
        for ($i = 0; $i < 3; $i++) {
            $file = new UploadedFile($this->parameterBag->get('test_images') . '/picture' . $i . '.png', $this->parameterBag->get('test_images') . '/picture' . $i . '.png', 'image/png', null, true);
            $pictures[] = $file->getClientOriginalName();
        }

        $oldPath = $this->parameterBag->get('test_images');
        $newPath = $this->parameterBag->get('test_images') . '/uploaded/';

        $this->filesUploader->moveTempPictures($oldPath, $newPath, $pictures);
        $actual = array_diff(scandir($newPath), array('.', '..'));
        $expected = [
            2 => $pictures[0],
            3 => $pictures[1],
            4 => $pictures[2]
        ];

        $this->assertEquals($expected, $actual);

        // moving files back to desired location, due to future tests
        $this->filesUploader->moveTempPictures($newPath, $oldPath, $pictures);
    }

    public function testMoveTempPicturesForEmptyArray()
    {
        $pictures = [];

        $oldPath = $this->parameterBag->get('test_images');
        $newPath = $this->parameterBag->get('test_images') . '/uploaded/';

        $this->filesUploader->moveTempPictures($oldPath, $newPath, $pictures);
        $actual = array_diff(scandir($newPath), array('.', '..'));
        $expected = [];

        $this->assertEquals($expected, $actual);
    }

    public function testRemoveTempPicturesForValidData()
    {
        $path = $this->parameterBag->get('test_images') . '/to_remove/';
        if (!file_exists($path)) {
            mkdir($path);
        }

        for ($i = 0; $i < 5; $i++) {
            fopen($path . 'file' . $i . '.txt', 'w');
        }

        $this->filesUploader->removeTempPictures($path);
        $this->assertDirectoryDoesNotExist($path);
        $this->assertFileDoesNotExist($path . 'file0.txt');
    }

    public function testRemoveTempPicturesForInvalidPath()
    {
        $path = $this->parameterBag->get('test_images') . '/to_remove_not_real/';

        $this->expectExceptionMessage('Path does not exists');
        $this->filesUploader->removeTempPictures($path);
    }

}