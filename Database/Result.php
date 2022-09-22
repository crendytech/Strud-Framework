<?php


namespace Strud\Database
{
	class Result
	{
		/**
		 * @var \PDOStatement
		 */
		private $statement;
		
		public function __construct(\PDOStatement $statement)
		{
			$this->statement = $statement;
		}
		
		public function fetch($class = null)
		{
			if($class)
			{
				return $this->statement->fetchObject($class);
			}
			
			return $this->statement->fetch(\PDO::FETCH_ASSOC);
		}
		
		public function fetchAll($class = null)
		{
			if($class)
			{
				return $this->statement->fetchAll(\PDO::FETCH_CLASS, $class);
			}
			
			return $this->statement->fetchAll(\PDO::FETCH_ASSOC);
		}
		
		public function getLength()
		{
			return $this->statement->rowCount();
		}
	}
}


