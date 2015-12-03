<?php

namespace AgileZenToRedmine;

/// Can create an object from raw data.
interface Marshallable
{
    /**
     * @param mixed[] $raw
     * @return self
     */
    public static function marshal(array $raw);
}
