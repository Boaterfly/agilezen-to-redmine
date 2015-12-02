<?php

namespace AgileZenToRedmine;

/// Display a pretty-printed JSON when cast to string.
trait PrettyJsonString
{
    public function __toString()
    {
        return json_encode($this, JSON_PRETTY_PRINT);
    }
}
