<?php

namespace App\Domain\Order\Exceptions;

use RuntimeException;

/**
 * An expected, user-correctable order failure whose message is safe to expose.
 */
class OrderOperationException extends RuntimeException {}
