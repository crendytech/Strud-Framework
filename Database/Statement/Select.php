<?php


namespace Strud\Database\Statement
{

	use Strud\Database\Connection;
	use Strud\Database\Expression;
	use Strud\Database\Model\Table;
	use Strud\Database\Statement;
	use Strud\Database\Statement\Builder;
	use Strud\Utils\StringUtil;
	
	class Select extends Statement
	{
		private $functionBuilder;
		private $joinClauseBuilder;
		private $whereClauseBuilder;
		private $groupByClauseBuilder;
		private $orderByClauseBuilder;
		private $limitClauseBuilder;

		public function __construct(Table $table, Connection $connection = null)
		{
			parent::__construct($table, $connection);
			$this->functionBuilder = new Builder\ColumnFunction();
			$this->joinClauseBuilder = new Builder\Join($this->table);
			$this->whereClauseBuilder = new Builder\Where();
			$this->groupByClauseBuilder = new Builder\GroupBy();
			$this->orderByClauseBuilder = new Builder\OrderBy();
			$this->limitClauseBuilder = new Builder\Limit();
		}
		
		
		/**
		 * @param Expression\Join $expression
		 * @return $this
		 */
		public function addJoinExpression(Expression\Join $expression)
		{
			$this->joinClauseBuilder->addExpression($expression);
			
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
		
		/**
		 * @param Expression $expression
		 * @return $this
		 */
		public function addGroupByExpression(Expression $expression)
		{
			$this->groupByClauseBuilder->addExpression($expression);
			
			return $this;
		}
		
		/**
		 * @param Expression\Comparision $expression
		 * @return $this
		 */
		public function setHavingExpression(Expression\Comparision $expression)
		{
			$this->groupByClauseBuilder->setHavingExpression($expression);
			
			return $this;
		}
		
		/**
		 * @param Expression $expression
		 * @return $this
		 */
		public function addOrderByExpression(Expression $expression)
		{
			$this->orderByClauseBuilder->addExpression($expression);
			
			return $this;
		}
		
		/**
		 * @param $limit
		 * @return $this
		 */
		public function setLimit($limit)
		{
			$this->limitClauseBuilder->setLimit($limit);
			
			return $this;
		}
		
		/**
		 * @param $offset
		 * @return $this
		 */
		public function setOffset($offset)
		{
			$this->limitClauseBuilder->setOffset($offset);
			
			return $this;
		}

        public function addSumFunctionOn($column)
        {
            $this->functionBuilder->setColumn($column);
            $this->functionBuilder->setFunction(Builder\ColumnFunction::SUM);

            return $this;
        }

        public function addCountFunctionOn($column)
        {
            $this->functionBuilder->setColumn($column);
            $this->functionBuilder->setFunction(Builder\ColumnFunction::COUNT);

            return $this;
        }

        public function addAvgFunctionOn($column)
        {
            $this->functionBuilder->setColumn($column);
            $this->functionBuilder->setFunction(Builder\ColumnFunction::AVG);

            return $this;
        }
		
		public function generate()
		{
			$statement = "";
			$statement .= "SELECT ";
            $statement .= empty($this->functionBuilder->getColumn())? $this->getColumnsWithQualifiedName() : $this->functionBuilder->build();
			$statement .= " FROM ";
			$statement .= $this->table->getQualifiedNameWithAlias();
			$statement .= $this->joinClauseBuilder->build();
			$statement .= $this->whereClauseBuilder->build();
			$statement .= $this->groupByClauseBuilder->build();
			$statement .= $this->orderByClauseBuilder->build();
			$statement .= $this->limitClauseBuilder->build();
			
			return $statement;
		}
		
		private function getColumnsWithQualifiedName()
		{
			$columns = array_merge($this->table->getColumns(), $this->joinClauseBuilder->getColumns());
			
			$columnsWithQualifiedName = [];
			
			foreach($columns as $column)
			{
				$columnsWithQualifiedName[] = $column->getQualifiedNameWithAlias();
			}

			return StringUtil::join($columnsWithQualifiedName);
		}
		
	}
}


