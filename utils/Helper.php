<?php

namespace Utils;

class Helper
{
    public static function upper(string $str): string
    {
        return strtoupper($str);
    }

    public static function dump($var): void
    {
        echo '<pre>';
        var_dump($var);
        echo '</pre>';
    }
}