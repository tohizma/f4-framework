<?php

declare(strict_types=1);

namespace F4;

use ReflectionClass;

//! Factory class for single-instance objects
abstract class Prefab
{
    /**
    *   Return class instance
    *   @return static
    **/
    public static function instance()
    {
        if (!Registry::exists($class = get_called_class())) {
            $ref = new ReflectionClass($class);
            $args = func_get_args();
            Registry::set(
                $class,
                $args ? $ref->newinstanceargs($args) : new $class()
            );
        }
        return Registry::get($class);
    }
}
