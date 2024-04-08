<?php

namespace dsarhoya\DSYFilesBundle\Listener\Uploader;

/**
 * Description of S3Uploader.
 *
 * @author matias
 */
class S3Uploader extends AbstractUploader
{
    /**
     * @var \dsarhoya\DSYFilesBundle\Services\DSYFilesService
     */
    private $fs;

    public function __construct($files_service)
    {
        $this->fs = $files_service;
    }

    public function upload($file, $path, $key, $properties)
    {
        $awsKey = $this->pathAndKey($path, $key);
        if ($this->fs->AWSFileWithFileAndKey($file, $awsKey, $properties)) {
            // Se subió bien..
        }
    }

    public function delete($path, $key)
    {
        $awsKey = $this->pathAndKey($path, $key);
        if ($this->fs->deleteAWSFile($awsKey)) {
            // Se eliminó bien..
        }
    }
}
