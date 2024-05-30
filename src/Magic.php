<?php

/*

    Copyright (c) 2009-2019 F3::Factory/Bong Cosca, All rights reserved.

    This file is part of the Fat-Free Framework (http://fatfreeframework.com).

    This is free software: you can redistribute it and/or modify it under the
    terms of the GNU General Public License as published by the Free Software
    Foundation, either version 3 of the License, or later.

    Fat-Free Framework is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
    General Public License for more details.

    You should have received a copy of the GNU General Public License along
    with Fat-Free Framework.  If not, see <http://www.gnu.org/licenses/>.

*/
declare(strict_types=1);

namespace F4;

use ArrayAccess;

//! PHP magic wrapper
abstract class Magic implements ArrayAccess
{
    /**
    *   Return TRUE if key is not empty
    *   @return bool
    *   @param string $key
    **/
    abstract public function exists($key);

    /**
    *   Bind value to key
    *   @return mixed
    *   @param string $key
    *   @param mixed $val
    **/
    abstract public function set($key, $val);

    /**
    *   Retrieve contents of key
    *   @return mixed
    *   @param string $key
    **/
    abstract public function &get($key);

    /**
    *   Unset key
    *   @return NULL
    *   @param string $key
    **/
    abstract public function clear($key);

    /**
    *   Convenience method for checking property value
    *   @return mixed
    *   @param string $key
    **/
    #[\ReturnTypeWillChange]
    public function offsetExists($key)
    {
        return Base::instance()->visible($this, $key) ?
            isset($this->$key) :
            ($this->exists($key) && $this->get($key) !== null);
    }

    /**
    *   Convenience method for assigning property value
    *   @return mixed
    *   @param string $key
    *   @param mixed $val
    **/
    #[\ReturnTypeWillChange]
    public function offsetSet($key, $val)
    {
        return Base::instance()->visible($this, $key) ?
            ($this->$key = $val) : $this->set($key, $val);
    }

    /**
    *   Convenience method for retrieving property value
    *   @return mixed
    *   @param string $key
    **/
    #[\ReturnTypeWillChange]
    public function &offsetGet($key)
    {
        if (Base::instance()->visible($this, $key)) {
            $val=&$this->$key;
        } else {
            $val=&$this->get($key);
        }
        return $val;
    }

    /**
    *   Convenience method for removing property value
    *   @return void
    *   @param string $key
    **/
    #[\ReturnTypeWillChange]
    public function offsetUnset($key) : void
    {
        if (Base::instance()->visible($this, $key)) {
            unset($this->$key);
        } else {
            $this->clear($key);
        }
    }

    /**
    *   Alias for offsetexists()
    *   @return mixed
    *   @param string $key
    **/
    public function __isset($key)
    {
        return $this->offsetExists($key);
    }

    /**
    *   Alias for offsetset()
    *   @return mixed
    *   @param string $key
    *   @param mixed $val
    **/
    public function __set($key, $val)
    {
        return $this->offsetSet($key, $val);
    }

    /**
    *   Alias for offsetget()
    *   @return mixed
    *   @param string $key
    **/
    public function &__get($key)
    {
        $val=&$this->offsetGet($key);
        return $val;
    }

    /**
    *   Alias for offsetunset()
    *   @param string $key
    **/
    public function __unset($key)
    {
        $this->offsetUnset($key);
    }
}
