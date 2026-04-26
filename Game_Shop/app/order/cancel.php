<?php
require '../_base.php';
auth('Member');

$purchase_id = req('id');

$stm = $_db->prepare("SELECT * FROM purchase WHERE purchase_id = ? AND user_id = ?");
$stm->execute([$purchase_id, $_user->user_id]);
$order = $stm->fetch();

if ($order && $order->status == 'Pending') {
    // 1. Mark as cancelled
    $stm = $_db->prepare("UPDATE purchase SET status = 'Cancelled' WHERE purchase_id = ?");
    $stm->execute([$purchase_id]);

    // 2. Fetch the items to return them to stock (Good E-Commerce Practice)
    $stm = $_db->prepare("SELECT product_id, quantity FROM purchase_detail WHERE purchase_id = ?");
    $stm->execute([$purchase_id]);
    $items = $stm->fetchAll();

    // 3. Restore Stock
    $restore_stm = $_db->prepare("UPDATE product SET stock_quantity = stock_quantity + ? WHERE product_id = ?");
    foreach ($items as $item) {
        $restore_stm->execute([$item->quantity, $item->product_id]);
    }

    temp('info', 'Your order has been successfully cancelled.');
} else {
    temp('info', 'This order cannot be cancelled (it may have already been processed).');
}

redirect('history.php');