<?php

namespace App\Service;

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mime\Email;

class MailerService
{
  static $postMail = "bt4nn_noreplay@boingtech.com";
  public static function sendEmail(array $toAdresses, string $subject, string $content): void
  {
    $transport = Transport::fromDsn($_ENV['MAILER_DSN'] ?? 'null://null');
    $mailer = new Mailer($transport);

    $email = (new Email())
      ->from(self::$postMail)
      ->subject($subject)
      ->html($content);

    foreach ($toAdresses as $address) {
      $email->addTo($address);
    }

    try {
      $mailer->send($email);
    } catch (TransportException $e) {
      Logger::error($e->getMessage());
    }
  }
}
