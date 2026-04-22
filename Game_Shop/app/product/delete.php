<?php
require '../_base.php';
auth('Admin');

$product_id = req('product_id');

$stm = $_db->prepare('SELECT * FROM product WHERE product_id = ?');
$stm->execute([$product_id]);
$p = $stm->fetch();

if ($p) {
    try {
        $stm = $_db->prepare('DELETE FROM product WHERE product_id = ?');
        $stm->execute([$product_id]);
        unlink(root("photos/$p->photo"));
        temp('info', 'Product deleted successfully');
    } catch (Exception $e) {
        temp('info', 'Cannot delete product; it is referenced in past purchases.');
    }
}

redirect('index.php');