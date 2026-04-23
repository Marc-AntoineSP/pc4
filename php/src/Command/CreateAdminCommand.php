<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:user:create-admin',
    description: 'Creates an admin user in the database with a hashed password.',
)]
class CreateAdminCommand extends AbstractCreateUserCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return $this->createUser($input, $output);
    }

    protected function roles(): array
    {
        return ['ROLE_ADMIN'];
    }

    protected function userLabel(): string
    {
        return 'Admin user';
    }
}
