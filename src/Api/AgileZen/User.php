<?php

namespace AgileZenToRedmine\Api\AgileZen;

use AgileZenToRedmine\Marshallable;

class User implements Marshallable
{
    use \lpeltier\Struct;

    /// @var int
    public $id;

    /// @var string human name
    public $name;

    /// @var string login
    public $userName;

    /// @var string
    public $email;

    public static function marshal(array $raw)
    {
        $author = new User($raw['author']);
        return new self(compact('author') + $raw);
    }
}
