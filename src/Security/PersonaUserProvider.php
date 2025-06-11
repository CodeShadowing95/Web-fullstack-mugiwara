<?php

namespace App\Security;

use App\Entity\User;
use App\Entity\Persona;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class PersonaUserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $persona = $this->em->getRepository(Persona::class)->findOneBy(['email' => $identifier]);
        if (!$persona || !$persona->getUser()) {
            throw new UserNotFoundException(sprintf('Aucun utilisateur trouvé pour l\'email "%s".', $identifier));
        }
        return $persona->getUser();
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new \InvalidArgumentException('Instances de "' . get_class($user) . '" non supportées.');
        }
        return $this->em->getRepository(User::class)->find($user->getId());
    }

    public function supportsClass(string $class): bool
    {
        return $class === User::class;
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            return;
        }
        $user->setPassword($newHashedPassword);
        $this->em->persist($user);
        $this->em->flush();
    }
}
