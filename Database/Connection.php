<?php


namespace Strud\Database
{
	
	use Strud\Database\Model\Table;
	use Strud\Database\Statement\Create;
	use Strud\Database\Statement\Delete;
	use Strud\Database\Statement\Drop;
	use Strud\Database\Statement\Insert;
	use Strud\Database\Statement\Select;
	use Strud\Database\Statement\Update;
	
	class Connection
	{
		/**
		 * @var Engine
		 */
		private $engine;
		
		public function __construct(Engine $engine)
		{
		    $this->engine = $engine;
		}

		/**
		 * @param Table $table
		 * @return Select
		 */
		public function createSelectStatement(Table $table)
		{
			return new Select($table, $this);
		}

		/**
		 * @param Table $table
		 * @return Insert
		 */
		public function createInsertStatement(Table $table)
		{
			return new Insert($table, $this);
		}

		/**
		 * @param Table $table
		 * @return Update
		 */
		public function createUpdateStatement(Table $table)
		{
			return new Update($table, $this);
		}

		/**
		 * @param Table $table
		 * @return Delete
		 */
		public function createDeleteStatement(Table $table)
		{
			return new Delete($table, $this);
		}

		/**
		 * @param Table $table
		 * @return Drop
		 */
		public function createDropStatement(Table $table)
		{
			return new Drop($table, $this);
		}

		/**
		 * @param Table $table
		 * @return Create
		 */
		public function createCreateStatement(Table $table)
		{
			return new Create($table, $this);
		}
		
		/**
		 * @param $statement
		 * @return Result
		 */
		public function execute($statement)
		{
			return $this->engine->execute($statement);
		}
		
		public function quote($value)
		{
			return $this->engine->quote($value);
		}
		
		public function beginTransaction()
		{
			$this->engine->beginTransaction();
		}
		
		public function endTransaction()
		{
			$this->engine->endTransaction();
		}
		
		public function rollbackTransaction()
		{
			$this->engine->rollbackTransaction();
		}
		
		
		public function getLastInsertId()
		{
			return $this->engine->getLastInsertId();
		}
	}
}


