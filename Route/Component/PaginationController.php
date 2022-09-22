<?php


namespace Strud\Route\Component
{

	use Strud\Request\Method;

	abstract class PaginationController extends Controller
	{
		public function run()
		{
			$handler = $this->request->using(Method::POST);
			
			$this->model->currentPageIndex = $handler->get("page", 1);
			$this->model->amountPerPage = $handler->get("length", 10);
			
			$this->response
				->put("output", $this->transformDataToMarkup($this->model->data))
				->put("nextPageIndex", $this->model->nextPageIndex);
		}
		
		abstract protected function transformDataToMarkup($data);
	}
}


