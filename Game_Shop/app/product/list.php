<?php
require '../_base.php';

$search = req('search');
$category = req('category');
$sort = req('sort');

// Fetch categories for dropdown
$categories_stm = $_db->prepare('SELECT category_id, category_name FROM category ORDER BY category_name');
$categories_stm->execute();
$categories_array = $categories_stm->fetchAll(PDO::FETCH_KEY_PAIR);

$sql = 'SELECT p.*, c.category_name FROM product p JOIN category c ON p.category_id = c.category_id WHERE p.name LIKE ?';
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
    $sql .= ' ORDER BY p.name ASC';
}

$stm = $_db->prepare($sql);
$stm->execute($params);
$products = $stm->fetchAll();

$_title = 'Game Store';
require '../_head.php';
?>

<form method="get" class="form mb-20">
    <label for="search">Search Games</label>
    <?= html_search('search') ?>
    
    <label for="category" class="grid-col-1">Category</label>
    <?= html_select('category', $categories_array, 'All Categories', 'data-autosubmit') ?>
    
    <label for="sort" class="grid-col-1">Sort By</label>
    <?= html_select('sort', ['price_desc' => 'Price: Highest to Lowest', 'price_asc' => 'Price: Lowest to Highest'], 'Default', 'data-autosubmit') ?>
    
    <section class="w-100 flex align-center gap-10">
        <button>Search</button>
        <a href="list.php" class="button btn-secondary">Clear</a>
    </section>
</form>

<div class="product-grid">
    <?php foreach ($products as $p): ?>
        <div class="product-card">
            <img src="/photos/<?= $p->photo ?>" class="product-img">
            <p class="product-category"><?= encode($p->category_name) ?></p>
            <h3 class="product-title"><?= encode($p->name) ?></h3>
            <p class="product-price">RM <?= $p->price ?></p>
            <a href="detail.php?product_id=<?= $p->product_id ?>" class="button">View Details</a>
        </div>
    <?php endforeach ?>
</div>

<?php require '../_foot.php'; ?>