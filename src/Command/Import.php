<?php

namespace AgileZenToRedmine\Command;

use Redmine\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use AgileZenToRedmine\Dump;

class Import extends Command
{
    /// @var Redmine\Client
    private $redmine;

    /// @var OutputInterface
    private $output;

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
        $this->output = $output;

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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dump = Dump::load($input->getOption('output-dir'));

        $this->createUsers($dump->getUsers());
    }

    /**
     * Create Redmine users if they don't already exist (email as primary key).
     *
     * @param User[] $users
     */
    private function createUsers(array $users)
    {
        $this->output->writeln('Create users.');
        $progress = new ProgressBar($this->output, count($users));
        $skipped = 0;
        $redmineUsers = array_column(
            $this->redmine->api('user')->all()['users'],
            'mail'
        );

        foreach ($users as $user) {
            if (in_array($user->email, $redmineUsers, true)) {
                $skipped += 1;
                $progress->advance();
                continue;
            }

            // Mandatory parameters, can't be an empty string.
            $names = explode(' ', $user->name, 2) + ['NoFirstName', 'NoLastName'];

            $this->redmine->api('user')->create([
                'login'     => explode('@', $user->email)[0],
                'firstname' => $names[0],
                'lastname'  => $names[1],
                'mail'      => $user->email
            ]);

            $progress->advance();
        }

        $progress->finish();
        $this->output->writeln("\nDone creating users, skipped: $skipped");
    }
}
