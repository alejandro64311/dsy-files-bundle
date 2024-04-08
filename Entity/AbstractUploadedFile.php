<?php

namespace dsarhoya\DSYFilesBundle\Entity;

/**
 * Description of AbstractUploadedImage.
 *
 * @author matias
 */
abstract class AbstractUploadedFile
{
    protected $localPath;
    protected $visibleUrl;
    protected $warnings;
    protected $errors;

    public function __construct()
    {
        $this->warnings = [];
        $this->errors = [];
    }

    public function getErrorsAsString()
    {
        return implode(', ', $this->errors);
    }

    public function getWarningsAsString()
    {
        return implode(', ', $this->warnings);
    }

    abstract public function getWarnings();

    abstract public function getErrors();

    public function setLocalPath($localPath)
    {
        return $this->localPath = $localPath;
    }

    public function setVisibleUrl($visibleUrl)
    {
        return $this->visibleUrl = $visibleUrl;
    }

    public function localPath()
    {
        return $this->localPath;
    }

    public function visibleUrl()
    {
        return $this->visibleUrl;
    }
}
