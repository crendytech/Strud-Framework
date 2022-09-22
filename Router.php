<?php


namespace Strud
{
	
	use ArrayObject;
	use Strud\Request\Method;
	use Strud\Router\Interpreter;
	
	class Router
	{
		/**
		 * @var Router
		 */
		private static $instance;
		
		/**
		 * @var ArrayObject
		 */
		private $interpreters;
		
		/**
		 * @return Router
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
			$this->interpreters = new ArrayObject();
		}
		
		/**
		 * @param Interpreter $interpreter
		 * @return $this
		 */
		public function register(Interpreter $interpreter)
		{
			$this->interpreters->append($interpreter);
			
			return $this;
		}
		
		/**
		 * @param $url
		 * @return Route
		 */
		public function find($url)
		{
			foreach($this->interpreters as $interpreter)
			{
				if($interpreter->understands($url))
				{
					return $interpreter->interpret($url);
				}
			}
		}
	}
}


