<?php


namespace Strud\Database\Statement
{

	use Strud\Database\Connection;
	use Strud\Database\Model\Table;
	use Strud\Database\Statement;
	
	class Drop extends Statement
	{

		public function __construct(Table $table, Connection $connection = null)
		{
			parent::__construct($table, $connection);
			
		}
		
		public function generate()
		{
			$statement = "";
			
			$statement .= "DROP ";
			$statement .= $this->table->getQualifiedNameWithoutAlias();
			
			return $statement;
		}
	}
}


