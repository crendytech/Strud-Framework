<?php


namespace Strud\Database\Statement\Builder
{
	
	use Strud\Database\Expression;
	use Strud\Database\Statement\Builder;
	use Strud\Utils\ArrayUtil;
	use Strud\Utils\StringUtil;
	
	class OrderBy implements Builder
	{
		/**
		 * @var \ArrayObject
		 */
		protected $expressions;
		
		public function __construct()
		{
			$this->expressions = new \ArrayObject();
		}
		
		/**
		 * @param Expression $expression
		 * @return $this
		 */
		public function addExpression(Expression $expression)
		{
			$this->expressions->append($expression);
			
			return $this;
		}
		
		/**
		 * @return string
		 */
		public function build()
		{
			$result = "";
			
			if(!ArrayUtil::isEmpty($this->expressions->getArrayCopy()))
			{
				$result .= " ORDER BY ";
				
				$generatedExpressions = [];
				
				foreach($this->expressions as $expression)
				{
					$generatedExpressions[] = $expression->generate();
				}
				
				$result .= StringUtil::join($generatedExpressions);
			}
			
			return $result;
		}
	}
}


