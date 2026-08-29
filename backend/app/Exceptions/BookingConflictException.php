<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Слот занят: на выбранное время уже существует бронь (в т.ч. другого типа).
 * Маппится в HTTP 409.
 */
class BookingConflictException extends RuntimeException {}
