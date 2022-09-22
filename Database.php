<?php


namespace Strud
{
	
	use Strud\Database\Connection;
	use Strud\Database\Engine;
	
	class Database
	{
		/**
		 * @param Engine $engine
		 * @return Connection
		 */
		public function createConnection(Engine $engine)
		{
			return new Connection($engine);
		}
		
	}
	
}


