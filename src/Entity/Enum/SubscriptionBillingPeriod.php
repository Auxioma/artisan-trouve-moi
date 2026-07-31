<?php

declare(strict_types=1);

namespace App\Entity\Enum;

enum SubscriptionBillingPeriod: string
{
    case MONTHLY = 'monthly';
    case YEARLY = 'yearly';
}
