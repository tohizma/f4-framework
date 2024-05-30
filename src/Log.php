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

//! Custom logger
class Log
{
    /** @var string File name */
    protected $file;

    /**
    *   Instantiate class
    *   @param string $file
    **/
    public function __construct($file)
    {
        $fw = Base::instance();
        if (!is_dir($dir = $fw->LOGS)) {
            mkdir($dir, Base::MODE, true);
        }
        $this->file = $dir . $file;
    }

    /**
    *   Write specified text to log file
    *   @return string
    *   @param string $text
    *   @param string $format
    **/
    public function write($text, $format = 'r')
    {
        $fw = Base::instance();
        foreach (preg_split('/\r?\n|\r/', trim($text)) as $line) {
            $fw->write(
                $this->file,
                date($format) .
                (isset($_SERVER['REMOTE_ADDR']) ?
                    (' [' . $_SERVER['REMOTE_ADDR'] .
                    (($fwd = filter_var(
                        $fw->get('HEADERS.X-Forwarded-For'),
                        FILTER_VALIDATE_IP
                    )) ? (' (' . $fwd . ')') : '')
                    . ']') : '') . ' ' .
                trim($line) . PHP_EOL,
                true
            );
        }
    }

    /**
    *   Erase log
    *   @return NULL
    **/
    public function erase()
    {
        unlink($this->file);
    }    
}
