<?php

namespace AltDesign\AltCommerce\Contracts;

use AltDesign\AltCommerce\Commerce\Settings\Settings;

interface SettingsRepository
{
    public function get(): Settings;
}