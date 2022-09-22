<?php


namespace Strud\Database\Statement\Builder
{
	
	use Strud\Database\Expression;
	use Strud\Database\Model\Table;
	use Strud\Database\Statement\Builder;
	use Strud\Utils\StringUtil;
	
	class Join implements Builder
	{
		/**
		 * @var \ArrayObject
		 */
		private $expressions;
		/**
		 * @var Table
		 */
		private $table;
		
		public function __construct(Table $table)
		{
		    $this->expressions = new \ArrayObject();
			$this->table = $table;
		}
		
		/**
		 * @param Expression\Join $expression
		 * @return Join
		 */
		public function addExpression(Expression\Join $expression)
		{
			$this->expressions->append($expression);
			
			return $this;
		}
		
		public function build()
		{
			$generatedStatements = [];
			
			foreach($this->expressions as $expression)
			{
				$generatedStatements[] = $expression->generate();
			}
			
			return " " . StringUtil::join($generatedStatements, " ");
		}
		
		public function getColumns()
		{
			$columns = [];
			
			foreach($this->expressions as $expression)
			{
				$columns = array_merge($columns, $expression->getTable()->getColumns());
			}
			
			return $columns;
		}
	}
}


