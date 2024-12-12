<?php

namespace AltDesign\AltCommerce\Enum;

enum TransactionType: string
{
    case SALE = 'sale';
    case CREDIT = 'credit';
}