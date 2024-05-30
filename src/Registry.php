<?php

declare(strict_types=1);

namespace F4;

//! Container for singular object instances
class Registry
{
    private static //! Object catalog
        $table;

    /**
    *   Return TRUE if object exists in catalog
    *   @return bool
    *   @param string $key
    **/
    public static function exists($key)
    {
        return isset(self::$table[$key]);
    }

    /**
    *   Add object to catalog
    *   @return object
    *   @param string $key
    *   @param object $obj
    **/
    public static function set($key, $obj)
    {
        return self::$table[$key] = $obj;
    }

    /**
    *   Retrieve object from catalog
    *   @return object
    *   @param string $key
    **/
    public static function get($key)
    {
        return self::$table[$key];
    }

    /**
    *   Delete object from catalog
    *   @param string $key
    **/
    public static function clear($key)
    {
        self::$table[$key] = null;
        unset(self::$table[$key]);
    }

    //! Prohibit cloning
    private function __clone()
    {
    }

    //! Prohibit instantiation
    private function __construct()
    {
    }
}
