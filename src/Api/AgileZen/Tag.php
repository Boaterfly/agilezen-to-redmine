<?php

namespace AgileZenToRedmine\Api\AgileZen;

use AgileZenToRedmine\Marshallable;

class Tag implements Marshallable
{
    use \lpeltier\Struct;

    /// @var int
    public $id;

    /// @var string
    public $name;

    public static function marshal(array $raw)
    {
        return new self($raw);
    }
}
