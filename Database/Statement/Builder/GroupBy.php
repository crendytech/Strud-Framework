<?php


namespace Strud\Database\Statement\Builder
{
	
	use Strud\Database\Expression;
	use Strud\Database\Statement\Builder;
	use Strud\Utils\ArrayUtil;
	use Strud\Utils\StringUtil;
	
	class GroupBy implements Builder
	{
		/**
		 * @var \ArrayObject
		 */
		protected $expressions;
		
		/**
		 * @var Expression
		 */
		protected $havingExpression;
		
		public function __construct()
		{
		    $this->expressions = new \ArrayObject();
		}
		
		public function addExpression(Expression $expression)
		{
			$this->expressions->append($expression);
		}
		
		public function setHavingExpression(Expression $expression)
		{
			$this->havingExpression = $expression;
		}
		
		/**
		 * @return string
		 */
		public function build()
		{
			$result = "";
			
			if(!ArrayUtil::isEmpty($this->expressions->getArrayCopy()))
			{
				$result .= " GROUP BY ";
				
				$generatedExpressions = [];
				
				foreach($this->expressions as $expression)
				{
					$generatedExpressions[] = $expression->generate();
				}

				$result .= StringUtil::join($generatedExpressions);

				if($this->havingExpression)
				{
					$result .= " HAVING ";
					$result .= $this->havingExpression->generate();
				}
			}
			
			return $result;
		}
	}
}


