<?php

namespace App\Tests\Command;

use App\Command\CreateUserCommand;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CreateUserCommandTest extends TestCase
{
    public function testItCreatesAUserWithHashedPassword(): void
    {
        $userRepository = $this->createMock(UserRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);

        $userRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'reader@example.com'])
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
                self::assertSame('reader@example.com', $user->getEmail());
                self::assertSame(['ROLE_USER'], $user->getRoles());
                self::assertSame('hashed-password', $user->getPassword());

                return true;
            }));

        $entityManager
            ->expects($this->once())
            ->method('flush');

        $command = new CreateUserCommand($userRepository, $entityManager, $passwordHasher);
        $tester = new CommandTester($command);

        $statusCode = $tester->execute([
            'email' => 'Reader@example.com',
            '--password' => 'secret-pass',
        ]);

        self::assertSame(Command::SUCCESS, $statusCode);
        self::assertStringContainsString('created successfully', $tester->getDisplay());
    }

    public function testItFailsWhenUserAlreadyExists(): void
    {
        $existingUser = new User();
        $existingUser->setEmail('reader@example.com');

        $userRepository = $this->createMock(UserRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);

        $userRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'reader@example.com'])
            ->willReturn($existingUser);

        $entityManager->expects($this->never())->method('persist');
        $entityManager->expects($this->never())->method('flush');
        $passwordHasher->expects($this->never())->method('hashPassword');

        $command = new CreateUserCommand($userRepository, $entityManager, $passwordHasher);
        $tester = new CommandTester($command);

        $statusCode = $tester->execute([
            'email' => 'reader@example.com',
            '--password' => 'secret-pass',
        ]);

        self::assertSame(Command::FAILURE, $statusCode);
        self::assertStringContainsString('already exists', $tester->getDisplay());
    }
}
