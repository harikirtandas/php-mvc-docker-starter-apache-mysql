<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    // renderiza una vista dentro del layout principal. primero captura el
    // HTML de la vista con output buffering, y ese resultado queda
    // disponible como $contenido dentro de app/Views/layouts/main.php
    protected function render(string $vista, array $datos = []): void
    {
        extract($datos);

        ob_start();
        require __DIR__ . '/../Views/' . $vista . '.php';
        $contenido = ob_get_clean();

        require __DIR__ . '/../Views/layouts/main.php';
    }
}
