<?php


namespace Strud\Database\Statement
{

	use Strud\Database\Connection;
	use Strud\Database\Expression;
	use Strud\Database\Model\Table;
	use Strud\Database\Statement;
	
	class Delete extends Statement
	{
		private $whereClauseBuilder;
		private $limitClauseBuilder;


		public function __construct(Table $table, Connection $connection = null)
		{
			parent::__construct($table, $connection);
			$this->whereClauseBuilder = new Builder\Where();
			$this->limitClauseBuilder = new Builder\Limit();
		}
		
		public function addWhereExpression(Expression $expression)
		{
			$this->whereClauseBuilder->addExpression($expression);
		}
		
		public function setLimit($limit)
		{
			$this->limitClauseBuilder->setLimit($limit);
		}
		
		public function setOffset($offset)
		{
			$this->limitClauseBuilder->setOffset($offset);
		}
		
		public function generate()
		{
			$statement = "";
			
			$statement .= "DELETE FROM ";
			$statement .= $this->table->getQualifiedNameWithAlias();
			$statement .= $this->whereClauseBuilder->build();
			$statement .= $this->limitClauseBuilder->build();
			
			return $statement;
		}
	}
}


