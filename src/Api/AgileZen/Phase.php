<?php

namespace AgileZenToRedmine\Api\AgileZen;

use AgileZenToRedmine\Marshallable;

class Phase implements Marshallable
{
    use \lpeltier\Struct;
    use \AgileZenToRedmine\PrettyJsonString;

    /// @var int
    public $id;

    /// @var string
    public $name;

    /// @var string
    public $description;

    /// @var int
    public $index;

    public static function marshal(array $raw)
    {
        return new self($raw);
    }
}
