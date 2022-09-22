<?php


namespace Strud\Utils
{
	class StringUtil
    {
        public static function isEmpty($string)
        {
            return empty($string);
        }

        public static function join($array, $separator = ", ")
        {
            return join($separator, $array);
        }

        public static function quote($value)
        {
            return is_string($value) ? "'" . htmlentities($value, ENT_QUOTES) . "'" : (($value) ? $value : "''");
        }

        public static function startsWith($word, $string)
        {
            return count(RegexUtil::match("/^{$word}/", $string)) > 0;
        }

        public static function endsWith($word, $string)
        {
            return count(RegexUtil::match("/{$word}$/", $string)) > 0;
        }

        public static function stripFromStart($word, $string)
        {
            if (self::startsWith($word, $string)) {
                return RegexUtil::replace("/^{$word}/", $string, "");
            }

            return $string;
        }

        public static function stripFromEnd($word, $string)
        {
            if (self::endsWith($word, $string)) {
                return RegexUtil::replace("/{$word}$/", $string, "");
            }

            return $string;
        }

        public static function slugify($string, $replace = array(), $delimiter = '-')
        {
            if (!extension_loaded('iconv')) {
                throw new Exception('iconv module not loaded');
            }
            // Save the old locale and set the new locale to UTF-8
            $oldLocale = setlocale(LC_ALL, '0');
            setlocale(LC_ALL, 'en_US.UTF-8');
            $clean = iconv('UTF-8', 'ASCII//TRANSLIT', $string);
            if (!empty($replace)) {
                $clean = str_replace((array)$replace, ' ', $clean);
            }
            $clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
            $clean = strtolower($clean);
            $clean = preg_replace("/[\/_|+ -]+/", $delimiter, $clean);
            $clean = trim($clean, $delimiter);
            // Revert back to the old locale
            setlocale(LC_ALL, $oldLocale);
            return $clean;
        }

        public static function pinify($length = 6) {
            $alphabets = range('A','Z');
            $numbers = range('0','9');
            $final_array = array_merge($alphabets,$numbers);

            $password = '';

            while($length--) {
                $key = array_rand($final_array);
                $password .= $final_array[$key];
            }

            return $password;
        }

        public static function truncate($str, $len){
            $tail = max(0, $len-10);
            $trunk = substr($str, 0, $tail);
            $trunk .= strrev(preg_replace('~^..+?[\s,:]\b|^...~', '\\1...', strrev(substr($str, $tail, $len-$tail))));
            return $trunk;
        }
    }
}