<?php

/*
 * PHP-DB (https://github.com/delight-im/PHP-DB)
 * Copyright (c) delight.im (https://www.delight.im/)
 * Licensed under the MIT License (https://opensource.org/licenses/MIT)
 */

namespace Strud\Database;

use PDOException;
use Strud\Database\Throwable\DatabaseNotFoundError;
use Strud\Database\Throwable\Error;
use Strud\Database\Throwable\Exception;
use Strud\Database\Throwable\IntegrityConstraintViolationException;
use Strud\Database\Throwable\NoDatabaseSelectedError;
use Strud\Database\Throwable\SyntaxError;
use Strud\Database\Throwable\TableNotFoundError;
use Strud\Database\Throwable\UnknownColumnError;
use Strud\Database\Throwable\WrongCredentialsError;


final class ErrorHandler {

	/**
	 * Handles the specified exception thrown by PDO and tries to throw a more specific exception or error instead
	 *
	 * @param PDOException $e the exception thrown by PDO
	 * @throws Exception
	 * @throws Error
	 */
	public static function rethrow(PDOException $e) {
		// the 2-character class of the error (if any) has the highest priority
		$errorClass = null;
		// the 3-character subclass of the error (if any) has a medium priority
		$errorSubClass = null;
		// the full error code itself has the lowest priority
		$error = null;

		// if an error code is available
		if (!empty($e->getCode())) {
			// remember the error code
			$error = $e->getCode();

			// if the error code is an "SQLSTATE" error
			if (strlen($e->getCode()) === 5) {
				// remember the class as well
				$errorClass = substr($e->getCode(), 0, 2);
				// and remember the subclass
				$errorSubClass = substr($e->getCode(), 2);
			}
		}

		if ($errorClass === '3D') {
			throw new NoDatabaseSelectedError($e->getMessage());
		}
		elseif ($errorClass === '23') {
			throw new IntegrityConstraintViolationException($e->getMessage());
		}
		elseif ($errorClass === '42') {
			if ($errorSubClass === 'S02') {
				throw new TableNotFoundError($e->getMessage());
			}
			elseif ($errorSubClass === 'S22') {
				throw new UnknownColumnError($e->getMessage());
			}
			else {
				throw new SyntaxError($e->getMessage());
			}
		}
		else {
			if ($error === 1044) {
				throw new WrongCredentialsError($e->getMessage());
			}
			elseif ($error === 1049) {
				throw new DatabaseNotFoundError($e->getMessage());
			}
			else {
				throw new Error($e->getMessage());
			}
		}
	}

}
