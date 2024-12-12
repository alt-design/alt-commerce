<?php

namespace AltDesign\AltCommerce\Enum;

enum OrderStatus: string
{
    case DRAFT = 'draft';
    case PROCESSING = 'processing';
    case COMPLETE = 'complete';
}