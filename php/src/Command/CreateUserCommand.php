<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:user:create',
    description: 'Creates a standard user in the database with a hashed password.',
)]
class CreateUserCommand extends AbstractCreateUserCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return $this->createUser($input, $output);
    }

    protected function roles(): array
    {
        return [];
    }

    protected function userLabel(): string
    {
        return 'User';
    }
}
