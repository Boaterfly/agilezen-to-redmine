<?php

namespace AgileZenToRedmine\Api\AgileZen;

class Story
{
    use \lpeltier\Struct;
    use \AgileZenToRedmine\PrettyJsonString;

    /// @var int
    public $id;

    /// @var Project
    public $project;

    /// @var User
    public $creator;

    /// @var Phase
    public $phase;

    /// @var User
    public $owner;

    /// @var Comment[]
    public $comments = [];

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

    public static function marshal($raw)
    {
        $tags = array_map(
            Tag::class . '::marshal',
            $raw['tags']
        );

        $comments = array_map(
            Comment::class . '::marshal',
            $raw['comments']
        );

        $owner = array_key_exists('owner', $raw)
            ? new User($raw['owner'])
            : null
        ;

        $creator = new User($raw['creator']);

        return new self(
            compact('owner', 'creator', 'comments') + $raw
        );
    }
}
