<?php

namespace App\Service;

use App\Entity\Ride;
use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class EmailService
{
    public function __construct(private MailerInterface $mailer) {}

    /**
     * Envoie un email au propriétaire de la voiture quand une réservation est faite
     */
    public function sendRideBookingNotification(Ride $ride, User $passenger): void
    {
        $driver = $ride->getDriver();

        $email = (new TemplatedEmail())
            ->from(new Address('noreply@covoiturage.fr', 'Covoiturage'))
            ->to($driver->getEmail())
            ->subject('Nouvelle réservation sur votre trajet')
            ->htmlTemplate('email/ride_booking_driver.html.twig')
            ->context([
                'driver' => $driver,
                'passenger' => $passenger,
                'ride' => $ride,
            ]);

        $this->mailer->send($email);
    }

    /**
     * Envoie un email de rappel au passager une heure avant le départ
     */
    public function sendRideReminder(Ride $ride, User $passenger): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address('rakotomihaminasandafitia@gmail.com', 'Covoiturage'))
            ->to($passenger->getEmail())
            ->subject('Rappel : Votre trajet en covoiturage est dans 1 heure')
            ->htmlTemplate('email/ride_reminder_passenger.html.twig')
            ->context([
                'passenger' => $passenger,
                'ride' => $ride,
                'driver' => $ride->getDriver(),
            ]);

        $this->mailer->send($email);
    }
}
