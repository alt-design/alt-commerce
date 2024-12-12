<?php

namespace AltDesign\AltCommerce\Contracts;

use AltDesign\AltCommerce\Support\Location;

interface VisitorLocator
{
    public function retrieve(): Location|null;
}