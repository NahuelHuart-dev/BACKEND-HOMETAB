<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class FeedbackEmailSender
{
    public function __construct(
        private MailerInterface $mailer,
        private string $fromAddress,
        private string $recipientAddress = 'hometab.admin@gmail.com'
    ) {
    }

    /**
     * @param array{name:string,email:string,category:string,message:string,ip:string} $feedback
     */
    public function send(array $feedback): void
    {
        $replyTo = filter_var($feedback['email'], FILTER_VALIDATE_EMAIL) ? $feedback['email'] : null;

        $email = (new Email())
            ->from(new Address($this->fromAddress, 'HomeTab'))
            ->to($this->recipientAddress)
            ->subject('[HomeTab] Feedback: '.$feedback['category'])
            ->text(
                "Nombre: ".$feedback['name']."\n".
                "Email: ".($feedback['email'] ?: 'No informado')."\n".
                "Categoria: ".$feedback['category']."\n".
                "IP: ".$feedback['ip']."\n\n".
                $feedback['message']
            );

        if ($replyTo) {
            $email->replyTo($replyTo);
        }

        $this->mailer->send($email);
    }
}
