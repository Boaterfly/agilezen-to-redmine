<?php

namespace AgilezenToRedmine\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use AgilezenToRedmine\Api\AgileZen;

class Export extends Command
{
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
        $token = $input->getOption('agilezen-key');
        if (strlen($token) <= 0) {
            throw new \RuntimeException('--agilezen-key is mandatory.');
        }

        $api = new AgileZen($token);

        if ($input->getOption('project-id') === null) {
            $output->writeln('You need to specify a project ID to export with --project-id=PROJECT-ID.');
            $output->writeln('Here are the available projects:');
            $this->renderProjectList($output, $api->projects());
            return 1;
        }
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
