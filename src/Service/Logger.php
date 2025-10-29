<?php

namespace App\Service;

use Monolog\Level;
use Monolog\Logger as Monologer;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

class Logger
{
  public static function __callStatic(string $method, array $arguments): void
  {
    $channel = $arguments[2] ?? "app";
    $logger = new Monologer($channel);
    unset($arguments[2]);
    $stackTraces = $arguments[3] ?? false;
    if ($stackTraces) {
      self::debugBackTrace($arguments[0]);
    }
    $handler = new StreamHandler(sprintf("%s/var/log/%s.log", BaseService::getProjectDir(), $channel), Level::Info);
    $formatter = new LineFormatter(null, null, false, true);
    // $formatter->ignoreEmptyContextAndExtra(true);
    $handler->setFormatter($formatter);
    $logger->pushHandler($handler);
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
        $frame['function'] ?? 'index'
      );
    }
    $callerInfo = implode(" -> ", $callerInfo);
    $msg = sprintf("%s=>%s", $msg, $callerInfo);
  }

  public static function log($msg, array $context = [], string $channel = "app"): void
  {
    self::__callStatic("info", [$msg, $context, $channel, false]);
  }

  public static function error($msg, array $context = [], string $channel = "app"): void
  {
    self::__callStatic("error", [$msg, $context, $channel, true]);
  }

  public static function debug($msg, array $context = [], string $channel = "app"): void
  {
    self::__callStatic("debug", [$msg, $context, $channel, true]);
  }

  public static function critical($msg, array $context = [], string $channel = "app"): void
  {
    self::__callStatic("critical", [$msg, $context, $channel, true]);
  }
}
