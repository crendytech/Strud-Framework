<?php


namespace Strud\Utils
{
	class ArrayUtil
	{
		public static function isEmpty(array $array)
		{
			return empty($array);
		}
		
		public static function join(array $firstArray, array $secondArray)
		{
			return array_merge($firstArray, $secondArray);
		}
		
		public static function quote(array $values)
		{
			return array_map(function($value) {
				return StringUtil::quote($value);
			}, $values);
		}

        public static function keyExist($key, array $values)
        {
            return array_key_exists($key, $values);
        }
		
		public static function _toArray($object)
		{
			if(!is_object($object))
			{
				$object = (object) $object;
				self::_toArray($object);
			}
			
			if(is_object($object))
			{
				$arr = [];
				foreach($object as $key => $value)
				{
					if($key != "parameters")
					{
					     $arr[$key] = $value;
					}
				}
				return $arr;
			}
		}

    public static function _toSize($bytes) {
        $sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        if($bytes == 0) {return '0 Bytes';}
        $i = floor($bytes/1024);
        return round($i) + ' ' + $sizes[i];
    }
	}
}


