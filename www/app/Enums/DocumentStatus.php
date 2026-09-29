<?php

namespace App\Enums;

enum DocumentStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Accepted => 'success',
            self::Rejected => 'danger',
        };
    }
}
