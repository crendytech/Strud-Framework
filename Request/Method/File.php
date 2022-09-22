<?php


namespace Strud\Request\Method
{
	
	use Strud\Request\Method;
	
	class File extends Method
	{
		private static $instance;
		
		public static function getInstance()
		{
		    if(!static::$instance)
		    {
		        static::$instance = new static();
		    }
		    
		    return static::$instance;
		}
		
		private function __construct()
		{
		    
		}
		
		public function has($property)
		{
			return !empty($_FILES[$property]);
		}
		
		public function get($property, $defaultValue = [])
		{
			return $this->has($property) ? $_FILES[$property] : $defaultValue;
		}
	}
}


