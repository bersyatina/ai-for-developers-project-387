<?php

namespace App\Exceptions;

use InvalidArgumentException;

/**
 * Недопустимый слот: вне 14-дневного окна записи, в прошлом или не кратен 30 минутам.
 * Маппится в HTTP 422.
 */
class InvalidSlotException extends InvalidArgumentException {}
