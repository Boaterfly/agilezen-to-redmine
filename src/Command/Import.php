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
use AgileZenToRedmine\Redmine;

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
        $this->createProjects($dump->getProjects());
    }

    /**
     * Create Redmine users if they don't already exist, email is used for
     * deduplication.
     *
     * @param User[] $users
     */
    private function createUsers(array $users)
    {
        $this->output->writeln('Create users:');
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

    /**
     * Create Redmine projects if they don't already exist, Redmine project
     * identifier is used for deduplication.
     *
     * @param Project[] $projects
     */
    private function createProjects(array $projects)
    {
        $this->output->writeln('Create projects:');
        $progress = new ProgressBar($this->output, count($projects));
        $skipped = 0;

        $redmineUsers = $this->getEmailMappedRedmineUsers();
        $redmineProjects = array_column(
            $this->redmine->api('project')->all()['projects'],
            'identifier'
        );

        foreach ($projects as $project) {
            $identifier = Redmine\identifier_from_agilezen_project($project);
            if (in_array($identifier, $redmineProjects, true)) {
                $skipped += 1;
                $progress->advance();
                continue;
            }

            $description = Redmine\description_from_agilezen_project($project);
            $this->redmine->api('project')->create([
                'name'        => $project->name,
                'identifier'  => $identifier,
                'description' => $description,
                'is_public'   => false,
            ]);

            $progress->advance();
        }

        $progress->finish();
        $this->output->writeln("\nDone creating projects, skipped: $skipped");
    }

    /**
     * Return a map of redmine user logins with their email as key.
     *
     * @return string[] mail => login
     */
    private function getEmailMappedRedmineUsers()
    {
        $ret = [];
        foreach ($this->redmine->api('user')->all()['users'] as $user) {
            $ret[$user['mail']] = $user['login'];
        }
        return $ret;
    }
}
