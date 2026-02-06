<?php

declare(strict_types=1);

namespace Redium\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'redium:build|rd:build', description: 'Hello PhpStorm')]
class BuildCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        //
        return Command::SUCCESS;
    }
}
