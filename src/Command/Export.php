<?php

namespace AgileZenToRedmine\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use AgileZenToRedmine\Api\AgileZen;

class Export extends Command
{
    /// @var AgileZen
    private $api;

    /// @var string where to store exported files.
    private $outputDir;

    protected function configure()
    {
        $this
            ->setName('export')
            ->setDescription('Export data from AgileZen.')
            ->addArgument(
                'output-dir',
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

        $this->outputDir = $input->getArgument('output-dir');
        assert_writable_dir($this->outputDir);

        $apiDir = $this->outputDir . '/api';
        assert_writable_dir($apiDir);

        $this->api = new AgileZen([
            'token' => $token,
            'cache' => true,
            'cacheDir' => $apiDir,
        ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $projects = $this->api->projects();
        $this->renderProjects($projects, $output);

        foreach ($projects as $project) {
            $output->writeln("Downloading stories for project #{$project->id}.");
            $project->stories = $this->api->stories($project->id);

            $output->writeln("Downloading attachment list for project #{$project->id}.");
            $attachmentsBar = new ProgressBar($output, count($project->stories));
            $attachmentsBar->start();

            foreach ($project->stories as $story) {
                $attachmentsBar->advance();
                $story->attachments = $this->api->attachments($project->id, $story->id);
            }

            $attachmentsBar->finish();
            $output->writeln('');
        }

        file_put_contents("{$this->outputDir}/agilezen.dat", serialize($projects));
    }

    /**
     * @param Project[] $projects
     */
    private function renderProjects(array $projects, OutputInterface $output)
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
    private function renderStories(array $stories, OutputInterface $output)
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
