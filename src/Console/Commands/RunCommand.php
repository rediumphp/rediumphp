<?php

namespace Redium\Console\Commands;

use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'redium:run|rd:run|rd|start', description: "Start Redium server")]
class RunCommand extends Command
{
    public function __invoke(OutputInterface $output, #[Argument] ?string $host, #[Argument] ?string $port, #[Option] bool $db = false):int
    {
        if ($db){
            $dbCommand = new MigrationCommand();
        }

        $host = $host ??  $_ENV['HOST'] ?? 'localhost';
        $port = $port ?? $_ENV['PORT'] ?? '8000';
        $output->writeln("Starting server on http://{$host}:{$port}");
        $output->writeln(file_get_contents(__DIR__ . '/../../../banner.txt'));
        $cmd = passthru("php -S {$host}:{$port}");
        $reponse = $cmd == null ? Command::SUCCESS : Command::FAILURE;
        return $reponse;
    }
}