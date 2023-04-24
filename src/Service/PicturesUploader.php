<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class PicturesUploader
{
    private SluggerInterface $slugger;

    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }

    public function createSpecificTempPath ($dir, $userId): string
    {
        return $dir . '/user' . $userId;
    }
    public function createTempDir ($path, $userId): string
    {
        $specificTempDirectory = $this->createSpecificTempPath($path, $userId);
        if (!file_exists($specificTempDirectory)) {
            mkdir($specificTempDirectory, 0777, true);
        }

        return $specificTempDirectory;
    }

    public function upload($picture, $tempDirectory): void
    {
        $originalFilename = pathinfo($picture->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFileName = $this->slugger->slug($originalFilename);
        $newFileName = $safeFileName.'-'.uniqid().'.'.$picture->guessExtension();

        try {
            $picture->move(
                $tempDirectory,
                $newFileName
            );
        } catch (FileException $e) {
            dd($e);
        }
    }

    public function moveTempPictures($oldPath, $newPath, $pictures): void
    {
        foreach ($pictures as $picture) {
            rename($oldPath . '/' . $picture, $newPath . '/' . $picture);
        }
    }
    public function removeTempPictures($path): void
    {
        $files = glob($path);
        foreach ($files as $file) {
            if(is_file($file)) {
                unlink($file);
            }
        }
        rmdir($path);
    }
    public function getPictures($oldPath): array
    {
        $pictures = array_diff(scandir($oldPath), array('.', '..'));
        $newPath = str_replace('/tmp', '', $oldPath);
        if (!file_exists($newPath)) {
            mkdir($newPath, 0755, true);
        }
        $this->moveTempPictures($oldPath, $newPath, $pictures);
        $this->removeTempPictures($oldPath);
        return $pictures;
    }
}