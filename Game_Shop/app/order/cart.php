<?php
require '../_base.php';
auth('Member');

$cart = get_cart();

if (is_post()) {
    $action = req('action');

    if ($action == 'update') {
        $units = req('units'); 
        foreach ($units as $product_id => $unit) {
            update_cart($product_id, (int)$unit);
        }
        temp('info', 'Cart updated');
        redirect();
    }

    if ($action == 'checkout' && $cart) {
        $total_price = 0;
        $items_to_insert = [];

        foreach ($cart as $product_id => $quantity) {
            $stm = $_db->prepare('SELECT * FROM product WHERE product_id = ?');
            $stm->execute([$product_id]);
            $p = $stm->fetch();

            if ($p) {
                // Adjust quantity if user attempts to buy more than stock
                $qty = min($quantity, $p->stock_quantity);
                if ($qty > 0) {
                    $subtotal = $p->price * $qty;
                    $total_price += $subtotal;
                    
                    $items_to_insert[] = [
                        'product_id' => $p->product_id,
                        'unit_price' => $p->price,
                        'quantity'   => $qty
                    ];
                }
            }
        }

        if ($total_price > 0) {
            // Create Purchase Record
            $stm = $_db->prepare('INSERT INTO `purchase` (user_id, purchase_date, total_price, status) VALUES (?, NOW(), ?, ?)');
            $stm->execute([$_user->user_id, $total_price, 'Pending']);
            $purchase_id = $_db->lastInsertId();

            // Create Detail Records and Update Stock
            $stm_detail = $_db->prepare('INSERT INTO `purchase_detail` (purchase_id, product_id, unit_price, quantity) VALUES (?, ?, ?, ?)');
            $stm_stock = $_db->prepare('UPDATE product SET stock_quantity = stock_quantity - ? WHERE product_id = ?');

            foreach ($items_to_insert as $i) {
                $stm_detail->execute([$purchase_id, $i['product_id'], $i['unit_price'], $i['quantity']]);
                $stm_stock->execute([$i['quantity'], $i['product_id']]);
            }

            clear_cart();
            temp('info', 'Checkout successful! Purchase created.');
            redirect('/product/list.php');
        } else {
            temp('info', 'Items in your cart are currently out of stock.');
            redirect();
        }
    }
}

$_title = 'Shopping Cart';
require '../_head.php';
?>

<?php if (!$cart): ?>
    <p>Your cart is empty.</p>
    <a href="/product/list.php" class="button">Continue Shopping</a>
<?php else: ?>
    <form method="post">
        <table class="table">
            <tr>
                <th>Photo</th>
                <th>Name</th>
                <th>Price (RM)</th>
                <th>Quantity</th>
                <th>Subtotal (RM)</th>
                <th>Remove</th>
            </tr>
            <?php 
            $grand_total = 0;
            foreach ($cart as $product_id => $quantity): 
                $stm = $_db->prepare('SELECT * FROM product WHERE product_id = ?');
                $stm->execute([$product_id]);
                $p = $stm->fetch();
                if(!$p) continue;
                $subtotal = $p->price * $quantity;
                $grand_total += $subtotal;
            ?>
            <tr>
                <td><img src="/photos/<?= $p->photo ?>" width="50" height="50" style="object-fit:cover;"></td>
                <td><?= encode($p->name) ?></td>
                <td><?= $p->price ?></td>
                <td>
                    <input type="number" name="units[<?= $p->product_id ?>]" value="<?= $quantity ?>" min="1" max="<?= $p->stock_quantity ?>" style="width: 60px;">
                    <?php if($quantity > $p->stock_quantity) echo "<br><span class='err'>Only {$p->stock_quantity} in stock!</span>"; ?>
                </td>
                <td><?= number_format($subtotal, 2) ?></td>
                <td>
                    <button type="submit" name="units[<?= $p->product_id ?>]" value="0" formnovalidate>Remove</button>
                </td>
            </tr>
            <?php endforeach ?>
            <tr>
                <th colspan="4" class="right">Grand Total:</th>
                <th>RM <?= number_format($grand_total, 2) ?></th>
                <th></th>
            </tr>
        </table>
        
        <br>
        <button name="action" value="update">Update Cart</button>
        <button name="action" value="checkout" style="background:#4CAF50; color:white;">Checkout</button>
    </form>
<?php endif ?>

<?php require '../_foot.php'; ?>