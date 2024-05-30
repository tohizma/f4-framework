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

//! Image manipulation tools
class Image
{
    //@{ Messages
	// phpcs:disable Generic.NamingConventions.UpperCaseConstantName.ClassConstantNotUpperCase
    public const E_Color = 'Invalid color specified: %s';
    public const E_File = 'File not found';
    public const E_Font = 'CAPTCHA font not found';
    public const E_TTF = 'No TrueType support in GD module';
    public const E_Length = 'Invalid CAPTCHA length: %s';
    //@}

    //@{ Positional cues
    public const    POS_Left = 1;
    public const    POS_Center = 2;
    public const    POS_Right = 4;
    public const    POS_Top = 8;
    public const    POS_Middle = 16;
    public const    POS_Bottom = 32;
    //@}
	// phpcs:enable Generic.NamingConventions.UpperCaseConstantName.ClassConstantNotUpperCase

    /** @var string Source filename */
    protected $file;
    /** @var \GdImage|resource Image resource */
    protected $data;
    /** @var bool Enable/disable history */
    protected $flag = false;
    /** @var int Filter count */
    protected $count = 0;

    /**
    *   Instantiate image
    *   @param string $file
    *   @param bool $flag
    *   @param string $path
    **/
    public function __construct($file = null, $flag = false, $path = null)
    {
        $this->flag = $flag;
        if ($file) {
            $fw = Base::instance();
            // Create image from file
            $this->file = $file;
            if (!isset($path)) {
                $path = $fw->UI . ';./';
            }
            foreach ($fw->split($path, false) as $dir) {
                if (is_file($dir . $file)) {
                    return $this->load($fw->read($dir . $file));
                }
            }
            user_error(self::E_File, E_USER_ERROR);
        }
    }

    /**
    *   Wrap-up
    *   @return NULL
    **/
    public function __destruct()
    {
        if (is_resource($this->data)) {
            imagedestroy($this->data); 
            $fw = Base::instance();
            $path = $fw->TEMP . $fw->SEED . '.' . $fw->hash($this->file);
            if ($glob = @glob($path . '*.png', GLOB_NOSORT)) {
                foreach ($glob as $match) {
                    if (preg_match('/-(\d+)\.png/', $match)) {
                        @unlink($match);
                    }
                }
            }
        }
    }

    /**
    *   Convert RGB hex triad to array
    *   @return array|FALSE
    *   @param int|string $color
    **/
    public function rgb($color)
    {
        if (is_string($color)) {
            $color = hexdec($color);
        }
        $hex = str_pad($hex = dechex($color), $color < 4096 ? 3 : 6, '0', STR_PAD_LEFT);
        if (($len = strlen($hex)) > 6) {
            user_error(sprintf(self::E_Color, '0x' . $hex), E_USER_ERROR);
        }
        $color = str_split($hex, $len / 3);
        foreach ($color as &$hue) {
            $hue = hexdec(str_repeat($hue, 6 / $len));
            unset($hue);
        }
        return $color;
    }

    /**
    *   Invert image
    *   @return object
    **/
    public function invert()
    {
        imagefilter($this->data, IMG_FILTER_NEGATE);
        return $this->save();
    }

    /**
    *   Adjust brightness (range:-255 to 255)
    *   @return object
    *   @param int $level
    **/
    public function brightness($level)
    {
        imagefilter($this->data, IMG_FILTER_BRIGHTNESS, $level);
        return $this->save();
    }

    /**
    *   Adjust contrast (range:-100 to 100)
    *   @return object
    *   @param int $level
    **/
    public function contrast($level)
    {
        imagefilter($this->data, IMG_FILTER_CONTRAST, $level);
        return $this->save();
    }

    /**
    *   Convert to grayscale
    *   @return object
    **/
    public function grayscale()
    {
        imagefilter($this->data, IMG_FILTER_GRAYSCALE);
        return $this->save();
    }

    /**
    *   Adjust smoothness
    *   @return object
    *   @param int $level
    **/
    public function smooth($level)
    {
        imagefilter($this->data, IMG_FILTER_SMOOTH, $level);
        return $this->save();
    }

    /**
    *   Emboss the image
    *   @return object
    **/
    public function emboss()
    {
        imagefilter($this->data, IMG_FILTER_EMBOSS);
        return $this->save();
    }

    /**
    *   Apply sepia effect
    *   @return object
    **/
    public function sepia()
    {
        imagefilter($this->data, IMG_FILTER_GRAYSCALE);
        imagefilter($this->data, IMG_FILTER_COLORIZE, 90, 60, 45);
        return $this->save();
    }

    /**
    *   Pixelate the image
    *   @return object
    *   @param int $size
    **/
    public function pixelate($size)
    {
        imagefilter($this->data, IMG_FILTER_PIXELATE, $size, true);
        return $this->save();
    }

    /**
    *   Blur the image using Gaussian filter
    *   @return object
    *   @param bool $selective
    **/
    public function blur($selective = false)
    {
        imagefilter(
            $this->data,
            $selective ? IMG_FILTER_SELECTIVE_BLUR : IMG_FILTER_GAUSSIAN_BLUR
        );
        return $this->save();
    }

    /**
    *   Apply sketch effect
    *   @return object
    **/
    public function sketch()
    {
        imagefilter($this->data, IMG_FILTER_MEAN_REMOVAL);
        return $this->save();
    }

    /**
    *   Flip on horizontal axis
    *   @return object
    **/
    public function hflip()
    {
        $tmp = imagecreatetruecolor(
            $width = $this->width(),
            $height = $this->height()
        );
        imagesavealpha($tmp, true);
        imagefill($tmp, 0, 0, IMG_COLOR_TRANSPARENT);
        imagecopyresampled(
            $tmp,
            $this->data,
            0,
            0,
            $width - 1,
            0,
            $width,
            $height,
            -$width,
            $height
        );
        imagedestroy($this->data);
        $this->data = $tmp;
        return $this->save();
    }

    /**
    *   Flip on vertical axis
    *   @return object
    **/
    public function vflip()
    {
        $tmp = imagecreatetruecolor(
            $width = $this->width(),
            $height = $this->height()
        );
        imagesavealpha($tmp, true);
        imagefill($tmp, 0, 0, IMG_COLOR_TRANSPARENT);
        imagecopyresampled(
            $tmp,
            $this->data,
            0,
            0,
            0,
            $height - 1,
            $width,
            $height,
            $width,
            -$height
        );
        imagedestroy($this->data);
        $this->data = $tmp;
        return $this->save();
    }

    /**
    *   Crop the image
    *   @return object
    *   @param int $x1
    *   @param int $y1
    *   @param int $x2
    *   @param int $y2
    **/
    public function crop($x1, $y1, $x2, $y2)
    {
        $tmp = imagecreatetruecolor($width = $x2 - $x1 + 1, $height = $y2 - $y1 + 1);
        imagesavealpha($tmp, true);
        imagefill($tmp, 0, 0, IMG_COLOR_TRANSPARENT);
        imagecopyresampled(
            $tmp,
            $this->data,
            0,
            0,
            $x1,
            $y1,
            $width,
            $height,
            $width,
            $height
        );
        imagedestroy($this->data);
        $this->data = $tmp;
        return $this->save();
    }

    /**
    *   Resize image (Maintain aspect ratio); Crop relative to center
    *   if flag is enabled; Enlargement allowed if flag is enabled
    *   @return object
    *   @param int $width
    *   @param int $height
    *   @param bool $crop
    *   @param bool $enlarge
    **/
    public function resize($width = null, $height = null, $crop = true, $enlarge = true)
    {
        if (is_null($width) && is_null($height)) {
            return $this;
        }
        $origw = $this->width();
        $origh = $this->height();
        if (is_null($width)) {
            $width = round(($height / $origh) * $origw);
        }
        if (is_null($height)) {
            $height = round(($width / $origw) * $origh);
        }
        // Adjust dimensions; retain aspect ratio
        $ratio = $origw / $origh;
        if (!$crop) {
            if ($width / $ratio <= $height) {
                $height = round($width / $ratio);
            } else {
                $width = round($height * $ratio);
            }
        }
        if (!$enlarge) {
            $width = min($origw, $width);
            $height = min($origh, $height);
        }
        // Create blank image
        $tmp = imagecreatetruecolor($width, $height);
        imagesavealpha($tmp, true);
        imagefill($tmp, 0, 0, IMG_COLOR_TRANSPARENT);
        // Resize
        if ($crop) {
            if ($width / $ratio <= $height) {
                $cropw = (int) round($origh * $width / $height);
                imagecopyresampled(
                    $tmp,
                    $this->data,
                    0,
                    0,
                    (int) round(($origw - $cropw) / 2),
                    0,
                    $width,
                    $height,
                    $cropw,
                    $origh
                );
            } else {
                $croph = (int) round($origw * $height / $width);
                imagecopyresampled(
                    $tmp,
                    $this->data,
                    0,
                    0,
                    0,
                    (int) round(($origh - $croph) / 2),
                    $width,
                    $height,
                    $origw,
                    $croph
                );
            }
        } else {
            imagecopyresampled(
                $tmp,
                $this->data,
                0,
                0,
                0,
                0,
                $width,
                $height,
                $origw,
                $origh
            );
        }
        imagedestroy($this->data);
        $this->data = $tmp;
        return $this->save();
    }

    /**
    *   Rotate image
    *   @return object
    *   @param int $angle
    **/
    public function rotate($angle)
    {
        $this->data = imagerotate(
            $this->data,
            $angle,
            imagecolorallocatealpha($this->data, 0, 0, 0, 127)
        );
        imagesavealpha($this->data, true);
        return $this->save();
    }

    /**
    *   Apply an image overlay
    *   @return object
    *   @param object $img
    *   @param int|array $align
    *   @param int $alpha
    **/
    public function overlay(Image $img, $align = null, $alpha = 100)
    {
        if (is_null($align)) {
            $align = self::POS_Right | self::POS_Bottom;
        }
        if (is_array($align)) {
            list($posx,$posy) = $align;
            $align = 0;
        }
        $ovr = imagecreatefromstring($img->dump());
        imagesavealpha($ovr, true);
        $imgw = $this->width();
        $imgh = $this->height();
        $ovrw = imagesx($ovr);
        $ovrh = imagesy($ovr);
        if ($align & self::POS_Left) {
            $posx = 0;
        }
        if ($align & self::POS_Center) {
            $posx = round(($imgw - $ovrw) / 2);
        }
        if ($align & self::POS_Right) {
            $posx = $imgw - $ovrw;
        }
        if ($align & self::POS_Top) {
            $posy = 0;
        }
        if ($align & self::POS_Middle) {
            $posy = round(($imgh - $ovrh) / 2);
        }
        if ($align & self::POS_Bottom) {
            $posy = $imgh - $ovrh;
        }
        if (empty($posx)) {
            $posx = 0;
        }
        if (empty($posy)) {
            $posy = 0;
        }
        if ($alpha == 100) {
            imagecopy($this->data, $ovr, $posx, $posy, 0, 0, $ovrw, $ovrh);
        } else {
            $cut = imagecreatetruecolor($ovrw, $ovrh);
            imagecopy($cut, $this->data, 0, 0, $posx, $posy, $ovrw, $ovrh);
            imagecopy($cut, $ovr, 0, 0, 0, 0, $ovrw, $ovrh);
            imagecopymerge(
                $this->data,
                $cut,
                $posx,
                $posy,
                0,
                0,
                $ovrw,
                $ovrh,
                $alpha
            );
        }
        return $this->save();
    }

    /**
    *   Generate identicon
    *   @return object
    *   @param string $str
    *   @param int $size
    *   @param int $blocks
    **/
    public function identicon($str, $size = 64, $blocks = 4)
    {
        $sprites = [
            [.5,1,1,0,1,1],
            [.5,0,1,0,.5,1,0,1],
            [.5,0,1,0,1,1,.5,1,1,.5],
            [0,.5,.5,0,1,.5,.5,1,.5,.5],
            [0,.5,1,0,1,1,0,1,1,.5],
            [1,0,1,1,.5,1,1,.5,.5,.5],
            [0,0,1,0,1,.5,0,0,.5,1,0,1],
            [0,0,.5,0,1,.5,.5,1,0,1,.5,.5],
            [.5,0,.5,.5,1,.5,1,1,.5,1,.5,.5,0,.5],
            [0,0,1,0,.5,.5,1,.5,.5,1,.5,.5,0,1],
            [0,.5,.5,1,1,.5,.5,0,1,0,1,1,0,1],
            [.5,0,1,0,1,1,.5,1,1,.75,.5,.5,1,.25],
            [0,.5,.5,0,.5,.5,1,0,1,.5,.5,1,.5,.5,0,1],
            [0,0,1,0,1,1,0,1,1,.5,.5,.25,.5,.75,0,.5,.5,.25],
            [0,.5,.5,.5,.5,0,1,0,.5,.5,1,.5,.5,1,.5,.5,0,1],
            [0,0,1,0,.5,.5,.5,0,0,.5,1,.5,.5,1,.5,.5,0,1]
        ];
        $hash = sha1($str);
        $this->data = imagecreatetruecolor($size, $size);
        list($r,$g,$b) = $this->rgb(hexdec(substr($hash, -3)));
        $fg = imagecolorallocate($this->data, $r, $g, $b);
        imagefill($this->data, 0, 0, IMG_COLOR_TRANSPARENT);
        $ctr = count($sprites);
        $dim = $blocks * floor($size / $blocks) * 2 / $blocks;
        for ($j = 0,$y = ceil($blocks / 2); $j < $y; ++$j) {
            for ($i = $j,$x = $blocks - 1 - $j; $i < $x; ++$i) {
                $sprite = imagecreatetruecolor($dim, $dim);
                imagefill($sprite, 0, 0, IMG_COLOR_TRANSPARENT);
                $block = $sprites[hexdec($hash[($j * $blocks + $i) * 2]) % $ctr];
                for ($k = 0,$pts = count($block); $k < $pts; ++$k) {
                    $block[$k] *= $dim;
                }
                if (version_compare(PHP_VERSION, '8.1.0') >= 0) {
                    imagefilledpolygon($sprite, $block, $fg);
                } else {
                    imagefilledpolygon($sprite, $block, $pts / 2, $fg);
                }
                for ($k = 0; $k < 4; ++$k) {
                    imagecopyresampled(
                        $this->data,
                        $sprite,
                        (int) round($i * $dim / 2),
                        (int) round($j * $dim / 2),
                        0,
                        0,
                        (int) round($dim / 2),
                        (int) round($dim / 2),
                        $dim,
                        $dim
                    );
                    $this->data = imagerotate(
                        $this->data,
                        90,
                        imagecolorallocatealpha($this->data, 0, 0, 0, 127)
                    );
                }
                imagedestroy($sprite);
            }
        }
        imagesavealpha($this->data, true);
        return $this->save();
    }

    /**
    *   Generate CAPTCHA image
    *   @return object|FALSE
    *   @param string $font
    *   @param int $size
    *   @param int $len
    *   @param string $key
    *   @param string $path
    *   @param int $fg
    *   @param int $bg
    **/
    public function captcha(
        $font,
        $size = 24,
        $len = 5,
        $key = null,
        $path = '',
        $fg = 0xFFFFFF,
        $bg = 0x000000
    ) {
        if ((!$ssl = extension_loaded('openssl')) && ($len < 4 || $len > 13)) {
            user_error(sprintf(self::E_Length, $len), E_USER_ERROR);
            return false;
        }
        if (!function_exists('imagettftext')) {
            user_error(self::E_TTF, E_USER_ERROR);
            return false;
        }
        $fw = Base::instance();
        foreach ($fw->split($path ?: $fw->UI . ';./') as $dir) {
            if (is_file($path = realpath($dir . $font))) {
                $seed = strtoupper(substr(
                    $ssl ? bin2hex(openssl_random_pseudo_bytes($len)) : uniqid(),
                    -$len
                ));
                $block = $size * 3;
                $tmp = [];
                for ($i = 0,$width = 0,$height = 0; $i < $len; ++$i) {
                    // Process at 2x magnification
                    $box = imagettfbbox($size * 2, 0, $path, $seed[$i]);
                    $w = $box[2] - $box[0];
                    $h = $box[1] - $box[5];
                    $char = imagecreatetruecolor($block, $block);
                    imagefill($char, 0, 0, $bg);
                    imagettftext(
                        $char,
                        $size * 2,
                        0,
                        (int) round(($block - $w) / 2),
                        (int) round($block - ($block - $h) / 2),
                        $fg,
                        $path,
                        $seed[$i]
                    );
                    $char = imagerotate(
                        $char,
                        mt_rand(-30, 30),
                        imagecolorallocatealpha($char, 0, 0, 0, 127)
                    );
                    // Reduce to normal size
                    $tmp[$i] = imagecreatetruecolor(
                        (int) round(($w = imagesx($char)) / 2),
                        (int) round(($h = imagesy($char)) / 2)
                    );
                    imagefill($tmp[$i], 0, 0, IMG_COLOR_TRANSPARENT);
                    imagecopyresampled(
                        $tmp[$i],
                        $char,
                        0,
                        0,
                        0,
                        0,
                        (int) round($w / 2),
                        (int) round($h / 2),
                        $w,
                        $h
                    );
                    imagedestroy($char);
                    $width += $i + 1 < $len ? $block / 2 : $w / 2;
                    $height = max($height, $h / 2);
                }
                $this->data = imagecreatetruecolor((int) round($width), (int) round($height));
                imagefill($this->data, 0, 0, IMG_COLOR_TRANSPARENT);
                for ($i = 0; $i < $len; ++$i) {
                    imagecopy(
                        $this->data,
                        $tmp[$i],
                        (int) round($i * $block / 2),
                        (int) round(($height - imagesy($tmp[$i])) / 2),
                        0,
                        0,
                        imagesx($tmp[$i]),
                        imagesy($tmp[$i])
                    );
                    imagedestroy($tmp[$i]);
                }
                imagesavealpha($this->data, true);
                if ($key) {
                    $fw->$key = $seed;
                }
                return $this->save();
            }
        }
        user_error(self::E_Font, E_USER_ERROR);
        return false;
    }

    /**
    *   Return image width
    *   @return int
    **/
    public function width()
    {
        return imagesx($this->data);
    }

    /**
    *   Return image height
    *   @return int
    **/
    public function height()
    {
        return imagesy($this->data);
    }

    /**
    *   Send image to HTTP client
    *   @return NULL
    **/
    public function render()
    {
        $args = func_get_args();
        $format = $args ? array_shift($args) : 'png';
        if (PHP_SAPI != 'cli') {
            header('Content-Type: image/' . $format);
            header('X-Powered-By: ' . Base::instance()->PACKAGE);
        }
        call_user_func_array(
            'image' . $format,
            array_merge([$this->data,null], $args)
        );
    }

    /**
    *   Return image as a string
    *   @return string
    **/
    public function dump()
    {
        $args = func_get_args();
        $format = $args ? array_shift($args) : 'png';
        ob_start();
        call_user_func_array(
            'image' . $format,
            array_merge([$this->data,null], $args)
        );
        return ob_get_clean();
    }

    /**
    *   Return image resource
    *   @return resource
    **/
    public function data()
    {
        return $this->data;
    }

    /**
    *   Save current state
    *   @return object
    **/
    public function save()
    {
        $fw = Base::instance();
        if ($this->flag) {
            if (!is_dir($dir = $fw->TEMP)) {
                mkdir($dir, Base::MODE, true);
            }
            ++$this->count;
            $fw->write(
                $dir . '/' . $fw->SEED . '.' .
                $fw->hash($this->file) . '-' . $this->count . '.png',
                $this->dump()
            );
        }
        return $this;
    }

    /**
    *   Revert to specified state
    *   @return object
    *   @param int $state
    **/
    public function restore($state = 1)
    {
        $fw = Base::instance();
        if ($this->flag && is_file($file = ($path = $fw->TEMP .
            $fw->SEED . '.' . $fw->hash($this->file) . '-') . $state . '.png')
        ) {
            if (is_resource($this->data)) {
                imagedestroy($this->data);
            }
            $this->data = imagecreatefromstring($fw->read($file));
            imagesavealpha($this->data, true);
            foreach (glob($path . '*.png', GLOB_NOSORT) as $match) {
                if (preg_match('/-(\d+)\.png/', $match, $parts) &&
                    $parts[1] > $state
                ) {
                    @unlink($match);
                }
            }
            $this->count = $state;
        }
        return $this;
    }

    /**
    *   Undo most recently applied filter
    *   @return object
    **/
    public function undo()
    {
        if ($this->flag) {
            if ($this->count) {
                $this->count--;
            }
            return $this->restore($this->count);
        }
        return $this;
    }

    /**
    *   Load string
    *   @return object|FALSE
    *   @param string $str
    **/
    public function load($str)
    {
        if (!$this->data = @imagecreatefromstring($str)) {
            return false;
        }
        imagesavealpha($this->data, true);
        $this->save();
        return $this;
    }
}
