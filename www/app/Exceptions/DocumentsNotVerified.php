<?php

namespace App\Exceptions;

use App\Enums\DocumentType;
use DomainException;

class DocumentsNotVerified extends DomainException
{
    /** @param  list<DocumentType>  $unverified */
    public static function for(array $unverified): self
    {
        $names = implode('; ', array_map(fn (DocumentType $type) => $type->label(), $unverified));

        return new self("Required documents have not been accepted yet: {$names}.");
    }
}
