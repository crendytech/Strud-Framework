<?php


namespace Strud\Database\Statement
{
	
	use Strud\Collection\ArrayMap;
	use Strud\Database\Connection;
	use Strud\Database\Model\Table;
	use Strud\Database\Statement;
	use Strud\Utils\ArrayUtil;
	use Strud\Utils\StringUtil;
	
	class Insert extends Statement
	{
		/**
		 * @var ArrayMap
		 */
		private $columnValueMap;


		public function __construct(Table $table, Connection $connection = null)
		{
			parent::__construct($table, $connection);
			$this->columnValueMap = new ArrayMap();
		}
		
		public function addValue($columnName, $value)
		{
			$this->columnValueMap->put($columnName, $value);
		}
		
		public function generate()
		{
			$statement = "";
			
			$statement .= "INSERT INTO ";
			$statement .= $this->table->getQualifiedNameWithoutAlias();
			$statement .= " ";
			$statement .= $this->buildColumnString();
			$statement .= " VALUES ";
			$statement .= $this->buildValuesString();
			
			return $statement;
		}
		
		private function buildColumnString()
		{
			return "(" . StringUtil::join($this->columnValueMap->getKeys()) . ")";
		}
		
		private function buildValuesString()
		{
			return "(" . StringUtil::join(ArrayUtil::quote($this->columnValueMap->getValues())) . ")";
		}
	}
}


