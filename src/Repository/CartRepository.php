<?php

namespace App\Repository;

use App\Entity\Cart;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Cart>
 */
class CartRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cart::class);
    }

    public function findByUser(User $user): ?Cart
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOrCreateByUser(User $user): Cart
    {
        $cart = $this->findByUser($user);
        
        if (!$cart) {
            $cart = new Cart();
            $cart->setUser($user);
            $this->getEntityManager()->persist($cart);
            $this->getEntityManager()->flush();
        }
        
        return $cart;
    }
}