<?php

namespace AltDesign\AltCommerce\Traits;

use AltDesign\AltCommerce\Support\GatewayEntity;

/**
 * @var GatewayEntity[] $gatewayEntities
 */
trait HasGatewayEntity
{

    public function findGatewayId(string $gateway): string|null
    {
        foreach ($this->gatewayEntities as $entity) {
            if ($entity->gateway === $gateway) {
                return $entity->gatewayId;
            }
        }
        return null;
    }

    public function getGatewayId(string $gateway): string
    {
        return $this->findGatewayId($gateway) ??  throw new \Exception('Unable to find gateway id for '.$gateway);
    }

    public function setGatewayId(string $gateway, string $id): void
    {
        $this->removeGatewayId($gateway);
        $this->gatewayEntities[] = new GatewayEntity($gateway, $id);
    }

    public function removeGatewayId(string $gateway): void
    {
        foreach ($this->gatewayEntities as $key => $gatewayEntity) {
            if ($gatewayEntity->gateway === $gateway) {
                unset($this->gatewayEntities[$key]);
            }
        }
    }
}