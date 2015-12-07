<?php

namespace AgileZenToRedmine;

class Dump
{
    use \lpeltier\Struct;

    /// @var string where to write our data, cache, and attachments.
    private $outputDir;

    /// @var Project[]
    public $projects = [];

    /// @var int[]
    public $storyMapping = [];

    /**
     * @param string $outputDir
     */
    public function __construct($outputDir)
    {
        if (!file_exists($outputDir) || !is_dir($outputDir)) {
            throw new \RuntimeException('Given outputDir does not exist or is not a directory.');
        }

        $this->outputDir = $outputDir;
    }

    /**
     * @return string
     */
    private static function getDataPath($outputDir)
    {
        return "$outputDir/agilezen.dat";
    }

    /**
     * @param string $outputDir
     * @return self
     */
    public static function load($outputDir)
    {
        $dump = unserialize(file_get_contents(
            self::getDataPath($outputDir)
        ));
        $dump->outputDir = $outputDir;

        return $dump;
    }

    public function write()
    {
        if (!file_put_contents(self::getDataPath($this->outputDir), serialize($this))) {
            throw new \RuntimeException('Unable to write dump.');
        }
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

    /**
     * @return int
     */
    public function getTotalAttachmentSize()
    {
        $totalSize = 0;

        foreach ($this->projects as $project) {
            foreach ($project->stories as $story) {
                $totalSize += array_sum(
                    collection_column($story->attachments, 'sizeInBytes')
                );
            }
        }

        return $totalSize;
    }
}
