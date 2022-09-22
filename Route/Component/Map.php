<?php


namespace Strud\Route\Component
{
	
	use Strud\Database\Model\Table;

	interface Map
	{
		/**
		 * @param string $alias
		 * @return Table
		 */
		public static function getTable($alias = '');
	}
}


