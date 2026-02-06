<?php
namespace Redium\Console;

use Redium\Console\Commands\BuildCommand;
use Redium\Console\Commands\MigrationCommand;
use Redium\Console\Commands\RunCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Console extends Application{
    function __construct($name = 'UNKNOWN', $version = 'UNKNOWN')
    {
        parent::__construct($name, $version);
    }

    public function run(?InputInterface $input = null, ?OutputInterface $output = null): int
    {
        $this->addCommands([new RunCommand(), new MigrationCommand(), new BuildCommand()]);
        return parent::run();
    }
}