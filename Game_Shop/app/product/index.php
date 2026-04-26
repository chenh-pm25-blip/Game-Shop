<?php
require '../_base.php';
auth('Admin');

$search = req('search');
$category = req('category');
$sort = req('sort');

// Fetch categories for dropdown
$categories_stm = $_db->prepare('SELECT category_id, category_name FROM category ORDER BY category_name');
$categories_stm->execute();
$categories_array = $categories_stm->fetchAll(PDO::FETCH_KEY_PAIR);

$sql = '
    SELECT p.*, c.category_name 
    FROM product p 
    JOIN category c ON p.category_id = c.category_id 
    WHERE p.name LIKE ? 
';
$params = ["%$search%"];

if ($category) {
    $sql .= ' AND p.category_id = ?';
    $params[] = $category;
}

if ($sort == 'price_desc') {
    $sql .= ' ORDER BY p.price DESC';
} else if ($sort == 'price_asc') {
    $sql .= ' ORDER BY p.price ASC';
} else {
    $sql .= ' ORDER BY p.product_id ASC';
}

$stm = $_db->prepare($sql);
$stm->execute($params);
$products = $stm->fetchAll();

$_title = 'Product Maintenance';
require '../_head.php';
?>

<p><a href="insert.php" class="button">Insert Product</a></p>

<form method="get" class="form mb-20">
    <label for="search">Search</label>
    <?= html_search('search', 'placeholder="Product Name"') ?>
    
    <label for="category" class="grid-col-1">Category</label>
    <?= html_select('category', $categories_array, 'All Categories', 'data-autosubmit') ?>
    
    <label for="sort" class="grid-col-1">Sort By</label>
    <?= html_select('sort', ['price_desc' => 'Price: Highest to Lowest', 'price_asc' => 'Price: Lowest to Highest'], 'Default', 'data-autosubmit') ?>
    
    <section class="w-100 flex align-center gap-10">
        <button>Search</button>
        <a href="index.php" class="button btn-secondary">Clear</a>
    </section>
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
        <td><img src="/photos/<?= $p->photo ?>" class="img-cover-40"></td>
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
