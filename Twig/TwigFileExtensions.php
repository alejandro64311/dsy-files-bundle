<?php

namespace dsarhoya\DSYFilesBundle\Twig;

use dsarhoya\DSYFilesBundle\Services\DSYFilesService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TwigFileExtensions extends AbstractExtension
{
    private $files_service;
    private $storage_type;
    private $filesSrv;

    public function __construct(DSYFilesService $filesSrv)
    {
        $this->filesSrv = $filesSrv;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('generateAWSSignedUrl', [$this->filesSrv, 'getSignedUrl']),
            new TwigFunction('generateAWSUrl', [$this->filesSrv, 'getObjectUrl']),
            new TwigFunction('fileUrl', [$this->filesSrv, 'fileUrl']),
        ];
    }

    public function getName()
    {
        return 'dsy-files-twig-utils';
    }
}
