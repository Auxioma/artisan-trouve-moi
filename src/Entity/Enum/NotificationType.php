<?php

declare(strict_types=1);

namespace App\Entity\Enum;

enum NotificationType: string
{
    case NEW_REQUEST = 'new_request';
    case NEW_QUOTE = 'new_quote';
    case QUOTE_ACCEPTED = 'quote_accepted';
    case NEW_MESSAGE = 'new_message';
    case APPOINTMENT_REMINDER = 'appointment_reminder';
    case NEW_REVIEW = 'new_review';
    case SUBSCRIPTION = 'subscription';
    case SYSTEM = 'system';
}
