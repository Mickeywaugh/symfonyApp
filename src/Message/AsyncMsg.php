<?php

namespace App\Message;

class AsyncMsg
{
  private $id;
  private $props;
  private $transport;
  public function __construct(string $transport, $id, $props)
  {
    $this->transport = $transport;
    $this->id = $id;
    $this->props = $props;
  }

  public function getTransport(): string
  {
    return $this->transport;
  }

  public function getId(): string
  {
    return $this->id;
  }

  public function getProps(): array
  {
    return $this->props;
  }
}
