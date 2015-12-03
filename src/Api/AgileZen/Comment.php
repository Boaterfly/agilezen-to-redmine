<?php

namespace AgileZenToRedmine\Api\AgileZen;

class Comment
{
    use \lpeltier\Struct;
    use \AgileZenToRedmine\PrettyJsonString;

    /// @var int
    public $id;

    /// @var string
    public $text;

    /// @var string
    public $createTime;

    /// @var User
    public $author;

    public static function marshal($raw)
    {
        return new self($raw);
    }
}
