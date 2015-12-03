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
            ->addArgument(
                'output',
                InputArgument::REQUIRED,
                'Where to write the exported data.'
            )
            ->addOption(
                'agilezen-key',
                null,
                InputOption::VALUE_REQUIRED,
                'AgileZen API key.'
            )
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $token = $input->getOption('agilezen-key');
        if (strlen($token) <= 0) {
            throw new \RuntimeException('--agilezen-key is mandatory.');
        }

        $this->api = new AgileZen($token);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stats = (object) [
            'projects' => 0,
            'stories'  => 0,
            'comments' => 0,
        ];

        $projects = $this->api->projects();
        $stats->projects = count($projects);

        foreach ($projects as $project) {
            $project->stories = $this->api->stories($project->id);
            $stats->stories += count($project->stories);
            $stats->comments += array_sum(array_map(
                function ($story) {
                    return count($story->comments);
                },
                $project->stories
            ));
        }

        $table = new Table($output);
        $table->setHeaders(array_keys((array) $stats));
        $table->setRows([array_values((array) $stats)]);
        $table->render();

        $out = $input->getArgument('output');
        file_put_contents($out, serialize($projects));
    }

    /**
     * @param Project[] $projects
     */
    private function renderProjects(OutputInterface $output, array $projects)
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

    /**
     * @param Story[] $stories
     */
    private function renderStories(OutputInterface $output, array $stories)
    {
        $table = new Table($output);
        $table->setHeaders(['ID', 'Text', 'Creator', 'Owner', 'Comments', 'Status']);
        $table->setRows(array_map(
            function ($story) {
                $text = mb_substr(str_replace(
                    ["\n", "\t"],
                    ['\n', '\t'],
                    $story->text
                ), 0, 40);

                return [
                    $story->id,
                    $text,
                    $story->creator->email,
                    $story->owner ? $story->owner->email : '',
                    count($story->comments),
                    $story->status,
                ];
            },
            $stories
        ));
        $table->render();
    }
}
