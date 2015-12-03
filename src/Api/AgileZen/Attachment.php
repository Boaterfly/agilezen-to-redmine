<?php

namespace AgileZenToRedmine\Api\AgileZen;

use AgileZenToRedmine\Marshallable;

class Attachment implements Marshallable
{
    use \lpeltier\Struct;
    use \AgileZenToRedmine\PrettyJsonString;

    /// @var int
    public $id;

    /// @var string
    public $fileName;

    /// @var string
    public $contentType;

    /// @var string
    public $token;

    /// @var int
    public $sizeInBytes;

    public static function marshal(array $raw)
    {
        return new self($raw);
    }
}
