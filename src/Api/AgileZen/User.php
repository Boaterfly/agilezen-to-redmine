<?php

namespace AgilezenToRedmine\Api\AgileZen;

class User
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
}
