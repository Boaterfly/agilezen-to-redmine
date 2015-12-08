<?php

namespace AgileZenToRedmine\Api\AgileZen;

use AgileZenToRedmine\Marshallable;

class Story implements Marshallable
{
    use \lpeltier\Struct;

    /// @var int
    public $id;

    /// @var Project
    public $project;

    /// @var User
    public $creator;

    /// @var Phase
    public $phase;

    /// @var User|null
    public $owner;

    /// @var Comment[]
    public $comments = [];

    /// @var Attachment[]
    public $attachments = [];

    /// @var Steps[]
    public $steps = [];

    /// @var string
    public $text;

    /// @var string
    public $details;

    /// @var string
    public $size;

    /// @var string
    public $color;

    /// @var string
    public $blockedReason;

    /// @var string
    public $status;

    /// @var string
    public $priority;

    /// @var string
    public $deadline;

    /// @var Tag[]
    public $tags;

    public static function marshal(array $raw)
    {
        $tags = array_map(
            Tag::class . '::marshal',
            $raw['tags']
        );

        $comments = array_map(
            Comment::class . '::marshal',
            $raw['comments']
        );

        $steps = array_map(
            Step::class . '::marshal',
            $raw['steps']
        );

        $owner = array_key_exists('owner', $raw)
            ? new User($raw['owner'])
            : null
        ;

        $creator = new User($raw['creator']);
        $phase = new Phase($raw['phase']);

        return new self(
            compact('phase', 'owner', 'creator', 'comments', 'steps') + $raw
        );
    }

    /// @return string
    public function getCreateTime()
    {
        if (count($this->steps) <= 0) {
            throw new \RuntimeException('Can\'t get steps for story.');
        }

        /* HACK: API returns steps in order and array order should be
         * preserved, I hope this does not come back to bite me. */
        return $this->steps[0]->startTime;
    }
}
