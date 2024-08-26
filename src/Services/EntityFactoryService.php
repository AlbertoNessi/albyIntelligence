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
                // Check if the field is a DateTime field by looking for 'date' in the field name
                if (!($value instanceof \DateTimeInterface) && str_contains($field, 'date')) {
                    $value = $this->convertToDateTime($value);
                }

                $entity->$setter($value);
            }
        }

        return $entity;
    }

    private function convertToDateTime($value): \DateTimeInterface
    {
        try {
            return new \DateTime($value);
        } catch (\Exception $e) {
            throw new BadRequestException("Invalid date format.");
        }
    }
}
