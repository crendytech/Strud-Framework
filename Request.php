<?php


namespace Strud
{
	
	use Strud\Request\Handler;
	use Strud\Request\Method;
	
	class Request
	{
		private static $instance;
		
		/**
		 * @return Request
		 */
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
		
		/**
		 * @param $method
		 * @return Handler
		 */
		public function using($method)
		{
			return new Handler($method);
		}
		
		public function isAjax()
		{
			return $this->using(Method::ANY)->has("_ajax");
		}

        public function targetView()
        {
            return ($this->using(Method::ANY)->has("_target") && ($this->using(Method::ANY)->get("_target") == "View"));
        }
		
		public function getCurrentLocation()
		{
			return $_SERVER["REQUEST_URI"];
		}
		
		public function getPreviousLocation()
		{
			return $_SERVER["HTTP_REFERER"];
		}
		
		public function getType()
		{
			return $_SERVER["REQUEST_METHOD"];
		}
		
	}
}