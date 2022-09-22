<?php


namespace Strud\Database\Expression
{
	
	use Strud\Database\Expression;
	
	class On implements Expression
	{
		private $firstColumnName;
		private $operator;
		private $secondColumnName;
		private $firstAlias;
		private $secondAlias;

		public function __construct($firstColumnName, $operator, $secondColumnName = null, $firstAlias, $secondAlias = null)
		{
			$this->firstColumnName = $firstColumnName;
			$this->operator = $operator;
			$this->secondColumnName = $secondColumnName;
            $this->firstAlias = is_null($firstAlias)?"":$firstAlias.".";
            $this->secondAlias = is_null($secondAlias)?"":$secondAlias.".";
		}
		
		public function generate()
		{
			return "ON " . $this->firstAlias.$this->firstColumnName . " " . $this->operator . " " . $this->secondAlias.$this->secondColumnName;
		}
	}
}


