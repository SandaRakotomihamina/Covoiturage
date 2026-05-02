<?php

namespace App\Controller;

use App\Entity\Ride;
use App\Entity\User;
use App\Repository\RideRepository;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/ride')]
class RideController extends AbstractController
{
    #[Route('/', name: 'app_ride_index')]
    public function index(RideRepository $rideRepository): Response
    {
        $rides = $rideRepository->findAvailableRides();

        return $this->render('ride/index.html.twig', [
            'rides' => $rides,
        ]);
    }

    #[Route('/new', name: 'app_ride_new')]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user || !$user->isHasCar()) {
            $this->addFlash('error', 'Vous devez avoir une voiture pour proposer un trajet.');
            return $this->redirectToRoute('app_profile');
        }

        if ($request->isMethod('POST')) {
            $ride = new Ride();
            $ride->setDriver($user);
            $ride->setDeparture($request->request->get('departure'));
            $ride->setDestination($request->request->get('destination'));
            $ride->setDepartureTime(new \DateTime($request->request->get('departure_time')));
            $ride->setAvailableSeats((int)$request->request->get('available_seats'));
            $ride->setNotes($request->request->get('notes'));

            $entityManager->persist($ride);
            $entityManager->flush();

            $this->addFlash('success', 'Trajet créé avec succès !');
            return $this->redirectToRoute('app_ride_index');
        }

        return $this->render('ride/new.html.twig');
    }

    #[Route('/{id}', name: 'app_ride_show', requirements: ['id' => '\\d+'])]
    public function show(Ride $ride): Response
    {
        return $this->render('ride/show.html.twig', [
            'ride' => $ride,
        ]);
    }

    #[Route('/{id}/book', name: 'app_ride_book', requirements: ['id' => '\\d+'])]
    #[IsGranted('ROLE_USER')]
    public function book(Ride $ride, EntityManagerInterface $entityManager, EmailService $emailService): Response
    {
        $user = $this->getUser();

        if ($ride->getDriver() === $user) {
            $this->addFlash('error', 'Vous ne pouvez pas réserver votre propre trajet.');
            return $this->redirectToRoute('app_ride_show', ['id' => $ride->getId()]);
        }

        if ($ride->getPassengers()->contains($user)) {
            $this->addFlash('error', 'Vous avez déjà réservé ce trajet.');
            return $this->redirectToRoute('app_ride_show', ['id' => $ride->getId()]);
        }

        if ($ride->getAvailableSeats() <= 0) {
            $this->addFlash('error', 'Plus de places disponibles.');
            return $this->redirectToRoute('app_ride_show', ['id' => $ride->getId()]);
        }

        $ride->addPassenger($user);
        $ride->setAvailableSeats($ride->getAvailableSeats() - 1);
        $entityManager->flush();

        // Envoyer un email au propriétaire de la voiture
        try {
            $emailService->sendRideBookingNotification($ride, $user);
        } catch (\Exception $e) {
            $this->addFlash('warning', 'Réservation confirmée, mais l\'email n\'a pas pu être envoyé.');
        }

        $this->addFlash('success', 'Place réservée avec succès !');
        return $this->redirectToRoute('app_ride_show', ['id' => $ride->getId()]);
    }

    #[Route('/{id}/cancel', name: 'app_ride_cancel', requirements: ['id' => '\\d+'])]
    #[IsGranted('ROLE_USER')]
    public function cancel(Ride $ride, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if (!$ride->getPassengers()->contains($user)) {
            $this->addFlash('error', 'Vous n\'êtes pas inscrit à ce trajet.');
            return $this->redirectToRoute('app_ride_show', ['id' => $ride->getId()]);
        }

        $ride->removePassenger($user);
        $ride->setAvailableSeats($ride->getAvailableSeats() + 1);
        $entityManager->flush();

        $this->addFlash('success', 'Votre réservation a été annulée.');
        return $this->redirectToRoute('app_my_rides');
    }

    #[Route('/my-rides', name: 'app_my_rides')]
    #[IsGranted('ROLE_USER')]
    public function myRides(RideRepository $rideRepository): Response
    {
        $user = $this->getUser();
        $rides = $rideRepository->findRidesByUser($user);

        return $this->render('ride/my_rides.html.twig', [
            'rides' => $rides,
        ]);
    }
}