<?php

namespace App\Repository;

use App\Entity\Ride;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ServiceEntityRepository<Ride>
 *
 * @method Ride|null find($id, $lockMode = null, $lockVersion = null)
 * @method Ride|null findOneBy(array $criteria, array $orderBy = null)
 * @method Ride[]    findAll()
 * @method Ride[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RideRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ride::class);
    }

    public function findAvailableRides()
    {
        return $this->createQueryBuilder('r')
            ->where('r.departureTime > :now')
            ->andWhere('r.availableSeats > 0')
            ->setParameter('now', new \DateTime())
            ->orderBy('r.departureTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findRidesByUser(UserInterface $user)
    {
        return $this->createQueryBuilder('r')
            ->where('r.driver = :user')
            ->orWhere(':user MEMBER OF r.passengers')
            ->setParameter('user', $user)
            ->orderBy('r.departureTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findSuggestedRides(UserInterface $user)
    {
        // Récupérer les destinations de l'historique de l'utilisateur
        $userRides = $this->createQueryBuilder('r')
            ->where('r.driver = :user')
            ->orWhere(':user MEMBER OF r.passengers')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        $destinations = [];
        foreach ($userRides as $ride) {
            $destinations[] = $ride->getDestination();
        }

        // Si pas d'historique, retourner les trajets disponibles
        if (empty($destinations)) {
            return $this->findAvailableRides();
        }

        // Trouver les trajets disponibles vers les mêmes destinations
        return $this->createQueryBuilder('r')
            ->where('r.departureTime > :now')
            ->andWhere('r.availableSeats > 0')
            ->andWhere('r.destination IN (:destinations)')
            ->setParameter('now', new \DateTime())
            ->setParameter('destinations', $destinations)
            ->orderBy('r.departureTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

//    /**
//     * @return Ride[] Returns an array of Ride objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('r.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Ride
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}