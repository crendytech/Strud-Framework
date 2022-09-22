<?php


namespace Strud\Database\Model
{
	
	use Strud\Utils\StringUtil;
	
	class Table
	{
		/**
		 * @var string
		 */
		private $name;
		/**
		 * @var string
		 */
		private $alias;
		/**
		 * @var \ArrayObject
		 */
		protected $columns;
		
		public function __construct($name, $alias = "")
		{
			$this->name = $name;
			$this->alias = $alias;
			$this->columns = new \ArrayObject();
		}
		
		/**
		 * @param Column $column
		 * @return $this
		 */
		public function addColumn(Column $column)
		{
			$column->setTable($this);
			$this->columns->append($column);
			
			return $this;
		}
		
		/**
		 * @param $name
		 * @return Column
		 * @throws \Error
		 */
		public function getColumn($name)
		{
			foreach($this->columns as $column)
			{
				if($column->getName() == $name)
				{
					return $column;
				}
			}
			
			throw new \Error("Column with name { $name } not found in table { $this->name }");
		}
		
		/**
		 * @param $name
		 * @return $this
		 * @throws \Error
		 */
		public function removeColumn($name)
		{
			foreach($this->columns as $index => $column)
			{
				if($column->getName() == $name)
				{
					$this->columns->offsetUnset($index);
					
					return $this;
				}
			}
			
			throw new \Error("Column with name { $name } not found in table { $this->name }");
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
			return ($includeAlias && $this->haveAlias()) ? $this->name . " AS " . $this->alias : $this->name;
		}
		
		public function haveAlias()
		{
			return !StringUtil::isEmpty($this->alias);
		}
		
		/**
		 * @return array
		 */
		public function getColumns()
		{
			return $this->columns->getArrayCopy();
		}
		
		/**
		 * @return string
		 */
		public function getAlias()
		{
			return $this->alias;
		}
		
	}
}


