<?php

namespace AgileZenToRedmine\Api\AgileZen;

use AgileZenToRedmine\Marshallable;

class Project implements Marshallable
{
    use \lpeltier\Struct;

    /// @var int
    public $id;

    /// @var string
    public $name;

    /// @var string
    public $description;

    /// @var int
    public $createTime;

    /// @var User
    public $owner;

    /// @var Phase[]
    public $phases = [];

    /// @var Stories[]
    public $stories = [];

    public static function marshal(array $raw)
    {
        $owner = new User($raw['owner']);
        $phases = array_map(
            Phase::class . '::marshal',
            $raw['phases']
        );

        return new self(compact('owner', 'phases') + $raw);
    }
}
