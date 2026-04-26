<?php
require '../_base.php';
auth('Admin');

$categories = $_db->query('SELECT category_id, category_name FROM category')->fetchAll(PDO::FETCH_KEY_PAIR);

if (is_post()) {
    $category_id    = req('category_id');
    $name           = req('name');
    $description    = req('description');
    $price          = req('price');
    $stock_quantity = req('stock_quantity');
    $f              = get_file('photo');

    if ($category_id == '') $_err['category_id'] = 'Required';
    if ($name == '')        $_err['name']        = 'Required';
    if ($description == '') $_err['description'] = 'Required';

    if ($price == '') {
        $_err['price'] = 'Required';
    } else if (!is_money($price)) {
        $_err['price'] = 'Invalid format';
    } else if ($price < 0) {
        $_err['price'] = 'Price cannot be negative';
    }

    if ($stock_quantity == '') {
        $_err['stock_quantity'] = 'Required';
    } else if (!filter_var($stock_quantity, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]])) {
        $_err['stock_quantity'] = 'Must be one or greater';
    }

    if (!$f) {
        $_err['photo'] = 'Required';
    } else if (!str_starts_with($f->type, 'image/')) {
        $_err['photo'] = 'Must be an image';
    }

    if (!$_err) {
        $photo = save_photo($f, '../photos');
        $stm = $_db->prepare('INSERT INTO product (category_id, name, description, price, stock_quantity, photo) VALUES (?, ?, ?, ?, ?, ?)');
        $stm->execute([$category_id, $name, $description, $price, $stock_quantity, $photo]);
        temp('info', 'Product inserted successfully');
        redirect('index.php');
    }
}

$_title = 'Insert Product';
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

    <label>Photo</label>
    <label class="upload" for="photo" style="cursor:pointer;" title="Click to choose a photo">
        <img id="photo-preview" src="/images/photo.jpg" alt="Product Photo Preview">
    </label>

    <input type="file"
           id="photo"
           name="photo"
           accept="image/*"
           style="position:absolute; width:1px; height:1px; opacity:0; overflow:hidden;">

    <?= err('photo') ?>

    <section>
        <button>Insert</button>
        <button type="reset" id="reset-btn">Clear</button>
        <a href="/product/index.php" class="button btn-cancel">Cancel</a>
    </section>
</form>

<script>
(function () {
    const input   = document.getElementById('photo');
    const preview = document.getElementById('photo-preview');
    const reset   = document.getElementById('reset-btn');

    if (!input || !preview) return;

    input.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;

        if (!file.type.startsWith('image/')) {
            alert('Please select a valid image file.');
            this.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = e => { preview.src = e.target.result; };
        reader.readAsDataURL(file);
    });

    reset.addEventListener('click', function () {
        preview.src = '/images/photo.jpg';
    });
})();
</script>

<?php require '../_foot.php'; ?>