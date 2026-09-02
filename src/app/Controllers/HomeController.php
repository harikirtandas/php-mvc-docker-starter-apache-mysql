<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Item;

// controlador de ejemplo del vertical slice demo, descartable
final class HomeController extends Controller
{
    public function index(): void
    {
        $items = Item::todos();

        $this->render('home/index', ['items' => $items]);
    }
}
