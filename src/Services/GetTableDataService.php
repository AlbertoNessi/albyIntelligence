<?php

namespace App\Services;

use App\Entity\CalendarEvents;
use App\Entity\Contacts;
use App\Entity\Customers;
use App\Entity\Documentation;
use App\Entity\Emails;
use App\Entity\Events;
use App\Entity\FileDocuments;
use App\Entity\Locations;
use App\Entity\Messages;
use App\Entity\Notes;
use App\Entity\Notifications;
use App\Entity\Reminders;
use App\Entity\Tasks;
use App\Entity\Orders;
use Doctrine\ORM\EntityManagerInterface;

class GetTableDataService
{
    private const array TABLE_COLUMNS = [
        1 => ['id', 'name', 'surname', 'phone', 'email'],
        2 => ['id', 'sender', 'receiver', 'subject', 'message'],
        3 => ['id', 'sender', 'receiver', 'message'],
        4 => ['id', 'note'],
        5 => ['id', 'date', 'title', 'subtitle', 'note'],
        6 => ['id', 'dueDate', 'priority', 'task'],
        7 => ['id', 'title', 'description', 'eventDate'],
        8 => ['id', 'name', 'dueDate', 'priority', 'status'],
        9 => ['id', 'message', 'flagRead', 'action'],
        10 => ['id', 'name', 'address', 'city', 'province', 'region'],
        11 => ['id', 'filename', 'filepath', 'uploadedAt', 'fileType', 'uploadedBy'],
        12 => ['id', 'title', 'content', 'section', 'type'],
        13 => ['id', 'createdAt', 'totalAmount', 'customer', 'shippingAddress', 'billingAddress', 'notes'],
        14 => ['id', 'name', 'email', 'code'],
    ];

    private const array TABLE_NAMES = [
        1 => 'contatti',
        2 => 'emails',
        3 => 'messaggi',
        4 => 'note',
        5 => 'eventi',
        6 => 'promemoria',
        7 => 'calendario',
        8 => 'attivita',
        9 => 'notifiche',
        10 => 'localita',
        11 => 'file',
        12 => 'documentazione',
        13 => 'ordini',
        14 => 'clienti',
    ];

    private const array TABLE_ENTITY_NAMES = [
        1 => Contacts::class,
        2 => Emails::class,
        3 => Messages::class,
        4 => Notes::class,
        5 => Events::class,
        6 => Reminders::class,
        7 => CalendarEvents::class,
        8 => Tasks::class,
        9 => Notifications::class,
        10 => Locations::class,
        11 => FileDocuments::class,
        12 => Documentation::class,
        13 => Orders::class,
        14 => Customers::class
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
                foreach ($columns as $column) {
                    $getter = self::snakeToCamel('get_' . $column);
                    if (method_exists($entity, $getter)) {
                        $data[$column] = $entity->$getter();
                    } else {
                        dd("Getter method $getter does not exist on " . get_class($entity));
                    }
                }
                return $data;
            }, $results);
        }

        return [];
    }

    private static function snakeToCamel(string $string): string
    {
        $string = str_replace('_', '', ucwords($string, '_'));
        return lcfirst($string);
    }
}
