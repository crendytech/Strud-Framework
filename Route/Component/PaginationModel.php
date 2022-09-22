<?php


namespace Strud\Route\Component
{

	abstract class PaginationModel extends Model
	{
		protected $currentPageIndex;
		protected $amountPerPage;

		public function reload()
		{

		}

		abstract public function getTotalNumberOfItems();
		
		public function getNextPageIndex()
		{
			return (($this->currentPageIndex + 1) <= $this->getNumberOfPages()) ? $this->currentPageIndex + 1 : -1;
		}
		
		/**
		 * @return mixed
		 */
		public function getData()
		{
			return $this->fetchRows($this->getIndexToStartFrom(), $this->amountPerPage);
		}
		
		abstract public function fetchRows($indexToStartFrom, $amount);
		
		/**
		 * @return mixed
		 */
		private function getIndexToStartFrom()
		{
			return ($this->currentPageIndex - 1) * $this->amountPerPage;
		}
		
		/**
		 * @param int $value
		 */
		public function setAmountPerPage($value)
		{
			$this->amountPerPage = $value;
		}

		/**
		 * @return mixed
		 */
		public function getNumberOfPages()
		{
			$totalItems = $this->getTotalNumberOfItems();

			return round($totalItems / $this->amountPerPage) + ($totalItems % $this->amountPerPage);
		}
	}
}


