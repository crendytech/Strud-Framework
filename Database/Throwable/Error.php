<?php

namespace Strud\Database\Throwable;

/** Base class for all conditions that the application might not recover from and thus should not catch */
class Error extends \Exception {}
