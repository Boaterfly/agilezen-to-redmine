<?php

namespace AgileZenToRedmine\Command;

use Redmine\Client;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

trait RedmineCommandTrait
{
    /// @var Redmine\Client
    private $redmine;

    private function configureRedmineOptions()
    {
        $this
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

    private function initializeRedmineClient(InputInterface $input)
    {
        $url = $input->getOption('redmine-url');
        $key = $input->getOption('redmine-key');

        if (strlen($url) <= 0 || strlen($key) <= 0) {
            throw new \RuntimeException('Both --redmine-url and --redmine-key are required.');
        }

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new \RuntimeException('Invalid URL for --redmine-url.');
        }

        $this->redmine = new Client($url, $key);
    }
}
