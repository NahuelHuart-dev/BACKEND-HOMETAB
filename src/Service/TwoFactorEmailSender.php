<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class TwoFactorEmailSender
{
    public function __construct(
        private MailerInterface $mailer,
        private string $fromAddress
    ) {}

    public function sendCode(User $user, string $code, string $purpose): void
    {
        $subjects = [
            'login_2fa' => 'Tu codigo de inicio de sesion de HomeTab',
            'enable_2fa' => 'Activa la verificacion en dos pasos de HomeTab',
            'password_reset' => 'Codigo para recuperar tu contrasena de HomeTab',
        ];

        $messages = [
            'login_2fa' => 'Usa este codigo para completar tu inicio de sesion.',
            'enable_2fa' => 'Usa este codigo para activar el inicio de sesion en dos pasos.',
            'password_reset' => 'Usa este codigo para confirmar el cambio de contrasena.',
        ];

        $subject = $subjects[$purpose] ?? 'Codigo de HomeTab';
        $message = $messages[$purpose] ?? 'Usa este codigo para continuar.';

        $email = (new Email())
            ->from(new Address($this->fromAddress, 'HomeTab'))
            ->to((string) $user->getEmail())
            ->subject($subject)
            ->text($message."\n\nCodigo: ".$code."\n\nCaduca en 10 minutos.");

        $this->mailer->send($email);
    }
}
