<?php

namespace App\Tests\unit\Service;

use App\Service\FilesUploader;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use function PHPUnit\Framework\assertEquals;

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

        $this->expectExceptionMessage('Path does not exist');
        $this->filesUploader->removeTempPictures($path);
    }

    public function testDeleteFileForValidFile()
    {
        $path = $this->parameterBag->get('test_images') . '/to_remove/';
        if (!file_exists($path)) {
            mkdir($path);
        }
        fopen($path . 'file1.txt', 'w');

        $excepted = 200;
        $actual = $this->filesUploader->deleteFile($path . 'file1.txt');

        $this->assertEquals($excepted, $actual);
    }

    public function testDeleteFileForNotExistingFile()
    {
        $path = $this->parameterBag->get('test_images') . '/to_remove/';
        if (!file_exists($path)) {
            mkdir($path);
        }

        $excepted = 404;
        $actual = $this->filesUploader->deleteFile($path . 'file1.txt');

        $this->assertEquals($excepted, $actual);
    }

    public function testDeleteFileForInvalidFile()
    {
        $path = $this->parameterBag->get('test_images') . '/to_remove/';
        if (!file_exists($path)) {
            mkdir($path);
        }

        $excepted = 500;
        $actual = $this->filesUploader->deleteFile($path);

        $this->assertEquals($excepted, $actual);
    }

    public function testGetPicturesForValidData()
    {
        $pictures = [];
        $path = $this->parameterBag->get('test_images') . '/tmp/';
        if (!file_exists($path)) {
            mkdir($path);
        }

        for ($i = 0; $i < 3; $i++) {
            copy($this->parameterBag->get('test_images') . '/picture' . $i . '.png', $path . 'picture' . $i . '.png');
            $file = new UploadedFile($path . 'picture' . $i . '.png', $path . 'picture' . $i . '.png', 'image/png', null, true);
            $pictures[] = $file->getClientOriginalName();
        }

        $expected = [
            2 => $pictures[0],
            3 => $pictures[1],
            4 => $pictures[2]
        ];
        $actual = $this->filesUploader->getPictures($this->parameterBag->get('test_images') . '/tmp/');
        $newPath = str_replace('/tmp', '', $path);

        $this->assertEquals($expected, $actual);
        $this->assertDirectoryDoesNotExist($path);
        $this->assertDirectoryExists($newPath);
    }

    public function testGetPicturesForInvalidPath()
    {
        $expected = [];
        $actual = $this->filesUploader->getPictures($this->parameterBag->get('test_images') . '/tmp1/');

        $this->assertEquals($expected, $actual);
    }

    public function testGetTempPicturesForValidData()
    {
        $pictures = [];
        $path = $this->parameterBag->get('test_images') . '/public';
        if (!file_exists($path)) {
            mkdir($path);
        }

        for ($i = 0; $i < 3; $i++) {
            copy($this->parameterBag->get('test_images') . '/picture' . $i . '.png', $path . '/picture' . $i . '.png');
            $file = new UploadedFile($path . '/picture' . $i . '.png', $path . '/picture' . $i . '.png', 'image/png', null, true);
            $pictures[] = '/' . $file->getClientOriginalName();
        }

        $expected = [
            2 => $pictures[0],
            3 => $pictures[1],
            4 => $pictures[2]
        ];
        $actual = $this->filesUploader->getTempPictures($path);

        $this->assertEquals($expected, $actual);
    }

    public function testGetTempPicturesForInvalidPath()
    {
        $expected = [];
        $actual = $this->filesUploader->getTempPictures($this->parameterBag->get('test_images') . '/tmp1/');

        $this->assertEquals($expected, $actual);
    }

    public function testGetTempPicturesForNumbersAsPath()
    {
        $expected = [];
        $actual = $this->filesUploader->getTempPictures(123);

        $this->assertEquals($expected, $actual);
    }

    public function testAppendPathForValidData()
    {
        $path = '/var/www/docker/public/uploads/flats/tmp/pictures/user8';
        $pictures = [
            "picture0.png",
            "picture1.png",
            "picture2.png",
        ];

        $expected = [
            "/uploads/flats/pictures/user8/picture0.png",
            "/uploads/flats/pictures/user8/picture1.png",
            "/uploads/flats/pictures/user8/picture2.png"
        ];

        $actual = $this->filesUploader->appendPath($pictures, $path);
        assertEquals($expected, $actual);
    }

    public function testAppendPathForEmptyPictures()
    {
        $path = '/var/www/docker/public/uploads/flats/tmp/pictures/user8';
        $pictures = [];

        $expected = [];

        $actual = $this->filesUploader->appendPath($pictures, $path);
        assertEquals($expected, $actual);
    }

    public function testAppendPathForEmptyPath()
    {
        $path = '';
        $pictures = [
            "picture0.png",
            "picture1.png",
            "picture2.png",
        ];

        $expected = [
            "/picture0.png",
            "/picture1.png",
            "/picture2.png",
        ];

        $actual = $this->filesUploader->appendPath($pictures, $path);
        assertEquals($expected, $actual);
    }

    public function testAppendPathForNoTmpInPath()
    {
        $path = '/var/www/docker/public/uploads/flats/pictures/user8';
        $pictures = [
            "picture0.png",
            "picture1.png",
            "picture2.png",
        ];

        $expected = [
            "/uploads/flats/pictures/user8/picture0.png",
            "/uploads/flats/pictures/user8/picture1.png",
            "/uploads/flats/pictures/user8/picture2.png"
        ];

        $actual = $this->filesUploader->appendPath($pictures, $path);
        assertEquals($expected, $actual);
    }

    public function testGetAgreementForValidData()
    {
        $path = $this->parameterBag->get('test_images');
        if (!file_exists($path . '/agreement.txt')) {
            fopen($path . '/agreement.txt', 'w');
        }

        $expected = 'agreement.txt';
        $actual = $this->filesUploader->getAgreement($path, 'agreement.txt');

        $this->assertEquals($expected, $actual);
    }

    public function testGetAgreementForEmptyPath()
    {
        $path = $this->parameterBag->get('test_images') . '/0';
        $this->expectExceptionMessage('Path ' . $path . ' does not exist');

        $this->filesUploader->getAgreement($path, 'agreement.txt');
    }

    public function testGetAgreementForNotExistingFile()
    {
        $path = $this->parameterBag->get('test_images') . '/public';
        $this->expectExceptionMessage('File agreement.txt does not exist');

        $this->filesUploader->getAgreement($path, 'agreement.txt');
    }

    public function testGetPreviousPicturesForValidData()
    {
        $path = $this->parameterBag->get('test_images') . '/public';
        $previousPictures = [
            'picture0.png',
            'picture1.png',
            'picture2.png',
        ];

        $expected = $previousPictures;
        $actual = $this->filesUploader->getPreviousPictures($previousPictures, $path);

        $this->assertEquals($expected, $actual);
    }

    public function testGetPreviousPicturesForTwoImages()
    {
        $path = $this->parameterBag->get('test_images') . '/public';
        $previousPictures = [
            'picture0.png',
            'picture1.png',
        ];

        $expected = $previousPictures;
        $actual = $this->filesUploader->getPreviousPictures($previousPictures, $path);

        $this->assertEquals($expected, $actual);
    }

    public function testGetPreviousPicturesForZeroImagess()
    {
        $path = $this->parameterBag->get('test_images') . '/public';
        $previousPictures = [];

        $expected = $previousPictures;
        $actual = $this->filesUploader->getPreviousPictures($previousPictures, $path);

        $this->assertEquals($expected, $actual);
    }

    public function testGetPreviousPicturesForInvalidPath()
    {
        $path = $this->parameterBag->get('test_images') . '/public/picture0.png';
        $previousPictures = [
            'picture0.png',
            'picture1.png',
        ];

        $expected = [];
        $actual = $this->filesUploader->getPreviousPictures($previousPictures, $path);

        $this->assertEquals($expected, $actual);
    }

    public function testGetPreviousPicturesForTooManyImages()
    {
        $path = $this->parameterBag->get('test_images') . '/public';
        $previousPictures = [
            'picture0.png',
            'picture1.png',
            'picture2.png',
            'picture3.png', // This file doesn't exist
        ];

        $expected = [
            'picture0.png',
            'picture1.png',
            'picture2.png',
        ];
        $actual = $this->filesUploader->getPreviousPictures($previousPictures, $path);

        $this->assertEquals($expected, $actual);
    }
}