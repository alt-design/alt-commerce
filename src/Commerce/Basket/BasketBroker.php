<?php

namespace AltDesign\AltCommerce\Commerce\Basket;

use AltDesign\AltCommerce\BasketDrivers\Request\RequestBasketDriverFactory;
use AltDesign\AltCommerce\Contracts\Resolver;

class BasketBroker
{

    public static array $drivers = [
        'request' => RequestBasketDriverFactory::class,
    ];

    protected array $baskets = [];

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(protected Resolver $resolver, protected array $config = [])
    {

    }

    public function context(string $context = 'default'): BasketContext
    {
        if (!array_key_exists($context, $this->config)) {
            throw new \Exception("No basket configuration found for '{$context}'");
        }

        if (!in_array($context, $this->baskets)) {
            $this->baskets[$context] = $this->obtainNewContext(
                context: $context,
                config: $this->config[$context],
            );
        }

        return $this->baskets[$context];
    }


    protected function obtainNewContext(string $context, array $config): BasketContext
    {

        $driver = $config['driver'] ?? '';
        if (empty(self::$drivers[$driver])) {
            throw new \Exception("Invalid driver '{$driver}' for basket '$context'");
        }

        unset($config['driver']);

        $driver = (new self::$drivers[$driver]())->create(
            resolver: $this->resolver,
            config: $config,
        );

        return new BasketContext(
            resolver: $this->resolver,
            driver: $driver,
            context: $context
        );

    }

}