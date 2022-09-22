<?php
/**
 * Created by PhpStorm.
 * User: Stephan
 * Date: 10/19/16
 * Time: 01:04 PM
 */

namespace Strud\Request
{
	
	abstract class Method
	{
		const GET = "GET";
		const POST = "POST";
		const PUT = "PUT";
		const DELETE = "DELETE";
		const FILE = "FILE";
		const ANY = "ANY";
		
		abstract public function has($property);
		abstract public function get($property, $defaultValue);
	}
}


