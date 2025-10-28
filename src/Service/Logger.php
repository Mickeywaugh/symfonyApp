<?php

namespace App\Service;

use Monolog\Level;
use Monolog\Logger as Monologer;
use Monolog\Handler\StreamHandler;

class Logger
{
  public static function __callStatic(string $method, array $arguments)
  {
    $logger = new Monologer('app');
    $logfile = $arguments[2] ?? "log";
    unset($arguments[2]);
    $logger->pushHandler(new StreamHandler(sprintf("%s/../../var/log/%s.log", __DIR__, $logfile), Level::Info));
    return $logger->{$method}(...$arguments);
  }

  private static function debugBackTrace(string &$msg): void
  {
    // 获取调用者信息
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    $callerInfo = [];
    foreach ($backtrace as $index => $frame) {
      $callerInfo[] = sprintf(
        "File %d: %s:%d %s()",
        $index,
        basename($frame['file']),
        $frame['line'],
        $frame['function'] ?? 'main'
      );
    }
    $callerInfo = implode(" -> ", $callerInfo);
    $msg = sprintf("%s=>%s", $msg, $callerInfo);
  }

  public static function log($msg, array $context = [], string $logFile = "log"): void
  {
    self::__callStatic("log", [$msg, $context, $logFile]);
  }

  public static function error($msg, array $context = [], string $logFile = "log"): void
  {
    self::debugBackTrace($msg);
    self::__callStatic("error", [$msg, $context, $logFile]);
  }

  public static function debug($msg, array $context = [], string $logFile = "log"): void
  {
    self::debugBackTrace($msg);
    self::__callStatic("debug", [$msg, $context, $logFile]);
  }

  public static function critical($msg, array $context = [], string $logFile = "log"): void
  {
    self::debugBackTrace($msg);
    self::__callStatic("critical", [$msg, $context, $logFile]);
  }
}
