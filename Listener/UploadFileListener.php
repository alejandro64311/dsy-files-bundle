<?php

namespace dsarhoya\DSYFilesBundle\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use dsarhoya\DSYFilesBundle\Interfaces\IFileEnabledEntity;
use dsarhoya\DSYFilesBundle\Interfaces\IMultipleFileEnabledEntity;

class UploadFileListener
{
    /**
     * @var \dsarhoya\DSYFilesBundle\Services\DSYFilesService
     */
    private $fs;

    /**
     * @var Uploader\UploaderInterface
     */
    private $uploader;

    public function __construct($files_service, $storage_type)
    {
        $this->fs = $files_service;
        if ('S3' == strtoupper($storage_type)) {
            $this->uploader = new Uploader\S3Uploader($files_service);
        } elseif ('local' == strtolower($storage_type)) {
            $this->uploader = new Uploader\LocalUploader();
        } else {
            throw new \Exception(sprintf('Unkonwn storage type: %s', $storage_type));
        }
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $this->uploadFile($args);
    }

    public function preUpdate(LifecycleEventArgs $args)
    {
        $this->uploadFile($args);
    }

    /**
     * @return type
     *
     * @throws \Exception
     */
    private function uploadFile(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if ($entity instanceof IFileEnabledEntity) {
            if (is_null($entity->getFile())) {
                return;
            }
            $this->uploader->upload($entity->getFile(), $entity->getFilePath(), $entity->getFileKey(), $entity->getFileProperties());
        } elseif ($entity instanceof IMultipleFileEnabledEntity) {
            $files = $entity->getFiles();
            if (!is_array($files)) {
                throw new \Exception('IMultipleFileEnableEntity requires a parameter of type Array.');
            }
            foreach ($files as $file) {
                $this->uploader->upload($file['file'], $file['filePath'], $file['fileKey'], $file['fileProperties']);
            }
        }
    }

    public function preRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if ($entity instanceof IFileEnabledEntity) {
            $this->uploader->delete($entity->getFilePath(), $entity->getFileKey());
        } elseif ($entity instanceof IMultipleFileEnabledEntity) {
            $files = $entity->getFiles();
            if (!is_array($files)) {
                throw new \Exception('IMultipleFileEnableEntity requires a parameter of type Array.');
            }
            foreach ($files as $file) {
                $this->uploader->delete($file['filePath'], $file['fileKey']);
            }
        }
    }
}
