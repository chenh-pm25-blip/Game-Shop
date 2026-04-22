<?php
require '../_base.php';

$search = req('search');

$stm = $_db->prepare('SELECT p.*, c.category_name FROM product p JOIN category c ON p.category_id = c.category_id WHERE p.name LIKE ? ORDER BY p.name ASC');
$stm->execute(["%$search%"]);
$products = $stm->fetchAll();

$_title = 'Game Store';
require '../_head.php';
?>

<form method="get" class="form" style="margin-bottom: 20px;">
    <label for="search">Search Games</label>
    <?= html_search('search') ?>
    <section><button>Search</button></section>
</form>

<div style="display: flex; flex-wrap: wrap; gap: 20px;">
    <?php foreach ($products as $p): ?>
        <div style="border: 1px solid #ccc; padding: 10px; text-align: center; width: 220px;">
            <img src="/photos/<?= $p->photo ?>" width="200" height="200" style="object-fit:cover;">
            <p style="font-size: 12px; color: #666; margin: 5px 0;"><?= encode($p->category_name) ?></p>
            <h3 style="margin: 5px 0; font-size: 16px;"><?= encode($p->name) ?></h3>
            <p style="color: #d32f2f; font-weight: bold;">RM <?= $p->price ?></p>
            <a href="detail.php?product_id=<?= $p->product_id ?>" class="button">View Details</a>
        </div>
    <?php endforeach ?>
</div>

<?php require '../_foot.php'; ?>