<?php

namespace App\Controller\Services;

class GetTableColumnsService
{
    private const array TABLE_COLUMNS = [
        1 => ['name', 'surname', 'phone', 'email'],
        2 => ['sender', 'receiver', 'object', 'message'],
        3 => ['sender', 'receiver', 'message'],
        4 => ['note'],
        5 => ['title', 'subtitle', 'note'],
    ];

    public function getColumnsByTableId(int $tableId): array
    {
        return self::TABLE_COLUMNS[$tableId] ?? [];
    }
}
