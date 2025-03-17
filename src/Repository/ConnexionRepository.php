<?php

namespace App\Repository;

use App\Entity\Connexion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Connexion>
 */
class ConnexionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Connexion::class);
    }

    //    /**
    //     * @return Connexion[] Returns an array of Connexion objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Connexion
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function getConnectionsForLast7Days(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
        SELECT
            DATE(date_connexion) as date,
            COUNT(*) as totalConnections
        FROM connexion
        WHERE date_connexion >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(date_connexion)
        ORDER BY date ASC
    ';
        $stmt = $conn->prepare($sql);
        $resultSet = $stmt->executeQuery();

        return $resultSet->fetchAllAssociative();
    }

}
