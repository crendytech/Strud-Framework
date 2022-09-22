<?php


namespace Strud\Database\Statement\Builder
{
	
	use Strud\Database\Expression;
	use Strud\Database\Statement\Builder;
	use Strud\Utils\ArrayUtil;
	
	class Where implements Builder
	{
		/**
		 * @var \ArrayObject
		 */
		protected $expressions;
		
		public function __construct()
		{
			$this->expressions = new \ArrayObject();
		}
		
		public function addExpression(Expression $expression)
		{
			$this->expressions->append($expression);
		}
		
		/**
		 * @return string
		 */
		public function build()
		{
			$result = "";
			
			if(!ArrayUtil::isEmpty($this->expressions->getArrayCopy()))
			{
				$result .= " WHERE ";
				
				foreach($this->expressions as $index => $expression)
				{
					//TODO simplify this
					if($index > 0)
					{
						$result .= " ";
						
						if(get_class($expression) == Expression\Comparision::class || get_class($expression) == Expression\Like::class)
						{
							$result .= $expression->getType();
						}
						else
						{
							$result .= " AND ";
						}
						
						$result .= " ";
					}
					
					$result .= $expression->generate();
				}
			}
			
			return $result;
		}
	}
}


