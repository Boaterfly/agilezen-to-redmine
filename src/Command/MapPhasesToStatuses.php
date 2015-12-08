<?php

namespace AgileZenToRedmine\Command;

use Redmine\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use AgileZenToRedmine\Dump;
use AgileZenToRedmine\Redmine;

class MapPhasesToStatuses extends Command
{
    use RedmineCommandTrait;

    protected function configure()
    {
        $this
            ->setName('map-phases-to-statuses')
            ->setDescription('Create configuration file to map AgileZen phases to Redmine issue statuses.')
            ->addOption(
                'output-dir',
                null,
                InputOption::VALUE_REQUIRED,
                'Where to read the exported data.',
                'export'
            )
        ;
        $this->configureRedmineOptions();
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->initializeRedmineClient($input);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->dump = Dump::load($input->getOption('output-dir'));
    }
}
