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
use AgileZenToRedmine\Dump;

class DownloadAttachments extends Command
{
    protected function configure()
    {
        $this
            ->setName('download-attachments')
            ->setDescription('Download attachments from AgileZen.')
            ->addOption(
                'output-dir',
                null,
                InputOption::VALUE_REQUIRED,
                'Where to write the exported data.',
                'export'
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
        $outputDir = $input->getOption('output-dir');
        $dump = Dump::load($outputDir);

        $dump->attachmentsDir = "$outputDir/attachments";
        assert_writable_dir($dump->attachmentsDir);
        $dump->write();

        $output->writeln('Get authenticated client.');
        $client = $this->getClient($input);

        $output->writeln('Start downloading files.');
        $progress = new ProgressBar($output, $dump->getTotalAttachmentSize());
        $progress->start();

        foreach ($dump->projects as $project) {
            foreach ($project->stories as $story) {
                foreach ($story->attachments as $attachment) {
                    $path = $dump->getAttachmentPath($attachment);
                    $this->downloadAttachment($client, $path, $project, $story, $attachment);
                    $progress->advance($attachment->sizeInBytes);
                }
            }
        }

        $progress->finish();
        $output->writeln('');

        $maxSize = round($dump->getBiggestAttachmentSize() / 1024);
        $output->writeln("Please ensure that Redmine maximum attachment size is greater than $maxSize.");
    }

    /**
     * @param string $path
     */
    private function downloadAttachment(
        Client $client,
        $path,
        Project $project,
        Story $story,
        Attachment $attachment
    ) {
        if (file_exists($path) && filesize($path) === $attachment->sizeInBytes) {
            return;
        }

        $uri = "project/{$project->id}"
            . "/story/{$story->id}"
            . "/attachment/{$attachment->id}"
            . '/download/' . rtrim(trim($attachment->fileName), '.')
        ;

        $out = fopen($path, 'w+');
        $client->get($uri, [
            'sink' => $out
        ]);
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
