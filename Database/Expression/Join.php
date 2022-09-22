<?php


namespace Strud\Database\Expression
{
	
	use Strud\Database\Expression;
	use Strud\Database\Model\Table;
	
	class Join implements Expression
	{
		/**
		 * @var Table
		 */
		protected $table;
		/**
		 * @var Expression
		 */
		protected $conditionExpression;
		/**
		 * @var string
		 */
		protected $type;
		
		public function __construct(Table $table = null, Expression $conditionExpression = null, $type = JoinType::LEFT)
		{
			$this->table = $table;
			$this->conditionExpression = $conditionExpression;
			$this->type = $type;
		}
		
		public function setTable(Table $table)
		{
			$this->table = $table;
			
			return $this;
		}
		
		public function setType($type)
		{
			$this->type = $type;
			
			return $this;
		}
		
		public function setConditionExpression($conditionExpression)
		{
			$this->conditionExpression = $conditionExpression;
			
			return $this;
		}
		
		public function generate()
		{
			$statement = "";
			
			$statement .= $this->type;
			$statement .= " JOIN ";
			$statement .= $this->table->getQualifiedNameWithAlias();
			
			if($this->conditionExpression)
			{
				$statement .= " ";
				$statement .= $this->conditionExpression->generate();
			}
			
			return $statement;
		}
		
		/**
		 * @return Table
		 */
		public function getTable()
		{
			return $this->table;
		}
	}
}


