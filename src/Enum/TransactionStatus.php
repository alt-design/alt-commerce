<?php

namespace AltDesign\AltCommerce\Enum;

enum TransactionStatus: string
{
    case FAILED = 'failed';
    case SETTLED = 'settled';
    case PENDING = 'pending';
}