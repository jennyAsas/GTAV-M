<?php

namespace App\Services;

use RuntimeException;

/** A business-rule failure whose message is safe to show to the user. */
class RegistrationException extends RuntimeException
{
}
