<?php

namespace AltDesign\AltCommerce\Traits;

use AltDesign\AltCommerce\Support\GatewayEntity;

/**
 * @var GatewayEntity[] $gatewayEntities
 */
trait HasGatewayEntity
{

    /**
     * @param array<string, string> $context
     */
    public function findGatewayId(string $gateway, array $context = []): string|null
    {
        foreach ($this->gatewayEntities as $entity) {
            if ($entity->gateway === $gateway && $this->doesContextMatch($entity->context, $context)) {
                return $entity->gatewayId;
            }
        }
        return null;
    }

    /**
     * @param array<string, string> $context
     */
    public function getGatewayId(string $gateway, array $context = []): string
    {
        return $this->findGatewayId($gateway, $context) ??  throw new \Exception('Unable to find gateway id for '.$gateway);
    }

    /**
     * @param array<string, string> $context
     */
    public function setGatewayId(string $gateway, string $id, array $context = []): void
    {
        $this->removeGatewayId($gateway, $context);
        $this->gatewayEntities[] = new GatewayEntity($gateway, $id, $context);
    }

    public function removeGatewayId(string $gateway, array $context = []): void
    {
        foreach ($this->gatewayEntities as $key => $gatewayEntity) {
            if ($gatewayEntity->gateway === $gateway && $this->doesContextMatch($gatewayEntity->context, $context)) {
                unset($this->gatewayEntities[$key]);
            }
        }
    }

    protected function doesContextMatch(array $a, array $b): bool
    {
        ksort($a);
        ksort($b);

        return json_encode($a) === json_encode($b);
    }
}