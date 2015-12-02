<?php

namespace AgilezenToRedmine\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Export extends Command
{
    protected function configure()
    {
        $this
            ->setName('export')
            ->setDescription('Export data from AgileZen.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
    }
}
