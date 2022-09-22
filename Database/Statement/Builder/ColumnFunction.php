<?php
/**
 * Copyright (c) 2018. Olusegun Olaniyi.
 */

namespace Strud\Database\Statement\Builder
{
	
	use Strud\Database\Statement\Builder;
	
	class ColumnFunction implements Builder
	{

        const SUM = "SUM";
        const AVG = "AVG";
        const COUNT = "COUNT";

		protected $column;
		protected $alias;
		protected  $function;

        /**
         * @param mixed $column
         */
        public function setColumn($column)
        {
            $this->column = $column;
        }

        /**
         * @param mixed $function
         */
        public function setFunction($function)
        {
            $this->function = $function;
        }

        /**
         * @return mixed
         */
        public function getColumn()
        {
            return $this->column;
        }

        /**
         * @param mixed $alias
         */
        public function setAlias($alias)
        {
            $this->alias = $alias;
        }
		
		/**
		 * @return string
		 */
		function build()
		{
			$result = "";
			
			if(!empty($this->column))
			{
				$result .= $this->function."(".$this->column.") ";
				
				if($this->alias !== "")
				{
					$result .= "AS ".strtolower($this->function).ucfirst($this->column);
				}
			}
			
			return $result;
		}
	}
}


