<?php
require '../_base.php';

$product_id = req('product_id');
$stm = $_db->prepare('SELECT p.*, c.category_name FROM product p JOIN category c ON p.category_id = c.category_id WHERE p.product_id = ?');
$stm->execute([$product_id]);
$p = $stm->fetch();

if (!$p) redirect('list.php');

if (is_post()) {
    auth('Member'); 
    $unit = req('unit');
    
    if (filter_var($unit, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => $p->stock_quantity]])) {
        $cart = get_cart();
        $current_unit = $cart[$product_id] ?? 0;
        
        $new_quantity = $current_unit + $unit;
        if ($new_quantity > $p->stock_quantity) {
             $_err['unit'] = "Cannot add more. Only {$p->stock_quantity} left in stock.";
        } else {
            update_cart($product_id, $new_quantity);
            temp('info', 'Added to cart');
            redirect('/order/cart.php');
        }
    } else {
        $_err['unit'] = 'Invalid quantity';
    }
}

$_title = encode($p->name);
require '../_head.php';
?>

<div style="display: flex; gap: 30px;">
    <img src="/photos/<?= $p->photo ?>" width="400" height="400" style="object-fit:cover; border:1px solid #333;">
    <div>
        <p><b>Category:</b> <?= encode($p->category_name) ?></p>
        <h2><?= encode($p->name) ?></h2>
        <p><?= nl2br(encode($p->description)) ?></p>
        <h3 style="color: #d32f2f;">RM <?= $p->price ?></h3>
        <p><b>In Stock:</b> <?= $p->stock_quantity ?></p>
        
        <?php if ($p->stock_quantity > 0): ?>
        <form method="post" class="form">
            <label for="unit">Quantity</label>
            <?= html_number('unit', 1, $p->stock_quantity, 1, 'value="1"') ?>
            <?= err('unit') ?>
            
            <section>
                <button>Add to Cart</button>
            </section>
        </form>
        <?php else: ?>
            <p style="color:red; font-weight:bold;">Out of Stock</p>
        <?php endif ?>
    </div>
</div>

<?php require '../_foot.php'; ?>