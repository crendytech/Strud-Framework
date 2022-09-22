<?php


namespace Strud\Utils
{
    class FileUtil
    {
        public static function _toSize($bytes) {
            if(filter_var($bytes, FILTER_VALIDATE_FLOAT) === false)
                return $bytes;
            $sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
            if($bytes == 0) {return '0 Bytes';}
            $i = floor(log($bytes)/log(1024));
            return round($bytes / pow(1024, $i), 2).' '.$sizes[$i];
        }
    }
}