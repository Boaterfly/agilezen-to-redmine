<?php

namespace AgileZenToRedmine\Api\AgileZen;

use AgileZenToRedmine\Marshallable;

class Comment implements Marshallable
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

    public static function marshal(array $raw)
    {
        $author = new User($raw['author']);
        return new self(compact('author') + $raw);
    }
}
