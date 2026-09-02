<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

// modelo de ejemplo del vertical slice demo, descartable
final class Item
{
    public static function todos(): array
    {
        $stmt = Database::connection()->query('SELECT id, nombre, creado_en FROM demo_items ORDER BY id');

        return $stmt->fetchAll();
    }

    public static function buscar(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT id, nombre, creado_en FROM demo_items WHERE id = :id');
        $stmt->execute(['id' => $id]);

        $fila = $stmt->fetch();

        return $fila === false ? null : $fila;
    }
}
