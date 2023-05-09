<?php

namespace App\Service;

use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use function PHPUnit\Framework\directoryExists;
use function PHPUnit\Framework\throwException;

class FilesUploader
{
    private SluggerInterface $slugger;

    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }

    public function getSpecificTempPath ($dir, $userId): string
    {
        $path = $dir . '/user' . $userId;
        if (str_ends_with($dir, '/')) {
            $path = $dir . 'user' . $userId;
        }

        return $path;
    }
    public function createTempDir ($path, $userId): string
    {
        $specificTempDirectory = $this->getSpecificTempPath($path, $userId);
        if (!file_exists($specificTempDirectory)) {
            mkdir($specificTempDirectory, 0755, true);
        }

        return $specificTempDirectory;
    }

    public function upload($file, $tempDirectory): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFileName = $this->slugger->slug($originalFilename);
        $newFileName = $safeFileName.'-'.uniqid().'.'.$file->guessExtension();

        try {
            $file->move(
                $tempDirectory,
                $newFileName
            );
        } catch (FileException $e) {
            throwException($e);
        }

        return $newFileName;
    }

    public function moveTempPictures($oldPath, $newPath, $pictures): void
    {
        foreach ($pictures as $picture) {
            rename($oldPath . '/' . $picture, $newPath . '/' . $picture);
        }
    }

    /**
     * @throws Exception
     */
    public function removeTempPictures($path): void
    {
        if (file_exists($path)) {
            $files = array_diff(scandir($path), array('.', '..'));
            foreach ($files as $file) {
                if(is_file($path . $file)) {
                    unlink($path . $file);
                }
            }
            if (is_dir($path)) {
                rmdir($path);
            }
        } else {
            throw new Exception('Path does not exist');
        }
    }

    public function deleteFile($file): int
    {
        try {
            unlink($file);
            $response = Response::HTTP_OK;
        } catch (Exception $e) {
            if (file_exists($file)) {
                $response = Response::HTTP_INTERNAL_SERVER_ERROR;
            } else {
                $response = Response::HTTP_NOT_FOUND;
            }
        }

        return $response;
    }
    public function getPictures($oldPath): array
    {
        if (is_dir($oldPath)) {
            $pictures = array_diff(scandir($oldPath), array('.', '..'));
            $newPath = str_replace('/tmp', '', $oldPath);

            if (!file_exists($newPath) && $newPath !== '') {
                mkdir($newPath, 0755, true);
            }

            $this->moveTempPictures($oldPath, $newPath, $pictures);
            $this->removeTempPictures($oldPath);
        } else {
            $pictures = [];
        }

        return $pictures;
    }

    public function getTempPictures($path): array
    {
        $pictures = [];
        if ($path !== null && is_dir($path)) {
            $pictures = array_diff(scandir($path), array('.', '..'));
            foreach ($pictures as $picture) {
                if (!file_exists($path . '/' . $picture)) {
                    $pictures = array_diff($pictures, [$picture]);
                } else {
                    $index = array_search($picture, $pictures);
                    $replacedPath = preg_replace('/(.*)\/public/', '', $path);
                    $pictures[$index] = $replacedPath . '/' . $picture;
                }
            }
        }

        return $pictures;
    }

    public function appendPath($pictures, $path): array
    {
        $path = str_replace('tmp/', '', $path);
        foreach ($pictures as $picture) {
            $index = array_search($picture, $pictures);
            $replacedPath = preg_replace('/(.*)\/public/', '', $path);
            $pictures[$index] = $replacedPath . '/' . $picture;
        }

        return $pictures;
    }

    /**
     * @throws Exception
     */
    public function getAgreement($path, $fileName): string
    {
        try {
            $agreements = array_diff(scandir($path), array('.', '..'));
        } catch (Exception $e) {
            throw new Exception('Path ' . $path . ' does not exist');
        }

        $index = array_search($fileName, $agreements);
        if ($index == '') {
            throw new Exception('File ' . $fileName . ' does not exist');
        } else {
            return $agreements[$index];
        }
    }

    public function getPreviousPictures($previousPictures, $path): array
    {
        $pictures = [];
        $newPath = str_replace('/tmp', '', $path);
        if (is_dir($newPath)) {
            $allPictures = array_diff(scandir($newPath), array('.', '..'));
            foreach ($previousPictures as $picture) {
                if (in_array($picture, $allPictures)) {
                    $pictures[] = $picture;
                }
            }
        }

        return $pictures;
    }
}