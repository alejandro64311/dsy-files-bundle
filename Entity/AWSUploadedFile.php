<?php

namespace dsarhoya\DSYFilesBundle\Entity;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Description of AWSUploadedImage.
 *
 * @author matias
 */
class AWSUploadedFile extends AbstractUploadedFile
{
    /**
     * @var string
     */
    private $awsKey;

    /**
     * Constructor.
     *
     * @param string $localPath
     * @param string $awsKey
     */
    public function __construct($localPath = null, $awsKey = null)
    {
        parent::__construct();
        if ($localPath) {
            $this->localPath = $localPath;
        }
        if ($awsKey) {
            $this->awsKey = $awsKey;
        }
    }

    public static function constructWithFile(UploadedFile $path)
    {
        throw new \Exception('Not implemented ... yet.');

        return $path;
    }

    /**
     * constructWithPath function.
     *
     * @param string $path
     *
     * @return self
     */
    public static function constructWithPath($path)
    {
        if (!is_string($path)) {
            throw new \Exception('Path needs to be string type');
        }

        return new self($path, null);
    }

    /**
     * constructWithAwsKey function.
     *
     * @param string $awsKey
     *
     * @return self
     */
    public static function constructWithAwsKey($awsKey)
    {
        if (!is_string($awsKey)) {
            throw new \Exception('AWS key needs to be string type');
        }

        return new self(null, $awsKey);
    }

    public function getErrors()
    {
        // custom implementation
    }

    public function getWarnings()
    {
        // custom implementation
    }

    /**
     * @return string
     */
    public function awsKey()
    {
        return $this->awsKey;
    }
}
