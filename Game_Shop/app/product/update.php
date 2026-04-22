<?php
require '../_base.php';
auth('Admin');

$product_id = req('product_id');
$stm = $_db->prepare('SELECT * FROM product WHERE product_id = ?');
$stm->execute([$product_id]);
$p = $stm->fetch();

if (!$p) redirect('index.php');

$categories = $_db->query('SELECT category_id, category_name FROM category')->fetchAll(PDO::FETCH_KEY_PAIR);

if (is_post()) {
    $category_id = req('category_id');
    $name = req('name');
    $description = req('description');
    $price = req('price');
    $stock_quantity = req('stock_quantity');
    $f = get_file('photo');

    if ($category_id == '') $_err['category_id'] = 'Required';
    if ($name == '') $_err['name'] = 'Required';
    if ($description == '') $_err['description'] = 'Required';
    
    if ($price == '') {
        $_err['price'] = 'Required';
    } else if (!is_money($price)) {
        $_err['price'] = 'Invalid format';
    }

    if ($stock_quantity == '') {
        $_err['stock_quantity'] = 'Required';
    } else if (!filter_var($stock_quantity, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]])) {
        $_err['stock_quantity'] = 'Must be zero or greater';
    }

    if ($f && !str_starts_with($f->type, 'image/')) {
        $_err['photo'] = 'Must be an image';
    }

    if (!$_err) {
        if ($f) {
            unlink(root("photos/$p->photo"));
            $photo = save_photo($f, '../photos');
            $stm = $_db->prepare('UPDATE product SET category_id = ?, name = ?, description = ?, price = ?, stock_quantity = ?, photo = ? WHERE product_id = ?');
            $stm->execute([$category_id, $name, $description, $price, $stock_quantity, $photo, $product_id]);
        } else {
            $stm = $_db->prepare('UPDATE product SET category_id = ?, name = ?, description = ?, price = ?, stock_quantity = ? WHERE product_id = ?');
            $stm->execute([$category_id, $name, $description, $price, $stock_quantity, $product_id]);
        }

        temp('info', 'Product updated successfully');
        redirect('index.php');
    }
} else {
    extract((array)$p);
}

$_title = 'Update Product';
require '../_head.php';
?>

<form method="post" enctype="multipart/form-data" class="form">
    <label for="category_id">Category</label>
    <?= html_select('category_id', $categories) ?>
    <?= err('category_id') ?>

    <label for="name">Name</label>
    <?= html_text('name', 'maxlength="100"') ?>
    <?= err('name') ?>

    <label for="description">Description</label>
    <?= html_textarea('description') ?>
    <?= err('description') ?>

    <label for="price">Price (RM)</label>
    <?= html_text('price', 'maxlength="10"') ?>
    <?= err('price') ?>

    <label for="stock_quantity">Stock</label>
    <?= html_number('stock_quantity', 0, 1000) ?>
    <?= err('stock_quantity') ?>

    <label for="photo">Photo</label>
    <label class="upload">
        <img src="/photos/<?= $p->photo ?>">
        <?= html_file('photo', 'image/*', 'hidden') ?>
    </label>
    <?= err('photo') ?>

    <section>
        <button>Update</button>
        <button type="reset">Reset</button>
    </section>
</form>

<?php require '../_foot.php'; ?>