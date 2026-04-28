<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

abstract class AbstractCreateUserCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email of the user to create.')
            ->addOption(
                'password',
                null,
                InputOption::VALUE_REQUIRED,
                'Plain password. If omitted, the command will prompt for it securely.'
            )
        ;
    }

    protected function createUser(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = mb_strtolower(trim((string) $input->getArgument('email')));
        if ('' === $email) {
            $io->error('The email cannot be empty.');

            return Command::INVALID;
        }

        if (null !== $this->userRepository->findOneBy(['email' => $email])) {
            $io->error(sprintf('A user with email "%s" already exists.', $email));

            return Command::FAILURE;
        }

        $plainPassword = $this->resolvePassword($input, $output, $io);
        if (null === $plainPassword) {
            return Command::INVALID;
        }

        $user = new User();
        $user->setEmail($email);
        $user->setRoles($this->roles());
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('%s "%s" created successfully.', $this->userLabel(), $email));

        return Command::SUCCESS;
    }

    /** @return list<string> */
    abstract protected function roles(): array;

    abstract protected function userLabel(): string;

    private function resolvePassword(InputInterface $input, OutputInterface $output, SymfonyStyle $io): ?string
    {
        $plainPassword = $input->getOption('password');
        if (\is_string($plainPassword)) {
            $plainPassword = trim($plainPassword);
        }

        if (!\is_string($plainPassword) || '' === $plainPassword) {
            /** @var QuestionHelper $helper */
            $helper = $this->getHelper('question');
            $question = new Question('Password: ');
            $question->setHidden(true);
            $question->setHiddenFallback(false);

            $plainPassword = $helper->ask($input, $output, $question);
            if (\is_string($plainPassword)) {
                $plainPassword = trim($plainPassword);
            }
        }

        if (!\is_string($plainPassword) || '' === $plainPassword) {
            $io->error('The password cannot be empty.');

            return null;
        }

        return $plainPassword;
    }
}
