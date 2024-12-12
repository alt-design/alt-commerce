<?php

namespace AltDesign\AltCommerce\Enum;

enum ProductType: string
{
    case PHYSICAL = 'physical';
    case DIGITAL = 'digital';
    case OTHER = 'other';

}