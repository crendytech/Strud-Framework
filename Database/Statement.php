<?php


namespace Strud\Database
{
	
	use Strud\Database\Model\Table;
	
	abstract class Statement
	{
		/**
		 * @var Table
		 */
		protected $table;

		/**
		 * @param Connection
		 */
		protected $connection;
		
		public function __construct(Table $table, Connection $connection = null)
		{
			$this->table = $table;
			$this->connection = $connection;
		}
		
		abstract function generate();
		
		/**
		 * @param Connection $connection
		 * @return Result
		 */
		public function execute(Connection $connection = null)
		{
			if($this->connection != null)
			{
				return $this->connection->execute($this->generate());
			}

			return null;
		}
		
	}
}


