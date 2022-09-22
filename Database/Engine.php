<?php


namespace Strud\Database
{
	interface Engine
	{
		public function execute($statement);
		public function quote($value);
		public function beginTransaction();
		public function endTransaction();
		public function rollbackTransaction();
		public function getLastInsertId();
	}
}


