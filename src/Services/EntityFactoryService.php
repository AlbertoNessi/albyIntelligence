<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class EntityFactoryService
{
    public function createEntity(string $entityClass, array $parameters)
    {
        if (!class_exists($entityClass)) {
            throw new BadRequestException("Invalid entity class: {$entityClass}.");
        }

        $entity = new $entityClass();

        foreach ($parameters as $field => $value) {
            $setter = self::snakeToCamel('set_' . $field);

            if (method_exists($entity, $setter)) {
                if (!($value instanceof \DateTimeInterface) && str_contains(strtolower($field), 'date')) {
                    try {
                        $convertedValue = $this->convertToDateTime($value);

                        $value = $convertedValue;
                    } catch (\InvalidArgumentException $e) {
                        throw new BadRequestException("Invalid date format for field '{$field}': {$e->getMessage()}");
                    }
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

    private static function snakeToCamel(string $string): string
    {
        $string = str_replace('_', '', ucwords($string, '_'));
        return lcfirst($string);
    }
}
