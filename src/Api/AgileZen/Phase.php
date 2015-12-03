<?php

namespace AgileZenToRedmine\Api\AgileZen;

class Phase
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

    public static function marshal($raw)
    {
        return new self($raw);
    }
}
