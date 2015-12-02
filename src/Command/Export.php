<?php

namespace AgileZenToRedmine\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use AgileZenToRedmine\Api\AgileZen;

class Export extends Command
{
    /// @var AgileZen
    private $api;

    protected function configure()
    {
        $this
            ->setName('export')
            ->setDescription('Export data from AgileZen.')
            ->addOption(
                'agilezen-key',
                null,
                InputOption::VALUE_REQUIRED,
                'AgileZen API key.'
            )
            ->addOption(
                'project-id',
                null,
                InputOption::VALUE_REQUIRED,
                'ID of the project to export.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $project = $this->getProject($input, $output);
        $output->writeln("Using project: $project");
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $token = $input->getOption('agilezen-key');
        if (strlen($token) <= 0) {
            throw new \RuntimeException('--agilezen-key is mandatory.');
        }

        $this->api = new AgileZen($token);
    }

    /// @return Project from given --project-id
    private function getProject(InputInterface $input, OutputInterface $output)
    {
        $projects = $this->api->projects();
        $projectId = (int) $input->getOption('project-id');

        if ($projectId === null) {
            $output->writeln('You need to specify a project ID to export with --project-id=PROJECT-ID.');
            $output->writeln('Here are the available projects:');
            $this->renderProjectList($output, $projects);
            exit(1);
        }

        if (!in_array($projectId, collection_column($projects, 'id'), true)) {
            $output->writeln('Unknown project id.');
            $output->writeln('Here are the available projects:');
            $this->renderProjectList($output, $projects);
            exit(1);
        }

        $project = collection_filter($projects, function ($v) use ($projectId) {
            return $v->id === $projectId;
        })[0];

        return $project;
    }

    /**
     * @param Project[] $projects
     */
    private function renderProjectList(OutputInterface $output, array $projects)
    {
        $table = new Table($output);
        $table->setHeaders(['ID', 'Name', 'Description', 'Owner']);
        $table->setRows(array_map(
            function ($project) {
                return [
                    $project->id,
                    $project->name,
                    $project->description,
                    $project->owner->email
                ];
            },
            $projects
        ));
        $table->render();
    }
}
