<?php
require '../_base.php';
auth();

$purchase_id = req('id');

$stm = $_db->prepare("SELECT p.*, u.username, u.full_name, u.email FROM purchase p JOIN user u ON p.user_id = u.user_id WHERE p.purchase_id = ?");
$stm->execute([$purchase_id]);
$order = $stm->fetch();

if (!$order) redirect('/');

// Security check: Only Admins or the Member who owns the order can view it
if ($_user->role != 'Admin' && $order->user_id != $_user->user_id) {
    redirect('/');
}

// Admin Update Status form
if (is_post() && $_user->role == 'Admin') {
    $action = req('action');
    
    if ($action == 'delete') {
        // Restore stock if not already cancelled
        if ($order->status != 'Cancelled') {
            $stm = $_db->prepare("SELECT product_id, quantity FROM purchase_detail WHERE purchase_id = ?");
            $stm->execute([$purchase_id]);
            $items = $stm->fetchAll();
            $restore_stm = $_db->prepare("UPDATE product SET stock_quantity = stock_quantity + ? WHERE product_id = ?");
            foreach ($items as $item) {
                $restore_stm->execute([$item->quantity, $item->product_id]);
            }
        }
        
        // Delete purchase details first due to foreign key
        $stm = $_db->prepare("DELETE FROM purchase_detail WHERE purchase_id = ?");
        $stm->execute([$purchase_id]);
        
        // Delete purchase
        $stm = $_db->prepare("DELETE FROM purchase WHERE purchase_id = ?");
        $stm->execute([$purchase_id]);
        
        temp('info', 'Order has been permanently removed.');
        redirect('index.php');
    } else {
        $new_status = req('status');
        $stm = $_db->prepare("UPDATE purchase SET status = ? WHERE purchase_id = ?");
        $stm->execute([$new_status, $purchase_id]);
        
        temp('info', 'Order status updated.');
        redirect("detail.php?id=$purchase_id");
    }
}

// Fetch Items
$stm = $_db->prepare("SELECT pd.*, pr.name, pr.photo FROM purchase_detail pd JOIN product pr ON pd.product_id = pr.product_id WHERE pd.purchase_id = ?");
$stm->execute([$purchase_id]);
$items = $stm->fetchAll();

$_title = "Order #$purchase_id Details";
require '../_head.php';
?>

<div class="detail-box">
    <p><strong>Customer:</strong> <?= encode($order->full_name) ?> (<?= $order->email ?>)</p>
    <p><strong>Transaction Date:</strong> <?= $order->purchase_date ?></p>
    <p><strong>Status:</strong> <span class="font-bold"><?= $order->status ?></span></p>

    <?php if ($_user->role == 'Admin'): ?>
        <hr class="detail-hr">
        <form method="post" class="form form-transparent">
            <label>Update Status:</label>
            <select name="status">
                <option value="Pending" <?= $order->status=='Pending'?'selected':'' ?>>Pending</option>
                <option value="Paid" <?= $order->status=='Paid'?'selected':'' ?>>Paid</option>
                <option value="Cancelled" <?= $order->status=='Cancelled'?'selected':'' ?>>Cancelled</option>
            </select>
            <section class="flex gap-10 align-center">
                <button name="action" value="update">Update</button>
                <button name="action" value="delete" class="button btn-danger" data-confirm="Are you sure you want to permanently delete this unauthorized order?">Remove Order</button>
            </section>
        </form>
    <?php endif; ?>
</div>

<h3>Items Purchased</h3>
<table class="table">
    <tr>
        <th>Cover</th>
        <th>Game Title</th>
        <th>Unit Price (RM)</th>
        <th>Qty</th>
        <th class="right">Subtotal (RM)</th>
    </tr>
    <?php foreach ($items as $item): ?>
    <tr>
        <td><img src="/photos/<?= $item->photo ?>" class="img-cover-40"></td>
        <td><?= encode($item->name) ?></td>
        <td><?= $item->unit_price ?></td>
        <td><?= $item->quantity ?></td>
        <td class="right"><?= number_format($item->unit_price * $item->quantity, 2) ?></td>
    </tr>
    <?php endforeach; ?>
    <tr>
        <th colspan="4" class="right">Grand Total:</th>
        <th class="right">RM <?= number_format($order->total_price, 2) ?></th>
    </tr>
</table>

<p class="mt-20">
    <?php if ($_user->role != 'Admin' && $order->status == 'Pending'): ?>
        <a href="payment.php?id=<?= $order->purchase_id ?>" class="button btn-success mr-10">Pay Now</a>
    <?php endif; ?>
    <a href="<?= $_user->role == 'Admin' ? 'index.php' : 'history.php' ?>" class="button btn-secondary">Back to List</a>
</p>

<?php require '../_foot.php'; ?>