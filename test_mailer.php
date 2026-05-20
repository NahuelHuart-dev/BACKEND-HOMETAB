<?php
require 'vendor/autoload.php';
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;

$transport = Transport::fromDsn('smtp://hometab.admin%40gmail.com:spgzkcqfaclkdwgt@smtp.gmail.com:587');
$mailer = new Mailer($transport);

$email = (new Email())
    ->from('hometab.admin@gmail.com')
    ->to('naoctubre@gmail.com')
    ->subject('Test Mailer')
    ->text('This is a test');

try {
    $mailer->send($email);
    echo 'Email sent successfully';
} catch (\Throwable $e) {
    echo 'Error: ' . $e->getMessage();
}
