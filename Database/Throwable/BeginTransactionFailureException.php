<?php

namespace Strud\Database\Throwable;

/** Exception that is thrown when a transaction cannot be started successfully for some reason */
class BeginTransactionFailureException extends TransactionFailureException {}
