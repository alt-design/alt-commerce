<?php

namespace AltDesign\AltCommerce\Enum;

enum OrderStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case PROCESSED = 'processed';
    case COMPLETE = 'complete';
}