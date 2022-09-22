<?php


namespace Strud\Database\Statement
{
	
	use Strud\Collection\ArrayMap;
	use Strud\Database\Connection;
	use Strud\Database\Expression;
	use Strud\Database\Model\Table;
	use Strud\Database\Statement;
	use Strud\Utils\StringUtil;
	
	class Update extends Statement
	{
		/**
		 * @var ArrayMap
		 */
		private $columnValueMap;
		/**
		 * @var Statement\Builder\Where
		 */
		private $whereClauseBuilder;

		public function __construct(Table $table, Connection $connection = null)
		{
			parent::__construct($table, $connection);
			$this->columnValueMap = new ArrayMap();
			$this->whereClauseBuilder = new Builder\Where();
		}
		
		/**
		 * @param $columnName
		 * @param $value
		 * @return $this
		 */
		public function addValue($columnName, $value)
		{
			$this->columnValueMap->put($columnName, $value);
			
			return $this;
		}
		
		/**
		 * @param Expression $expression
		 * @return $this
		 */
		public function addWhereExpression(Expression $expression)
		{
			$this->whereClauseBuilder->addExpression($expression);
			
			return $this;
		}
		
		public function generate()
		{
			$statement = "";
			
			$statement .= "UPDATE ";
			$statement .= $this->table->getQualifiedNameWithAlias();
			$statement .= " SET ";
			$statement .= $this->buildColumnNameEqualsValueString();
			$statement .= $this->whereClauseBuilder->build();
			
			return $statement;
		}
		
		private function buildColumnNameEqualsValueString()
		{
			$columnNameEqualsValueString = [];
			
			foreach($this->columnValueMap->getSource() as $columnName => $value)
			{
				$columnNameEqualsValueString[] = "{$columnName}=" . StringUtil::quote($value);
			}
			
			return StringUtil::join($columnNameEqualsValueString);
		}
	}
}


