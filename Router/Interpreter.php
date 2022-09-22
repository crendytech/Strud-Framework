<?php


namespace Strud\Router
{
	
	use Strud\Utils\RegexUtil;
	
	abstract class Interpreter
	{
		protected $pattern;
		
		public function understands($url)
		{
			return RegexUtil::match($this->pattern, $url);
		}
		
		abstract public function interpret($url);
	}
}


