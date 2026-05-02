<?php

namespace App\Repository;

use App\Entity\RideReminder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RideReminder>
 *
 * @method RideReminder|null find($id, $lockMode = null, $lockVersion = null)
 * @method RideReminder|null findOneBy(array $criteria, array $orderBy = null)
 * @method RideReminder[]    findAll()
 * @method RideReminder[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RideReminderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RideReminder::class);
    }
}
