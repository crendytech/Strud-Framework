<?php


namespace Strud
{
	class Session
	{
		/**
		 * @var Session
		 */
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
			session_start();
		}
		
		public function close()
		{
			session_abort();
		}
		
		public function clear()
		{
			session_unset();
		}
		
		public function has($name)
		{
			return !empty($_SESSION[$name]);
		}
		
		public function get($name, $defaultValue = null)
		{
			return $this->has($name) ? $_SESSION[$name] : $defaultValue;
		}
		
		public function put($name, $value)
		{
			$_SESSION[$name] = $value;
		}
		
		public function remove($name)
		{
			unset($_SESSION[$name]);
		}
	}
}


