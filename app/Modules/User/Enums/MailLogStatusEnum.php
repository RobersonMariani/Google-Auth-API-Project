<?php

namespace App\Modules\User\Enums;

enum MailLogStatusEnum: string
{
    case PENDING = 'pending';
    case SENT    = 'sent';
    case FAILED  = 'failed';
}
