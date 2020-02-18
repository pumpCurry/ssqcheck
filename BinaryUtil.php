<?php

// code from:
// http://www.ajisaba.net/php/binary_util.html#BINARY_UTIL

class BinaryUtil
{
    const BIG_ENDIAN = 0;
    const LITTLE_ENDIAN = 1;

    /**
     * The buffer for data from a binary file.
     *
     * @var
     */
    private $buffer;

    private $isBigEndian = true;

    /**
     * Set endian
     *
     * @param $val
     */
    public function setEndian($val)
    {
        $this->isBigEndian = $this->checkBigEndian($val);
    }

    /**
     * Return true if the value is big endian, otherwise return false.
     *
     * @param int $endian
     * @return boolean
     */
    public function checkBigEndian($endian)
    {
        if ($endian == self::BIG_ENDIAN) {
            return true;
        } elseif ($endian == self::LITTLE_ENDIAN) {
            return false;
        } else {
            throw new Exception("Invalid endian type: $endian");
        }
    }

    /**
     * Read from the binary file and return string data of 8bit unsigned char.
     *
     * @param string $path
     * @param int $offset
     * @param int $length
     * @return array
     */
    public function read($path, $offset = 0, $length = null)
    {
        if (is_null($length)) {
            if (empty($offset)) {
                $buffer = file_get_contents($path);
            } else {
                $buffer = file_get_contents($path, false, null, $offset);
            }
        } else {
            $buffer = file_get_contents($path, false, null, $offset, $length);
        }
        if ($buffer === false) {
            throw new Exception("Can not read file: $path");
        }
        return $this->buffer = unpack('C*', $buffer);
    }

    /**
     * Return one PHP integer data of 8bit unsigned char
     *
     * @param int $offset
     * @return mixed
     */
    public function getByte($offset)
    {
        return $this->buffer[$offset + 1];
    }

    /**
     * Return one PHP integer data of 16 bit unsigned short
     *
     * @@aram int $offset
     * @param int $endian  self::BIG_ENDIAN or self::LITTLE_ENDIAN
     * @return int|null
     */
    public function getShort($offset, $endian = null)
    {
        if (!isset($this->buffer[$offset + 1])
            || !isset($this->buffer[$offset + 2])
        ) {
            return null;
        }

        if (is_null($endian)) {
            $isBigEndian = $this->isBigEndian;
        } else {
            $isBigEndian = $this->checkBigEndian($endian);
        }
        if ($isBigEndian) {
            $first = $this->buffer[$offset + 1];
            $second = $this->buffer[$offset + 2];
        } else {
            $first = $this->buffer[$offset + 2];
            $second = $this->buffer[$offset + 1];
        }
        return ($first << 8) + $second;
    }

    /**
     * Return one PHP integer data of 32 bit unsigned integer
     *
     * @param int $offset
     * @param int $endian  self::BIG_ENDIAN or self::LITTLE_ENDIAN
     * @return int|null
     */
    public function getInt($offset, $endian = null)
    {
        if (!isset($this->buffer[$offset + 1])
            || !isset($this->buffer[$offset + 2])
            || !isset($this->buffer[$offset + 3])
            || !isset($this->buffer[$offset + 4])
        ) {
            return null;
        }

        if (is_null($endian)) {
            $isBigEndian = $this->isBigEndian;
        } else {
            $isBigEndian = $this->checkBigEndian($endian);
        }
        if ($isBigEndian) {
            $first = $this->buffer[$offset + 1];
            $second = $this->buffer[$offset + 2];
            $third = $this->buffer[$offset + 3];
            $fourth = $this->buffer[$offset + 4];
        } else {
            $first = $this->buffer[$offset + 4];
            $second = $this->buffer[$offset + 3];
            $third = $this->buffer[$offset + 2];
            $fourth = $this->buffer[$offset + 1];
        }
        return ($first << 24) + ($second << 16) + ($third << 8) + $fourth;
    }

    /**
     * Return PHP string data
     *
     * @param int $offset
     * @param int $length
     * @return string
     */
    public function getString($offset, $length)
    {
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= chr($this->getByte($offset + $i));
        }
        return $str;
    }

    /**
     * (add by pumpCurry 2019-12-12.)
     * Return Length.
     * @return int $length
     */
    public function length()
    {
        if(is_array($this->buffer)){
            return count($this->buffer);
        } else {
            return -1;
        }
    }

}