<?php

namespace App\Service;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class OscService
{
  protected $S3Client;
  protected $bucket;

  public function __construct()
  {
    $this->bucket = $_ENV['MINIO_BUCKET'];
    if (!$this->bucket) {
      throw new \Exception('MINIO_BUCKET not set');
    }
    $this->S3Client = new S3Client([
      'credentials' => [
        'key' => $_ENV['MINIO_ACCESS_KEY'],
        'secret' => $_ENV['MINIO_SECRET_KEY']
      ],
      'use_path_style_endpoint' => true,
      'region' => 'cn-east',
      'version' => 'latest',
      'endpoint' => $_ENV['MINIO_ENDPOINT']
    ]);
  }
  public static function getInstance()
  {
    return new static();
  }

  public function setBucket(string $bucket)
  {
    $this->bucket = $bucket;
    return $this;
  }

  public function getS3Client()
  {
    return self::$S3Client;
  }

  /**
   * 存储文件
   * @param string $savePath
   * @param string $content
   * @return bool
   */
  public function putContent($content, string $savePath): ?string
  {
    try {
      $storageSavePath = $this->formatStorageSavePath($savePath);
      $this->S3Client->putObject([
        'Bucket' => $this->bucket,
        'Key'    => $storageSavePath,
        'Body'   => $content
      ]);
      return $storageSavePath;
    } catch (AwsException $awsException) {
      return $this->handleException($awsException);
    }
  }

  /**
   * （通过文件路径）上传对象（指定bucket）
   * @param string $localObjectPath 本地对象路径，支持相对和绝对路径
   * @param string|null $storageSavePath minio存储路径，自动创建文件夹，如test-dir/test-file.txt, 注意开头不能是 “/”, 回报错
   * @return string|bool 成功返回真实的$storageSavePath
   */
  public function putObject(string $localObjectPath, string $storageSavePath): ?string
  {
    try {
      if ($storageSavePath === null) {
        $storageSavePath = $localObjectPath;
      }
      $storageSavePath = $this->formatStorageSavePath($storageSavePath);

      // 下载文件的内容
      $result = $this->S3Client->putObject([
        'Bucket'     => $this->bucket,
        'Key'        => $storageSavePath,
        'SourceFile' => $localObjectPath,
      ]);
      Logger::log($result);
      return $storageSavePath;
    } catch (AwsException $awsException) {
      return $this->handleException($awsException);
    }
  }

  /**
   * 获取文件（指定bucket）
   * @param string $storageSavePath
   * @param string $localSaveAsPath
   * @return bool|mixed|\GuzzleHttp\Psr7\LazyOpenStream
   */
  public function getObject(string $storageSavePath, ?string $localSaveAsPath = null): mixed
  {
    try {
      $param = [
        'Bucket' => $this->bucket,
        'Key'    => $storageSavePath,
      ];
      if (!is_null($localSaveAsPath)) {
        $param = [
          'Bucket' => $this->bucket,
          'Key'    => $storageSavePath,
          'SaveAs' => $localSaveAsPath
        ];
      }
      // 下载文件的内容
      $result = $this->S3Client->getObject($param);
      return $result['Body']->getContents();
    } catch (AwsException $awsException) {
      return $this->handleException($awsException);
    }
  }

  public function getObjectUrl($storageSavePath): ?string
  {
    try {
      return $this->S3Client->getObjectUrl($this->bucket, $storageSavePath);
    } catch (AwsException $awsException) {
      return $this->handleException($awsException);
    }
  }
  public function removeObject($storageSavePath): bool
  {
    try {
      $storageSavePaths = (array) $storageSavePath;
      $this->S3Client->deleteObjects([
        'Bucket'  => $this->bucket,
        'Delete' => [
          'Objects' => array_map(function ($key) {
            return ['Key' => $key];
          }, $storageSavePaths)
        ],
      ]);
      return true;
    } catch (AwsException $awsException) {
      return $this->handleException($awsException);
    }
  }


  /**
   * 格式化处理, 去除前后的“/”
   * @param string $storageSavePath
   * @return string
   */
  private function formatStorageSavePath(string $storageSavePath)
  {
    return trim($storageSavePath, '/');
  }

  protected function handleException(AwsException $awsException): bool
  {
    if ($awsException->getResponse()) {
      Logger::error($awsException->getResponse()->getReasonPhrase());
    } else {
      Logger::error($awsException->getMessage());
    }
    return false;
  }
}
