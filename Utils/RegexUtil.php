<?php


namespace Strud\Utils
{
	class RegexUtil
	{
		public static function match($pattern, $string)
		{
			$matches = [];
			
			preg_match($pattern, $string, $matches);
			
			return $matches;
		}
		
		public static function replace($pattern, $string, $replacement = "")
		{
			return preg_replace($pattern, $replacement, $string);
		}
	}
}


