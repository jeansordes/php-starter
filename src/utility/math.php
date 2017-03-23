<?php

namespace MyApp\Utility;

class Math {
    private static $base62 = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    
    public static function getARandomString ($length = 18, $keyspace = ''){
        $str = '';
        $keyspace = empty($keyspace) ? self::$base62 : $keyspace;
        $max = mb_strlen($keyspace, '8bit') - 1;
        for ($i = 0; $i < $length; ++$i) {
            $str .= $keyspace[random_int(0, $max)];
        }
        return $str;
    }
}