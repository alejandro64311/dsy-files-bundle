<?php

namespace dsarhoya\DSYFilesBundle\Listener\Uploader;

/**
 * Description of S3Uploader.
 *
 * @author matias
 */
class LocalUploader extends AbstractUploader
{
    /**
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    private $fs;

    public function __construct()
    {
        $this->fs = new \Symfony\Component\Filesystem\Filesystem();
    }

    public function upload($file, $path, $key, $properties)
    {
        $this->delete($path, $key);
        $file->move($path, $key);
    }

    public function delete($path, $key)
    {
        $fullKey = $this->pathAndKey($path, $key);
        if (is_null($fullKey)) {
            return false;
        }

        if ($this->fs->exists($fullKey)) {
            $this->fs->remove($fullKey);
        }

        return true;
    }
}
