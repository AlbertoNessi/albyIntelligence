<?php

namespace App\Services;

use App\Entity\Contacts;
use App\Entity\Emails;
use App\Entity\Events;
use App\Entity\Messages;
use App\Entity\Notes;
use Doctrine\ORM\EntityManagerInterface;

class GetTableDataService
{
    private const array TABLE_COLUMNS = [
        1 => ['id', 'name', 'surname', 'phone', 'email'],
        2 => ['id', 'sender', 'receiver', 'subject', 'message'],
        3 => ['id', 'sender', 'receiver', 'message'],
        4 => ['id', 'note'],
        5 => ['id', 'date', 'title', 'subtitle', 'note'],
    ];

    private const array TABLE_NAMES = [
        1 => 'contatti',
        2 => 'emails',
        3 => 'messaggi',
        4 => 'note',
        5 => 'eventi'
    ];

    private const array TABLE_ENTITY_NAMES = [
        1 => Contacts::class,
        2 => Emails::class,
        3 => Messages::class,
        4 => Notes::class,
        5 => Events::class
    ];

    public function getColumnsByTableId(int $tableId): array
    {
        return self::TABLE_COLUMNS[$tableId] ?? [];
    }

    public function getTableNameByTableId(int $tableId): string
    {
        return self::TABLE_NAMES[$tableId] ?? "";
    }

    public function getEntityNameByTableId(int $tableId): string
    {
        return self::TABLE_ENTITY_NAMES[$tableId] ?? "";
    }

    public function getTableDataByTableId(EntityManagerInterface $entityManager, int $tableId): array
    {
        $entityName = $this->getEntityNameByTableId($tableId);
        $columns = $this->getColumnsByTableId($tableId);

        if ($entityName && $columns) {
            $repository = $entityManager->getRepository($entityName);
            $results = $repository->findAll();

            // Convert objects to array of arrays with only specified columns
            return array_map(static function($entity) use ($columns) {
                $data = [];

                // Ensure ID is included
                $data['id'] = $entity->getId();

                foreach ($columns as $column) {
                    $getter = 'get' . ucfirst($column);
                    if (method_exists($entity, $getter)) {
                        $data[$column] = $entity->$getter();
                    } else {
                        dd($getter);
                    }
                }
                return $data;
            }, $results);
        }

        return [];
    }
}
