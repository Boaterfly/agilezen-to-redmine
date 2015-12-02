<?php

namespace AgileZenToRedmine\Api\AgileZen;

class Project
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
    public $createTime;

    /// @var User
    public $owner;
}
