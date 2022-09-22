<?php


namespace Strud\Database\Expression
{
	
	use Strud\Database\Expression;
	use Strud\Utils\ArrayUtil;
	use Strud\Utils\StringUtil;
	
	class Using implements Expression
	{
		/**
		 * @var array
		 */
		private $columns;
		
		public function __construct($columnName, ...$columns)
		{
		    $this->columns[] = $columnName;
			
			if(!empty($columns))
			{
				array_merge($this->columns, $columns);
			}
		}
		
		public function generate()
		{
			return "USING" . "(" . StringUtil::join($this->columns) . ")";
		}
	}
}


