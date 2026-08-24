<?php

namespace App;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Succeeded = 'succeeded';
    case Failed = 'failed';

    public function canTransitionTo(PaymentStatus $status): bool
    {
        return match ($this) {
            self::Pending => $status === self::Processing,
            self::Processing => in_array($status, [
                self::Succeeded,
                self::Failed,
            ], true),
            self::Succeeded,
            self::Failed => false,
        };
    }
}
