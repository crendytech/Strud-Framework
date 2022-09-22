<?php


namespace Strud\Database\Engine
{
	
	use Strud\Database\Configuration;
	use PDOException;
	use Strud\Database\Engine;
    use Strud\Database\ErrorHandler;
    use Strud\Database\Result;
	
	class SQL implements Engine
	{
		/**
		 * @var \PDO
		 */
		private $driver;
		
		public function __construct(Configuration $configuration)
		{
			$dsn = "mysql:dbname={$configuration->getDatabase()};host={$configuration->getHost()};charset=utf8mb4;collation=utf8mb4_unicode_ci";
			
			$this->driver = new \PDO($dsn, $configuration->getUsername(), $configuration->getPassword());
			$this->driver->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		}
		
		/**
		 * @param $statement
		 * @return Result
		 */
		public function execute($statement)
		{
		    try {
                return new Result($this->driver->query($statement));
            }catch (PDOException $e)
            {
                ErrorHandler::rethrow($e);
            }
		}
		
		/**
		 * @param $value
		 * @return string
		 */
		public function quote($value)
		{
			return $this->driver->quote($value);
		}
		
		public function beginTransaction()
		{
			$this->driver->beginTransaction();
		}
		
		public function endTransaction()
		{
			$this->driver->commit();
		}
		
		public function rollbackTransaction()
		{
			$this->driver->rollBack();
		}
		
		public function getLastInsertId()
		{
			return $this->driver->lastInsertId();
		}
	}
}


