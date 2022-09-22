<?php


namespace Strud\Request
{
	
	use Strud\Request\Method\Any;
	use Strud\Request\Method\File;
	use Strud\Request\Method\Get;
	use Strud\Request\Method\Post;
	
	class Handler
	{
		private $method;
		
		public function __construct($method)
		{
			$this->method = $this->getMethodInstance($method);
		}
		
		public function get($property, $defaultValue = "")
		{
			return $this->method->get($property, $defaultValue);
		}
		
		public function has($property)
		{
			return $this->method->has($property);
		}
		
		/**
		 * @return Method
		 */
		private function getMethodInstance($method)
		{
			switch($method)
			{
				case Method::POST:
					return Post::getInstance();
				case Method::GET:
					return Get::getInstance();
				case Method::FILE:
					return File::getInstance();
				case Method::ANY:
					return Any::getInstance();
				default:
					return new \stdClass();
			}
		}
	}
}


