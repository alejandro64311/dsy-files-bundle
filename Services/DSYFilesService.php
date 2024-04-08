<?php

namespace dsarhoya\DSYFilesBundle\Services;

use Aws\CommandInterface;
use Aws\Result;
use Aws\S3\S3Client;
use dsarhoya\DSYFilesBundle\Entity\AWSUploadedFile;
use dsarhoya\DSYFilesBundle\Entity\LocalUploadedFile;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Description of FilesService.
 *
 * @author matias
 */
class DSYFilesService
{
    public const ACL_PRIVATE = 'private';
    public const ACL_PUBLIC_READ = 'public-read';
    public const ACL_PUBLIC_READ_WRITE = 'public-read-write';
    public const ACL_AUTHENTICATED_READ = 'authenticated-read';
    public const ACL_BUCKET_OWNER_READ = 'bucket-owner-read';
    public const ACL_BUCKET_OWNER_FULL_CONTROL = 'bucket-owner-full-control';

    /**
     * @var array
     */
    private $aws_credentials;

    /**
     * @var array
     */
    private $aws_named_credentials;

    /**
     * @var array
     */
    private $used_credentials;

    /**
     * @var string
     */
    private $cache_dir;

    /**
     * @var string
     */
    private $base_url;

    /**
     * @var string
     */
    private $storage_type;

    /**
     * @var array
     */
    private $errors;

    /**
     * @var string
     */
    private $local_upload_folder = '';

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * Constructor.
     *
     * @param array        $aws_credentials
     * @param array        $aws_named_credentials
     * @param string       $cache_dir
     * @param string       $storage_type
     * @param RequestStack $request_stack
     */
    public function __construct($aws_credentials, $aws_named_credentials, $cache_dir, $storage_type, $request_stack)
    {
        $this->aws_credentials = $aws_credentials;
        $this->aws_named_credentials = $aws_named_credentials;
        $this->cache_dir = $cache_dir;
        $this->errors = [];
        $this->base_url = null;
        $this->storage_type = $storage_type;
        $this->requestStack = $request_stack;

        if ($this->aws_credentials) {
            $this->used_credentials = $this->aws_credentials;
        } elseif ($this->aws_named_credentials) {
            $credential_keys = array_keys($this->aws_named_credentials);
            $first_credentials_name = $credential_keys[0];
            $this->used_credentials = $this->aws_named_credentials[$first_credentials_name];
        }
    }

    /**
     * @param array|null $credentials
     *
     * @return S3Client
     */
    public function getS3Client($credentials = null)
    {
        if (is_null($credentials)) {
            $credentials = $this->used_credentials;
        }

        $arguments = [
            'version' => 'latest',
            'region' => $credentials['region'],
        ];

        if (isset($credentials['key']) && isset($credentials['secret'])) {
            $arguments['credentials'] = [
                'key' => $credentials['key'],
                'secret' => $credentials['secret'],
            ];
        }

        $client = new S3Client($arguments);

        return $client;
    }

    /**
     * getCredencials.
     *
     * @param string|null $credentials_name
     *
     * @return array
     */
    public function getCredencials($credentials_name = null)
    {
        return null === $credentials_name ? $this->used_credentials : $this->aws_named_credentials[$credentials_name];
    }

    /**
     * useNamedCredentials.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function useNamedCredentials($name)
    {
        $this->used_credentials = $this->aws_named_credentials[$name];

        return $this;
    }

    /**
     * fileWithAWSKey function.
     *
     * @param string $aws_key
     *
     * @return AWSUploadedFile
     */
    public function fileWithAWSKey($aws_key)
    {
        return $this->AWSFileWithAWSKey($aws_key);
    }

    /**
     * AWSFileWithAWSKey function.
     *
     * @param string $aws_key
     *
     * @return AWSUploadedFile
     */
    public function AWSFileWithAWSKey($aws_key)
    {
        /** @var AWSUploadedFile $image */
        $image = AWSUploadedFile::constructWithAwsKey($aws_key);

        $client = $this->getS3Client();
        $path = $this->cache_dir.'/'.md5($aws_key);
        $client->getObject([
            'Bucket' => $this->used_credentials['bucket'],
            'Key' => $aws_key,
            'SaveAs' => $path,
        ]);

        $image->setLocalPath($path);

        return $image;
    }

    /**
     * AWSFileWithFileAndKey function.
     *
     * @param UploadedFile $file
     * @param string       $aws_key
     * @param array|null   $options
     *
     * @return AWSUploadedFile|false
     */
    public function AWSFileWithFileAndKey($file, $aws_key, $options = null)
    {
        $putArray = [
            'Bucket' => $this->used_credentials['bucket'],
            'Key' => $aws_key,
            'SourceFile' => $file->getPathName(),
            'ACL' => isset($options['ACL']) ? $options['ACL'] : 'private',
            'ContentType' => $file->getMimeType(),
        ];

        try {
            $client = $this->getS3Client();
            $client->putObject($putArray);
        } catch (\Aws\S3\Exception\S3Exception $e) {
            $this->errors[] = $e->getMessage();

            return false;
        }

        $image = AWSUploadedFile::constructWithAwsKey($aws_key);
        $image->setLocalPath($file->getPathName());

        return $image;
    }

    /**
     * @param string $local_path
     *
     * @return LocalUploadedFile
     */
    public function LocalFileWithPath($local_path)
    {
        $local_file = LocalUploadedFile::constructWithPath($local_path);
        if ($this->base_url) {
            $local_file->setVisibleUrl('/'.$this->assetUrl($local_path));
        }

        return $local_file;
    }

    /**
     * @param UploadedFile $file
     * @param string       $local_path
     *
     * @return LocalUploadedFile|false
     */
    public function LocalFileWithFileAndPath($file, $local_path)
    {
        $full_path = $this->constructFullPath($local_path);

        if (!$full_path) {
            return false;
        }

        $full_path_array = explode('/', $full_path);

        $name = array_pop($full_path_array);
        $path = implode('/', $full_path_array);

        if (!$this->createPath($path)) {
            return false;
        }

        $file->move($path, $name);

        $local_file = LocalUploadedFile::constructWithPath($full_path);
        if ($this->base_url) {
            $local_file->setVisibleUrl($this->assetUrl('/'.$full_path));
        }

        return $local_file;
    }

    /**
     * @param string $path
     *
     * @return string
     */
    private function constructFullPath($path)
    {
        $base_path = '';
        if ($this->local_upload_folder && '' != $this->local_upload_folder) {
            $base_path = $this->local_upload_folder.'/';
        }

        return $this->cleanPath($base_path.$this->cleanPath($path));
    }

    /**
     * @param string $path
     *
     * @return string
     */
    private function cleanPath($path)
    {
        $patrón = '/^(\/*)(.*)$/';
        $coincidencias = [];
        preg_match($patrón, $path, $coincidencias);

        return $coincidencias[2];
    }

    /**
     * @param string $path
     *
     * @return bool
     */
    private function createPath($path)
    {
        $clean_path = explode('/', $this->cleanPath($path));

        $fs = new Filesystem();
        $current_path = '.';
        foreach ($clean_path as $path_item) {
            $current_path .= '/'.$path_item;
            if (!$fs->exists($current_path)) {
                try {
                    $fs->mkdir($current_path);
                } catch (\Exception $e) {
                    $this->errors[] = $e->getMessage();
                    $this->errors[] = sprintf('The path was: %s', $this->cleanPath($path));

                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param string $path
     *
     * @return string
     */
    public function assetUrl($path)
    {
        if (!$this->base_url) {
            throw new \Exception('Set the base url before trying to get the asset url');
        }

        return $this->base_url.$path;
    }

    /**
     * Description Elimina una imagen dependiendo de la configuración global de config.yml de la variable store_type.
     *
     * @param type $file
     */
    public function deleteFile($file)
    {
        if ('s3' === $this->storage_type) {
            return $this->deleteAWSFile($file);
        } else {
            return $this->deleteLocalFile($file);
        }
    }

    /**
     * Description Elimina una imagen dependiendo de la configuración global de config.yml de la variable store_type.
     *
     * @param string     $file
     * @param array|null $options
     *
     * @return string
     *
     * @throws \Exception
     */
    public function fileUrl($file, $options = null)
    {
        if ('S3' == strtoupper($this->storage_type)) {
            if (isset($options['signed']) && $options['signed']) {
                return $this->getSignedUrl($file, isset($options['duration']) ? $options['duration'] : null, isset($options['named_credentials']) ? $options['named_credentials'] : null);
            } else {
                return $this->getObjectUrl($file, isset($options['named_credentials']) ? $options['named_credentials'] : null);
            }
        } elseif ('local' == strtolower($this->storage_type)) {
            if ('/' !== $file[0]) {
                $file = '/'.$file;
            }

            $request = $this->requestStack->getMasterRequest();
            if (isset($options['absolute']) && $options['absolute']) {
                return "{$request->getScheme()}://{$request->getHost()}{$request->getBasePath()}{$file}";
            } else {
                return "{$request->getBasePath()}{$file}";
            }
        } else {
            throw new \Exception(sprintf('Unkonwn storage type: %s', $this->storage_type));
        }
    }

    /**
     * @param string|LocalUploadedFile $file
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function deleteLocalFile($file)
    {
        $path = null;

        if (is_string($file)) {
            $path = $file;
        } else {
            if (!$file instanceof LocalUploadedFile) {
                throw new \Exception('Esta función es para borrar archivos locales');
            }

            $path = $file->localPath();
        }

        try {
            unlink($path);

            return true;
        } catch (\Exception $e) {
            $this->errors[] = 'Ocurrió un error al borrar el archivo';

            return false;
        }
    }

    /**
     * @param string|AWSUploadedFile $file
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function deleteAWSFile($file)
    {
        $key = null;

        if (is_string($file)) {
            $key = $file;
        } else {
            if (!$file instanceof AWSUploadedFile) {
                throw new \Exception('Esta función es para borrar archivos en AWS');
            }
            $key = $file->awsKey();
        }

        $client = $this->getS3Client();
        try {
            $client->deleteObject([
                'Bucket' => $this->used_credentials['bucket'],
                'Key' => $key,
            ]);
        } catch (\Aws\S3\Exception\S3Exception $ex) {
            $this->errors[] = $ex->getMessage();

            return false;
        }

        return true;
    }

    /**
     * getErrorsAsString function.
     *
     * @return string
     */
    public function getErrorsAsString()
    {
        return implode(', ', $this->errors);
    }

    /**
     * setBaseFolder function.
     *
     * @param string $base_folder
     *
     * @return self
     */
    public function setBaseFolder($base_folder)
    {
        $this->local_upload_folder = $base_folder;

        return $this;
    }

    /**
     * Get Service.
     *
     * @param string $aws_service
     *
     * @return mixed
     *
     * @throws \Exception No hay servicios con ese nombre
     */
    public function get($aws_service)
    {
        switch (strtoupper($aws_service)) {
            case 'S3':
                return $this->getS3Client();
            default:
                throw new \Exception('No hay servicios con ese nombre.');
        }
    }

    /**
     * setBaseUrl function.
     *
     * @param string $base_url
     *
     * @return $this
     */
    public function setBaseUrl($base_url)
    {
        $this->base_url = $base_url;

        return $this;
    }

    /**
     * getSignedUrl function.
     *
     * @param string      $key
     * @param string|null $duration
     * @param string|null $credentials_name
     *
     * @return string
     */
    public function getSignedUrl($key, $duration = null, $credentials_name = null)
    {
        $credentials = is_null($credentials_name) ? $this->used_credentials : $this->aws_named_credentials[$credentials_name];
        $client = $this->getS3Client($credentials);
        /** @var CommandInterface $cmd */
        $cmd = $client->getCommand('GetObject', [
            'Bucket' => $credentials['bucket'],
            'Key' => $key,
        ]);

        $request = $client->createPresignedRequest($cmd, is_null($duration) ? '+10 minutes' : $duration);

        return (string) $request->getUri();
    }

    /**
     * getObjectUrl function.
     *
     * @param string      $key
     * @param string|null $credentials_name
     *
     * @return string
     */
    public function getObjectUrl($key, $credentials_name = null)
    {
        $credentials = is_null($credentials_name) ? $this->used_credentials : $this->aws_named_credentials[$credentials_name];
        $client = $this->getS3Client($credentials);

        return $client->getObjectUrl($credentials['bucket'], $key);
    }

    /**
     * setObjectACL.
     *
     * @param string      $key
     * @param string      $acl
     * @param string|null $credentials_name
     *
     * @return bool
     */
    public function setObjectACL($key, $acl, $credentials_name = null)
    {
        $credentials = is_null($credentials_name) ? $this->used_credentials : $this->aws_named_credentials[$credentials_name];
        $client = $this->getS3Client($credentials);
        try {
            $client->putObjectAcl([
                'ACL' => $acl,
                'Bucket' => $credentials['bucket'],
                'Key' => $key,
            ]);

            return true;
        } catch (\Aws\S3\Exception\S3Exception $ex) {
            $this->errors[] = $ex->getMessage();

            return false;
        }
    }

    /**
     * getHeadObject.
     *
     * @param string      $key
     * @param string|null $credentials_name
     *
     * @return Result|false
     */
    public function getHeadObject($key, $credentials_name = null)
    {
        $credentials = $this->getCredencials($credentials_name);
        $client = $this->getS3Client($credentials);
        try {
            $head = $client->headObject([
                'Bucket' => $credentials['bucket'],
                'Key' => $key,
            ]);
        } catch (\Aws\S3\Exception\S3Exception $ex) {
            $this->errors[] = $ex->getMessage();

            return false;
        }

        return $head;
    }
}
