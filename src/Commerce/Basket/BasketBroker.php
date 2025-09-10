<?php

namespace AltDesign\AltCommerce\Commerce\Basket;

use AltDesign\AltCommerce\Contracts\BasketDriverFactory;
use AltDesign\AltCommerce\Contracts\Resolver;

class BasketBroker
{

    protected array $instances = [];

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(protected Resolver $resolver, protected array $config = [])
    {

    }

    public function context(string $context): BasketContext
    {
        $config = $this->config['contexts'][$context] ?? [];
        if (empty($config)) {
            throw new \Exception("No basket configuration found for '{$context}'");
        }

        return $this->build($config['driver'], $context, $config);

    }


    public function build(string $driver, string $context, array $config = []): BasketContext
    {

        if ($existing = $this->findExisting($driver, $context)) {
            return $existing;
        }

        $factory = $this->driver($driver);
        $context = new BasketContext(
            resolver: $this->resolver,
            driver: $factory->create(
                resolver: $this->resolver,
                config: $config,
            ),
            context: $context
        );

        if (!empty($config['with'])) {
            $this->resolver->resolve($config['with'], [
                'context' => $context,
                'config' => $config,
            ])->run();
        }

        $instance = [
            'driverName' => $driver,
            'context' => $context,
            'basket' => $context
        ];

        $this->instances[] = $instance;

        return $instance['basket'];
    }

    protected function findExisting(string $driver, string $context): ?BasketContext
    {
        foreach ($this->instances as $instance) {
            if ($instance['driverName'] === $driver && $instance['context'] === $context) {
                return $instance['basket'];
            }
        }
        return null;
    }

    protected function driver(string $driver): BasketDriverFactory
    {
        if (empty($this->config['drivers'][$driver])) {
            throw new \Exception("Invalid basket driver '{$driver}'");
        }

        return new $this->config['drivers'][$driver]();
    }

}