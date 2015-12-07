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
 * Ensure the given path is a directory, is writeable and has the given mode.
 *
 * If the directory does not exist, it will be created.
 * Nothing is returned, RuntimeException are thrown.
 *
 * @param string $path
 * @param int $chmod
 */
function assert_writable_dir($path, $chmod = 0755)
{
    if (!file_exists($path)) {
        if (!mkdir($path, $chmod, true)) {
            throw new \RuntimeException('Unable to create dir: ' . $path);
        }
    } else {
        if (!is_dir($path)) {
            throw new \RuntimeException('Path exists but is not a directory: ' . $path);
        }
    }

    if (!chmod($path, $chmod)) {
        throw new \RuntimeException('Unable to set permissions for path: ', $path);
    }
}
