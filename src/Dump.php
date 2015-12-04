<?php

namespace AgileZenToRedmine;

class Dump
{
    /// @var string
    private $outputDir;

    /// @var Project[]
    private $projects = [];

    private function __construct($outputDir)
    {
        if (!file_exists($outputDir) || !is_dir($outputDir)) {
            throw new \RuntimeException('Given outputDir does not exist or is not a directory.');
        }

        $this->outputDir = $outputDir;
    }

    /**
     * @return string
     */
    private function getDataPath()
    {
        return "{$this->outputDir}/agilezen.dat";
    }

    /**
     * @param string $outputDir
     * @return self
     */
    public static function load($outputDir)
    {
        $dump = new self($outputDir);
        $dump->projects = unserialize(file_get_contents(
            $dump->getDataPath()
        ));
        return $dump;
    }

    /// @return Project[]
    public function getProjects()
    {
        return $this->projects;
    }

    /**
     * Return all users mentionned in the projects, their stories and comments.
     *
     * @return User[]
     */
    public function getUsers()
    {
        $users = [];

        foreach ($this->projects as $project) {
            $users[$project->owner->id] = $project->owner;

            foreach ($project->stories as $story) {
                $users[$story->creator->id] = $story->creator;

                if ($story->owner !== null) {
                    $users[$story->owner->id] = $story->owner;
                }

                foreach ($story->comments as $comment) {
                    $users[$comment->author->id] = $comment->author;
                }
            }
        }

        return array_values($users);
    }
}
