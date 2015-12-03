<?php

namespace AgileZenToRedmine\Command;

use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use AgileZenToRedmine\Api\AgileZen\Attachment;
use AgileZenToRedmine\Api\AgileZen\Project;
use AgileZenToRedmine\Api\AgileZen\Story;

class DownloadAttachments extends Command
{
    protected function configure()
    {
        $this
            ->setName('download-attachments')
            ->setDescription('Download attachments from AgileZen.')
            ->addArgument(
                'output-dir',
                InputArgument::REQUIRED,
                'Where to read and write the exported data.'
            )
            ->addOption(
                'user',
                null,
                InputOption::VALUE_REQUIRED,
                'AgileZen user.'
            )
            ->addOption(
                'password',
                null,
                InputOption::VALUE_REQUIRED,
                'AgileZen password.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $outputDir = $input->getArgument('output-dir');
        if (!file_exists("$outputDir/agilezen.dat")) {
            throw new \RuntimeException('No exported data found at given output dir.');
        }

        $attachmentsDir = "$outputDir/attachments";
        if (!file_exists($attachmentsDir)) {
            if (!mkdir($attachmentsDir, 0775)) {
                throw new \RuntimeException('Unable to create directory for downloaded attachments.');
            }
        }

        $projects = unserialize(file_get_contents("$outputDir/agilezen.dat"));
        $progress = new ProgressBar($output, $this->getAttachmentSize($projects));
        $output->writeln('Get authenticated client.');
        $client = $this->getClient($input);

        $output->writeln('Start downloading files.');
        foreach ($projects as $project) {
            foreach ($project->stories as $story) {
                foreach ($story->attachments as $attachment) {
                    $path = "$attachmentsDir/{$attachment->id}";
                    $this->downloadAttachment($client, $path, $project, $story, $attachment);
                    $progress->advance($attachment->sizeInBytes);
                }
            }
        }

        $progress->finish();
        $output->writeln('');
    }

    private function downloadAttachment(Client $client, $path, Project $project, Story $story, Attachment $attachment)
    {
        if (file_exists($path) && filesize($path) === $attachment->sizeInBytes) {
            return;
        }

        $uri = "project/{$project->id}"
            . "/story/{$story->id}"
            . "/attachment/{$attachment->id}"
            . '/download/' . trim($attachment->fileName)
        ;

        $out = fopen($path, 'w+');
        $client->get($uri, [
            'sink' => $out
        ]);
    }

    /**
     * @param Project[] $projects
     * @return int
     */
    private function getAttachmentSize(array $projects)
    {
        $totalSize = 0;

        foreach ($projects as $project) {
            foreach ($project->stories as $story) {
                $totalSize += array_sum(collection_column($story->attachments, 'sizeInBytes'));
            }
        }

        return $totalSize;
    }


    /**
     * @return GuzzleHttp\Client authentificated client ready to download
     * attachments.
     */
    private function getClient(InputInterface $input)
    {
        $user     = $input->getOption('user');
        $password = $input->getOption('password');
        foreach (['user', 'password'] as $k) {
            if (!is_string($$k) || strlen($$k) <= 0) { // sue me
                throw new \RuntimeException("Option --$k is required.");
            }
        }

        $client = new Client([
            'base_uri' => 'https://agilezen.com/',
            'cookies' => true,
        ]);
        $client->get('login');
        $client->request('POST', 'login', [
            'headers' => [
                'X-Requested-With' => 'XMLHttpRequest',
                'Accept' => 'application/json',
                'Accept-Encoding' => 'gzip',
            ],
            'form_params' => [
                'userName' => $user,
                'password' => $password,
                'timezoneOffset' => 1, // arbitrary
            ]
        ]);

        return $client;
    }
}
