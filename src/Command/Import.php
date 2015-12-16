<?php

namespace AgileZenToRedmine\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use AgileZenToRedmine\Api\AgileZen\Project;
use AgileZenToRedmine\Api\AgileZen\Story;
use AgileZenToRedmine\Dump;
use AgileZenToRedmine\Redmine;

class Import extends Command
{
    use RedmineCommandTrait;

    /// @var OutputInterface
    private $output;

    /// @var Dump
    private $dump;

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
        ;
        $this->configureRedmineOptions();
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->initializeRedmineClient($input);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->dump = Dump::load($input->getOption('output-dir'));

        $this->createUsers($this->dump->getUsers());
        $this->createProjects($this->dump->projects);

        /* HACK: It seems that the client caches the project list and won't
         * update it after creating a new one. We need to destroy and recreate
         * the client to be able to fetch project ids. */
        $this->initialize($input, $output);

        try {
            foreach ($this->dump->projects as $project) {
                $this->createProjectIssues($project);
            }
        } finally {
            $this->dump->write();
        }
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
        $progress->start();
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
        $progress->start();
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

    private function createProjectIssues(Project $project)
    {
        $this->output->writeln("Create issues for project #{$project->id}.");
        $progress = new ProgressBar($this->output, count($project->stories));
        $progress->start();
        $skipped = 0;

        $redmineProjectId = $this->getRedmineProjectId($project);

        foreach ($project->stories as $story) {
            if (array_key_exists($story->id, $this->dump->storyMapping)) {
                $skipped += 1;
                $progress->advance();
                continue;
            }

            try {
                $issueId = $this->createSingleIssue($story, $project, $redmineProjectId);

                /* HACK: Can't set the status by name or id during creation (PHP API bug?)
                 * so we do it here. */
                $this->setIssueStatus($issueId, $story, $project->id);
                $this->createIssueComments($issueId, $story, $project);
                $this->dump->storyMapping[$story->id] = $issueId;

                /* Allow ^C at the price of one write per issue. An issue can take
                 * a second so this should not be a problem. */
                $this->dump->write();
            } catch (\RuntimeException $e) {
                $this->output->writeln("Failed to create issue for story {$story->id}");
            }

            $progress->advance();
        }

        $progress->finish();
        $this->output->writeln('');
        $this->output->writeln("\nDone creating issues, skipped: $skipped");
    }

    /**
     * @param int $redmineProjectId in which Redmine project this issue will be
     * created.
     * @return int created Redmine issue ID.
     */
    private function createSingleIssue(Story $story, Project $project, $redmineProjectId)
    {
        static $users = null;
        if ($users === null) {
            $users = $this->redmine->api('user')->listing();
        }

        $this->redmine->setImpersonateUser(
            Redmine\login_from_agilezen_user($story->creator)
        );

        $assignedToId = null;
        if ($story->owner !== null) {
            $assignedToId = $users[Redmine\login_from_agilezen_user($story->owner)];
        }

        $res = $this->redmine->api('issue')->create([
            'project_id' => $redmineProjectId,
            'subject' => Redmine\subject_from_agilezen_story($story),
            'description' => Redmine\description_from_agilezen_story($story, $project),
            'assigned_to_id' => $assignedToId,
            'uploads' => $this->uploadStoryAttachments($story),
        ]);

        $this->redmine->setImpersonateUser(null);

        if (empty($res->id)) {
            $error = empty($res->error) ? 'no response' : $res->error;
            throw new \RuntimeException("Unable to create issue for story #{$story->id}: $error.");
        }
        $issueId = (int) ((string) $res->id);

        return $issueId;
    }

    /**
     * @return string[] parameters to feed redmine->api('issue')->attach
     */
    private function uploadStoryAttachments(Story $story)
    {
        $uploads = [];

        foreach ($story->attachments as $attachment) {
            $res = json_decode($this->redmine->api('attachment')->upload(
                file_get_contents($this->dump->getAttachmentPath($attachment))
            ));

            if (empty($res->upload) || empty($res->upload->token)) {
                $errors = implode("\n", $res->errors);
                throw new \RuntimeException("Unable to upload attachment #{$attachment->id}: $errors.");
            }

            $uploads[] = [
                'token' => $res->upload->token,
                'filename' => $attachment->fileName,
                'description' => Redmine\description_from_agilezen_attachment($attachment),
                'content_type' => $attachment->contentType,
            ];
        }

        return $uploads;
    }

    /**
     * @return int $issueId
     */
    private function createIssueComments($issueId, Story $story, Project $project)
    {
        foreach ($story->comments as $comment) {
            $this->redmine->setImpersonateUser(
                Redmine\login_from_agilezen_user($comment->author)
            );

            $this->redmine->api('issue')->addNoteToIssue(
                $issueId,
                Redmine\note_from_agilezen_comment($comment, $story, $project)
            );

            $this->redmine->setImpersonateUser(null);
        }
    }

    /// @return int
    private function getRedmineProjectId(Project $project)
    {
        return $this->redmine->api('project')->getIdByName($project->name);
    }

    /**
     * @param int $issueId
     * @param int $agilezenProjectId which project to use to get phases map.
     * @return int
     */
    private function setIssueStatus($issueId, Story $story, $agilezenProjectId)
    {
        assert('count($this->dump->phaseMap) > 0');
        $status = $this->dump->phaseMap[$agilezenProjectId][$story->phase->name];
        $this->redmine->api('issue')->update($issueId, compact('status'));
    }
}
