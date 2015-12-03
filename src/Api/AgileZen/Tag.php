<?php

namespace AgileZenToRedmine\Api\AgileZen;

class Tag
{
    use \lpeltier\Struct;
    use \AgileZenToRedmine\PrettyJsonString;

    /// @var int
    public $id;

    /// @var string
    public $name;

    public static function marshal($raw)
    {
        return new self($raw);
    }
}
