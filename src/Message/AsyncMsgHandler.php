<?php

namespace App\Message;

use App\Message\AsyncMsg;
use App\Service\Logger;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(handles: \App\Message\AsyncMsg::class, fromTransport: "async")]
class AsyncMsgHandler
{

  public function __construct() {}

  public function __invoke(AsyncMsg $msg)
  {
    try {
      match ($msg->getTransport()) {
        "job1" => $this->handleJob1($msg),
        "job2" => $this->handleJob2($msg),
        default => Logger::log("MsgHandler: unknown transport" . $msg->getTransport())
      };
    } catch (\Exception $e) {
      Logger::log(sprintf("invoke transport %s failed with reason: %s", $msg->getTransport(), $e->getMessage()));
    }
  }

  public function handleJob1(AsyncMsg $msg)
  {
    // do something
    Logger::log(sprintf("handleJob1: %s", $msg->getId()));
  }

  public function handleJob2(AsyncMsg $msg)
  {
    // do something
    Logger::log(sprintf("handleJob2: %s", $msg->getId()));
  }
}
