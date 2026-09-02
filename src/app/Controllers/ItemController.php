<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Item;

// controlador de ejemplo del vertical slice demo, descartable
final class ItemController extends Controller
{
    public function show(int $id): void
    {
        $item = Item::buscar($id);

        if ($item === null) {
            // 404 crudo, sin layout: mismo criterio que usa Router::dispatch()
            // cuando no matchea ninguna ruta
            http_response_code(404);
            require __DIR__ . '/../Views/errors/404.php';
            return;
        }

        $this->render('items/show', ['item' => $item]);
    }
}
