<?php


namespace Strud\Database\Statement\Builder
{
	
	use Strud\Database\Statement\Builder;
	
	class Limit implements Builder
	{
		protected $limit;
		protected $offset;
		
		public function setLimit($limit)
		{
			$this->limit = $limit;
		}
	
		public function setOffset($offset)
		{
			$this->offset = $offset;
		}
		
		/**
		 * @return string
		 */
		function build()
		{
			$result = "";
			
			if($this->limit > 0)
			{
				$result .= " LIMIT ";
				
				if($this->offset > 0)
				{
					$result .= $this->offset;
					$result .= ", ";
				}
				
				$result .= $this->limit;
			}
			
			return $result;
		}
	}
}


