<?php
/** @var array $item */
// vista de ejemplo del vertical slice demo, descartable
?>
<h1><?= htmlspecialchars($item['nombre']) ?></h1>
<p>ID: <?= (int) $item['id'] ?></p>
<p>Creado: <?= htmlspecialchars((string) $item['creado_en']) ?></p>
<p><a href="/">Volver</a></p>
