<?php

namespace AgileZenToRedmine\Command;

use Redmine\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use AgileZenToRedmine\Dump;

class Import extends Command
{
    /// @var Redmine\Client
    private $api;

    protected function configure()
    {
        $this
            ->setName('import')
            ->setDescription('Import exported data from AgileZen into Redmine')
            ->addOption(
                'output-dir',
                null,
                InputOption::VALUE_REQUIRED,
                'Where to read the exported data.',
                'export'
            )
            ->addOption(
                'redmine-url',
                null,
                InputOption::VALUE_REQUIRED,
                'Redmine HTTP URL.'
            )
            ->addOption(
                'redmine-key',
                null,
                InputOption::VALUE_REQUIRED,
                'Redmine API key.'
            )
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $url = $input->getOption('redmine-url');
        $key = $input->getOption('redmine-key');

        if (strlen($url) <= 0 || strlen($key) <= 0) {
            throw new \RuntimeException('Both --redmine-url and --redmine-key are required.');
        }

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new \RuntimeException('Invalid URL for --redmine-url.');
        }

        $this->api = new Client($url, $key);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dump = Dump::load($input->getOption('output-dir'));
    }
}
