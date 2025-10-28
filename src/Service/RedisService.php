<?php

namespace App\Service;

use Predis\Client;

class RedisService
{
  private static $instance;
  // 从环境变量中读取redis配置信息，每个项目.env中配置的DBINDEX不能相同
  public static function getCofing(): array
  {
    $config =  [
      "scheme" => "tcp",
      "host" => $_ENV['REDIS_HOST'],
      "port" => $_ENV['REDIS_PORT'],
      "password" => $_ENV['REDIS_PASSWORD'],
      "database" => $_ENV['REDIS_DBINDEX']
    ];
    return $config;
  }

  public static function getInstance()
  {
    if (!self::$instance) {
      self::$instance = new Client(self::getCofing());
    }
    return self::$instance;
  }
}
