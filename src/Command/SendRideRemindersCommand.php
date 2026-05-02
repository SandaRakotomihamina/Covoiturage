<?php

namespace App\Command;

use App\Entity\RideReminder;
use App\Repository\RideRepository;
use App\Repository\RideReminderRepository;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:send-ride-reminders',
    description: 'Envoie les emails de rappel aux passagers 1 heure avant le départ',
)]
class SendRideRemindersCommand extends Command
{
    public function __construct(
        private RideRepository $rideRepository,
        private RideReminderRepository $reminderRepository,
        private EmailService $emailService,
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->info('Envoi des rappels de trajet...');

        // Calculer l'heure limite (maintenant + 1 heure)
        $now = new \DateTime();
        $oneHourLater = (clone $now)->modify('+1 hour');
        $fiveMinutesBefore = (clone $now)->modify('+55 minutes');

        // Trouver les trajets qui partent dans 1 heure (+/- 5 minutes)
        $ridesQb = $this->rideRepository->createQueryBuilder('r')
            ->where('r.departureTime BETWEEN :start AND :end')
            ->setParameter('start', $fiveMinutesBefore)
            ->setParameter('end', $oneHourLater)
            ->getQuery()
            ->getResult();

        $sentCount = 0;

        foreach ($ridesQb as $ride) {
            // Pour chaque passager du trajet
            foreach ($ride->getPassengers() as $passenger) {
                // Vérifier si le rappel n'a pas déjà été envoyé
                $existingReminder = $this->reminderRepository->findOneBy([
                    'ride' => $ride,
                    'passenger' => $passenger,
                ]);

                if (!$existingReminder) {
                    try {
                        // Envoyer l'email
                        $this->emailService->sendRideReminder($ride, $passenger);

                        // Enregistrer le rappel
                        $reminder = new RideReminder();
                        $reminder->setRide($ride);
                        $reminder->setPassenger($passenger);
                        $reminder->setSentAt(new \DateTime());

                        $this->entityManager->persist($reminder);
                        $sentCount++;

                        $io->success("Rappel envoyé à {$passenger->getEmail()} pour le trajet {$ride->getDeparture()} → {$ride->getDestination()}");
                    } catch (\Exception $e) {
                        $io->error("Erreur lors de l'envoi à {$passenger->getEmail()}: {$e->getMessage()}");
                    }
                }
            }
        }

        $this->entityManager->flush();

        $io->success("$sentCount rappel(s) envoyé(s) avec succès!");

        return Command::SUCCESS;
    }
}
