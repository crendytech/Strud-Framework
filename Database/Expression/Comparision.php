<?php


namespace Strud\Database\Expression
{
	
	use Strud\Database\Expression;
	use Strud\Utils\StringUtil;
	
	class Comparision implements Expression
	{
		const TYPE_AND = "AND";
		const TYPE_OR = "OR";
		
		private $column;
		private $criteria;
		private $value;
		private $type;
		private $alias;

		public function __construct($column, $criteria, $value, $alias=null, $type = self::TYPE_AND)
		{
		    $this->alias = is_null($alias)?"":$alias.".";
			$this->column = $column;
			$this->criteria = $criteria;
			$this->value = $value;
			$this->type = $type;
		}
		
		function generate()
		{
            $value = $this->criteria == Criteria::IN ? "( ".$this->value." )" : $this->criteria == Criteria::BETWEEN ? $this->value : StringUtil::quote($this->value);
			return "{$this->alias}{$this->column} {$this->criteria} " . $value;
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


