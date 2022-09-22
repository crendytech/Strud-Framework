<?php


namespace Strud\Database\Expression
{
	
	use Strud\Database\Expression;
	use Strud\Utils\StringUtil;
	
	class Like implements Expression
	{
		const TYPE_AND = "AND";
		const TYPE_OR = "OR";
		
		private $column;
		private $pattern;
		private $type;
		
		public function __construct($column, $pattern, $type = self::TYPE_AND)
		{
			$this->column = $column;
			$this->pattern = $pattern;
			$this->type = $type;
		}
		
		public function generate()
		{
			return $this->column . " LIKE " . StringUtil::quote($this->pattern);
		}
		
		/**
		 * @return string
		 */
		public function getType()
		{
			return $this->type;
		}
	}
}


