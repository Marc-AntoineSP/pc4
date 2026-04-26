<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\CreateAdminCommand;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CreateAdminCommandTest extends TestCase
{
    public function testItCreatesAnAdminUserWithHashedPassword(): void
    {
        $userRepository = $this->createMock(UserRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);

        $userRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'admin@example.com'])
            ->willReturn(null);

        $passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->with(
                $this->isInstanceOf(User::class),
                'secret-pass',
            )
            ->willReturn('hashed-password');

        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (User $user): bool {
                self::assertSame('admin@example.com', $user->getEmail());
                self::assertSame(['ROLE_ADMIN', 'ROLE_USER'], $user->getRoles());
                self::assertSame('hashed-password', $user->getPassword());

                return true;
            }));

        $entityManager
            ->expects($this->once())
            ->method('flush');

        $command = new CreateAdminCommand($userRepository, $entityManager, $passwordHasher);
        $tester = new CommandTester($command);

        $statusCode = $tester->execute([
            'email' => 'Admin@example.com',
            '--password' => 'secret-pass',
        ]);

        self::assertSame(Command::SUCCESS, $statusCode);
        self::assertStringContainsString('created successfully', $tester->getDisplay());
    }
}
