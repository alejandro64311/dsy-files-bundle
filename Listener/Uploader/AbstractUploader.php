<?php

namespace dsarhoya\DSYFilesBundle\Listener\Uploader;

abstract class AbstractUploader
{
    abstract public function upload($file, $path, $key, $properties);

    abstract public function delete($path, $key);

    protected function pathAndKey($path, $key)
    {
        $fullKey = '';
        if (!is_null($path)) {
            $fullKey .= $path.'/';
        }
        $fullKey .= $key;

        return $fullKey;
    }
}
