<?php

namespace dsarhoya\DSYFilesBundle\Interfaces;

interface IFileEnabledEntity
{
    public function getFile();

    public function getFileKey();

    public function getFilePath();

    public function getFileProperties();
}
