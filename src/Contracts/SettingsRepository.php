<?php

namespace AltDesign\AltCommerce\Contracts;

interface SettingsRepository
{
    public function get(): Settings;
}