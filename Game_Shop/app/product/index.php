<?php
require '../_base.php';
auth('Admin');

$search = req('search');

$stm = $_db->prepare('
    SELECT p.*, c.category_name 
    FROM product p 
    JOIN category c ON p.category_id = c.category_id 
    WHERE p.name LIKE ? 
    ORDER BY p.product_id ASC
');
$stm->execute(["%$search%"]);
$products = $stm->fetchAll();

$_title = 'Product Maintenance';
require '../_head.php';
?>

<p><a href="insert.php" class="button">Insert Product</a></p>

<form method="get" class="form">
    <label for="search">Search</label>
    <?= html_search('search', 'placeholder="Product Name"') ?>
    <section><button>Search</button></section>
</form>

<p><?= count($products) ?> record(s) found.</p>

<table class="table">
    <tr>
        <th>Photo</th>
        <th>Category</th>
        <th>Name</th>
        <th>Price (RM)</th>
        <th>Stock</th>
        <th>Actions</th>
    </tr>
    <?php foreach ($products as $p): ?>
    <tr>
        <td><img src="/photos/<?= $p->photo ?>" width="50" height="50" style="object-fit:cover;"></td>
        <td><?= encode($p->category_name) ?></td>
        <td><?= encode($p->name) ?></td>
        <td><?= $p->price ?></td>
        <td><?= $p->stock_quantity ?></td>
        <td>
            <a href="update.php?product_id=<?= $p->product_id ?>">Edit</a> | 
            <a href="delete.php?product_id=<?= $p->product_id ?>" data-confirm="Delete this product?">Delete</a>
        </td>
    </tr>
    <?php endforeach ?>
</table>

<?php require '../_foot.php'; ?>
