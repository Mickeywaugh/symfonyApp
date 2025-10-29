<?php

namespace App\Service;

use Monolog\Level;
use Monolog\Logger as Monologer;
use Monolog\Handler\StreamHandler;

class Logger
{
  public static function __callStatic(string $method, array $arguments): void
  {
    $logger = new Monologer('app');
    $logfile = $arguments[2] ?? "app";
    unset($arguments[2]);
    $logger->pushHandler(new StreamHandler(sprintf("%s/../../var/log/%s.log", __DIR__, $logfile), Level::Info));
    $logger->{$method}(...$arguments);
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

  /**
   * @param string $msg 日志信息
   * @param array $context 日志上下文
   * @param string $logFile 日志文件名
   */
  public static function log($msg, array $context = [], string $logFile = "app"): void
  {
    self::__callStatic("info", [$msg, $context, $logFile]);
  }

  /**
   * @param string $msg 日志信息
   * @param array $context 日志上下文
   * @param string $logFile 日志文件名
   */
  public static function error($msg, array $context = [], string $logFile = "app"): void
  {
    self::debugBackTrace($msg);
    self::__callStatic("error", [$msg, $context, $logFile]);
  }

  /**
   * @param string $msg 日志信息
   * @param array $context 日志上下文
   * @param string $logFile 日志文件名
   */
  public static function debug($msg, array $context = [], string $logFile = "app"): void
  {
    self::debugBackTrace($msg);
    self::__callStatic("debug", [$msg, $context, $logFile]);
  }

  /**
   * @param string $msg 日志信息
   * @param array $context 日志上下文
   * @param string $logFile 日志文件名
   */
  public static function critical($msg, array $context = [], string $logFile = "app"): void
  {
    self::debugBackTrace($msg);
    self::__callStatic("critical", [$msg, $context, $logFile]);
  }
}
