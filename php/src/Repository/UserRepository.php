<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function countNonAdminUsers(): int
    {
        $users = $this->createQueryBuilder('u')
            ->select('u.roles')
            ->getQuery()
            ->getArrayResult();

        $count = 0;
        foreach ($users as $user) {
            $rawRoles = $user['roles'] ?? [];
            $roles = match (true) {
                is_array($rawRoles) => $rawRoles,
                is_string($rawRoles) => is_array($decodedRoles = json_decode($rawRoles, true)) ? $decodedRoles : [],
                default => [],
            };
            if (!in_array('ROLE_ADMIN', $roles, true)) {
                ++$count;
            }
        }

        return $count;
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }
}
