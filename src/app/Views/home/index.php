<?php
/** @var array $items */
// vista de ejemplo del vertical slice demo, descartable
?>
<h1>php-mvc-docker-starter-apache-mysql</h1>

<?php if ($items === []): ?>
    <p>No hay items todavia.</p>
<?php else: ?>
    <ul>
        <?php foreach ($items as $item): ?>
            <li>
                <a href="/items/<?= (int) $item['id'] ?>"><?= htmlspecialchars($item['nombre']) ?></a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
