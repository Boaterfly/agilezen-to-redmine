<?php

namespace AgileZenToRedmine\Command;

use Redmine\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use AgileZenToRedmine\Api\AgileZen\Project;
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
        $this->createProjects($dump->projects);

        foreach ($dump->projects as $project) {
            $dump->storyMapping = $this->createProjectIssues($project, $dump->storyMapping);
        }

        $dump->write();
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
                'login'     => Redmine\login_from_agilezen_user($user),
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
     * Create issues and return a mapping of AgileZen story ID to Redmine issue ID.
     *
     * @param int[] $storyMapping original mapping. This is used to skip
     * already created issues.
     * @return int[] story id => issue id
     */
    private function createProjectIssues(Project $project, array $storyMapping)
    {
        $this->output->writeln("Create issues for project #{$project->id}.");
        $progress = new ProgressBar($this->output, count($project->stories));
        $skipped = 0;

        $projectId = $this->getRedmineProjectId($project);

        foreach ($project->stories as $story) {
            if (array_key_exists($story->id, $storyMapping)) {
                $skipped += 1;
                $progress->advance();
                continue;
            }

            $this->redmine->setImpersonateUser(
                Redmine\login_from_agilezen_user($story->creator)
            );

            $res = $this->redmine->api('issue')->create([
                'project_id' => $projectId,
                'subject' => Redmine\subject_from_agilezen_story($story),
                'description' => Redmine\description_from_agilezen_story($story),
            ]);

            if (empty($res->id)) {
                throw new \RuntimeException("Unable to create issue for story #{$story->id}: {$res->error}.");
            }

            $storyMapping[$story->id] = (int) ((string) $res->id);
            $this->redmine->setImpersonateUser(null);
            $progress->advance();
        }

        $progress->finish();
        $this->output->writeln('');
        $this->output->writeln("\nDone creating issues, skipped: $skipped");
        return $storyMapping;
    }

    /// @return int
    private function getRedmineProjectId(Project $project)
    {
        return $this->redmine->api('project')->getIdByName($project->name);
    }
}
