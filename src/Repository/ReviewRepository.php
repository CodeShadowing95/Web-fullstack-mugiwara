<?php

namespace App\Repository;

use App\Entity\Review;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Review>
 *
 * @method Review|null find($id, $lockMode = null, $lockVersion = null)
 * @method Review|null findOneBy(array $criteria, array $orderBy = null)
 * @method Review[]    findAll()
 * @method Review[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    public function save(Review $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Review $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return Review[] Returns an array of Review objects
     */
    public function findByProduct(int $productId): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.product = :productId')
            ->setParameter('productId', $productId)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Review[] Returns an array of Review objects
     */
    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAverageRatingByProduct(int $productId): ?float
    {
        $result = $this->createQueryBuilder('r')
            ->select('AVG(r.rating) as averageRating')
            ->andWhere('r.product = :productId')
            ->setParameter('productId', $productId)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (float) $result : null;
    }

    public function findReviewByUserAndProduct(int $userId, int $productId): ?Review
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.user = :userId')
            ->andWhere('r.product = :productId')
            ->setParameter('userId', $userId)
            ->setParameter('productId', $productId)
            ->getQuery()
            ->getOneOrNullResult();
    }
} 