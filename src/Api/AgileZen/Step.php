<?php

namespace AgileZenToRedmine\Api\AgileZen;

use AgileZenToRedmine\Marshallable;

class Step implements Marshallable
{
    use \lpeltier\Struct;

    /// @var int
    public $id;

    /// @var string
    public $type;

    /// @var string
    public $startTime;

    /// @var string
    public $endTime;

    /// @var int
    public $duration;

    public static function marshal(array $raw)
    {
        return new self($raw);
    }
}
