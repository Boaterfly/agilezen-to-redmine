<?php

namespace AgileZenToRedmine\Command;

use Redmine\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

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
        $map = [];

        foreach ($this->dump->projects as $project) {
            $phases = collection_column($project->phases, 'name');
            $statuses = array_keys($this->redmine->api('issue_status')->listing());
            $map[$project->id] = [];

            $output->writeln("For project #{$project->id} '{$project->name}':");
            foreach ($phases as $phase) {
                $helper = $this->getHelper('question');
                $question = new ChoiceQuestion(
                    "Map the AgileZen phase '$phase' to Redmine status:",
                    $statuses
                );
                $question->setErrorMessage('Invalid status.');
                $map[$project->id][$phase] = $helper->ask($input, $output, $question);
            }
        }

        $this->dump->phaseMap = $map;
        $this->dump->write();
    }
}
