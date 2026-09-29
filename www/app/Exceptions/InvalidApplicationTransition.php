<?php

namespace App\Exceptions;

use App\Enums\ApplicationStatus;
use DomainException;

class InvalidApplicationTransition extends DomainException
{
    public static function between(ApplicationStatus $from, ApplicationStatus $to): self
    {
        return new self("An application cannot move from '{$from->label()}' to '{$to->label()}'.");
    }
}
