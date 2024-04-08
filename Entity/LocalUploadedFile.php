<?php

namespace dsarhoya\DSYFilesBundle\Entity;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Description of AWSUploadedImage.
 *
 * @author matias
 */
class LocalUploadedFile extends AbstractUploadedFile
{
    public function __construct($localPath)
    {
        parent::__construct();
        if ($localPath) {
            $this->localPath = $localPath;
        }
    }

    public static function constructWithFile(UploadedFile $path)
    {
        throw new \Exception('Not implemented ... yet.');

        return $path;
    }

    public static function constructWithPath($path)
    {
        if (!is_string($path)) {
            throw new \Exception('Path needs to be string type');
        }

        $instance = new self($path, null);

        return $instance;
    }

    public function getErrors()
    {
        // custom implementation
    }

    public function getWarnings()
    {
        // custom implementation
    }
}
