<?php

/**
 * @param mixed $v
 * @return bool true if you can use foreach on $v.
 */
function is_iterable($v)
{
    return is_array($v) || (is_object($v) && $v instanceof \Iterator);
}

/**
 * @param mixed $v
 * @return bool true $v has properties
 */
function is_map($v)
{
    return is_array($v) || is_object($v);
}

/**
 * @param mixed $map
 * @param string $property name
 * @return mixed
 */
function map_get($map, $property)
{
    switch (true) {
        case is_array($map):
        case is_object($map) && $map instanceof \ArrayAccess:
            return $map[$property];
        case is_object($map):
            return $map->$property;
        default:
            throw new \InvalidArgumentException('Bad type for $map.');
    }
}

/**
 * @param mixed $collection
 * @param string $column
 * @return mixed[]
 */
function collection_column($collection, $column)
{
    assert('is_iterable($collection)');
    return collection_map($collection, function ($v) use ($column) {
        return map_get($v, $column);
    });
}

/**
 * @param mixed $collection
 * @return mixed[]
 */
function collection_map($collection, callable $callback)
{
    assert('is_iterable($collection)');

    if (is_array($collection)) {
        return array_map($callback, $collection);
    } else {
        $ret = [];
        foreach ($collection as $v) {
            $ret[] = $callback($v);
        }
        return $ret;
    }
}

/**
 * @param mixed $collection
 * @return mixed[]
 */
function collection_filter($collection, callable $callback)
{
    assert('is_iterable($collection)');

    if (is_array($collection)) {
        return array_filter($collection, $callback);
    } else {
        $ret = [];
        foreach ($collection as $v) {
            // implicit cast to mimic array_filter behavior
            if ($callback($v)) {
                $ret[] = $callback($v);
            }
        }
        return $ret;
    }
}

/**
 * Return the first item of the collection that has the given $field set to
 * $value.
 *
 * @param mixed $collection
 * @param string $field
 * @param mixed $value
 * @return mixed|null null if not found.
 */
function collection_find_first($collection, $field, $value)
{
    $found = collection_filter($collection, function ($map) use ($field, $value) {
        return map_get($map, $field) === $value;
    });

    return (count($found) > 0) ? reset($found) : null;
}

// Waiting for the coalesce operator.
function array_get(array $array, $key, $default = null)
{
    return array_key_exists($key, $array) ? $array[$key] : $default;
}
