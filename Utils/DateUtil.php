<?php


namespace Strud\Utils
{
	class DateUtil
	{
		public static function getCurrentDate($format = 'Y-m-d')
		{
			$current_date = new \DateTime();
			
			return $current_date->format($format);
		}
		
		public static function getCurrentDateWithTime()
		{
			return self::getCurrentDate('Y-m-d H:i:s');
		}
		
		public static function getDateInTimeStamp()
		{
			return time();
		}

        public static function strToTime($string)
        {
            return strtotime($string);
		}

		public static function dateToTimeStamp($date)
        {
           return (empty($date))?self::getCurrentDateWithTime():strtotime($date);
        }
	}
}


