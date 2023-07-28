<?php

namespace App\Repository;

use App\Entity\UtilityMeterReading;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UtilityMeterReading>
 *
 * @method UtilityMeterReading|null find($id, $lockMode = null, $lockVersion = null)
 * @method UtilityMeterReading|null findOneBy(array $criteria, array $orderBy = null)
 * @method UtilityMeterReading[]    findAll()
 * @method UtilityMeterReading[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UtilityMeterReadingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UtilityMeterReading::class);
    }

    public function save(UtilityMeterReading $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(UtilityMeterReading $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return UtilityMeterReading[] Returns an array of UtilityMeterReading objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('u.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?UtilityMeterReading
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
