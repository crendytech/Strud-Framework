<?php


namespace Strud\Database\Model
{
	
	use Strud\Utils\StringUtil;
	
	class Column
	{
		/**
		 * @var Table
		 */
		private $table;
		/**
		 * @var string
		 */
		private $name;
		/**
		 * @var string
		 */
		private $alias;
		
		public function __construct($name, $alias = "")
		{
			$this->name = $name;
			$this->alias = $alias;
		}
		
		public function getQualifiedNameWithAlias()
		{
			return $this->getQualifiedName(true);
		}
		
		public function getQualifiedNameWithoutAlias()
		{
			return $this->getQualifiedName();
		}
		
		public function getQualifiedName($includeAlias = false)
		{
			$result = "";

			if($this->table)
			{
				$result .= $this->table->haveAlias() ? $this->table->getAlias() : $this->table->getQualifiedNameWithoutAlias();
				$result .= ".";
			}
	
			$result .= $this->name;
			
			if($includeAlias && $this->haveAlias())
			{
				$result .= " AS " . $this->alias;
			}
			
			return $result;
		}
		
		public function haveAlias()
		{
			return !StringUtil::isEmpty($this->alias);
		}
		
		/**
		 * @return Table
		 */
		public function getTable()
		{
			return $this->table;
		}
		
		/**
		 * @param Table $table
		 */
		public function setTable(Table $table)
		{
			$this->table = $table;
		}
		
		/**
		 * @return string
		 */
		public function getName()
		{
			return $this->name;
		}
		
		/**
		 * @return string
		 */
		public function getAlias()
		{
			return $this->alias;
		}
		
		/**
		 * @param string $alias
		 */
		public function setAlias($alias)
		{
			$this->alias = $alias;
		}
		
	}
}


