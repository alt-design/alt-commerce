<?php

namespace AltDesign\AltCommerce\Enum;

enum StockPolicy: string
{
    case UNTRACKED = 'untracked';
    case TRACKED = 'tracked';
    case BACKORDER = 'backorder';
}
