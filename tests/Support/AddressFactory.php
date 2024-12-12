<?php

namespace AltDesign\AltCommerce\Tests\Support;

use AltDesign\AltCommerce\Commerce\Customer\Address;
use Faker\Factory;

class AddressFactory
{
    public static function create(array $args = [], string $locale = 'en_GB'): Address
    {
        $faker = Factory::create($locale);
        return new Address(
            company: $args['company'] ?? $faker->company(),
            firstName: $args['firstName'] ?? $faker->firstName(),
            lastName: $args['lastName'] ?? $faker->lastName(),
            countryCode: $args['countryCode'] ?? $faker->countryCode(),
            postalCode: $args['postalCode'] ?? $faker->postcode(),
            region: $args['region'] ?? null,
            locality: $args['locality'] ?? $faker->city(),
            street: $args['street'] ?? $faker->streetName(),
            phoneNumber: $args['phoneNumber'] ?? $faker->phoneNumber(),
        );
    }
}