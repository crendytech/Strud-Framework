<?php


namespace Strud\Database\Expression
{
	
	use Strud\Database\Expression;
	
	class Basic implements Expression
	{
		private $values;
		
		public function __construct(...$values)
		{
			$this->values = $values;
		}
		
		public function generate()
		{
			return join(" ", $this->values);
		}
	}
}


