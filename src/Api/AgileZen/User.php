<?php

namespace AgileZenToRedmine\Api\AgileZen;

class User
{
    use \lpeltier\Struct;
    use \AgileZenToRedmine\PrettyJsonString;

    /// @var int
    public $id;

    /// @var string human name
    public $name;

    /// @var string login
    public $userName;

    /// @var string
    public $email;
}
