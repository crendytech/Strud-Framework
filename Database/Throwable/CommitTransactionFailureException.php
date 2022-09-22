<?php

namespace Strud\Database\Throwable;

/** Exception that is thrown when a transaction cannot be committed successfully for some reason */
class CommitTransactionFailureException extends TransactionFailureException {}
