<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class EntityFactoryService
{
    public function createEntity(string $entityClass, array $parameters)
    {
        if (!class_exists($entityClass)) {
            throw new BadRequestException("Invalid entity class.");
        }

        $entity = new $entityClass();

        foreach ($parameters as $field => $value) {
            $setter = 'set' . ucfirst($field);
            if (method_exists($entity, $setter)) {
                $entity->$setter($value);
            }
        }

        return $entity;
    }
}
