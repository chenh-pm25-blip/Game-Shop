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
    $new_status = req('status');
    $stm = $_db->prepare("UPDATE purchase SET status = ? WHERE purchase_id = ?");
    $stm->execute([$new_status, $purchase_id]);
    
    temp('info', 'Order status updated.');
    redirect("detail.php?id=$purchase_id");
}

// Fetch Items
$stm = $_db->prepare("SELECT pd.*, pr.name, pr.photo FROM purchase_detail pd JOIN product pr ON pd.product_id = pr.product_id WHERE pd.purchase_id = ?");
$stm->execute([$purchase_id]);
$items = $stm->fetchAll();

$_title = "Order #$purchase_id Details";
require '../_head.php';
?>

<div style="background: rgba(0,0,0,0.2); padding: 20px; margin-bottom: 20px; border: 1px solid #1b2838;">
    <p><strong>Customer:</strong> <?= encode($order->full_name) ?> (<?= $order->email ?>)</p>
    <p><strong>Transaction Date:</strong> <?= $order->purchase_date ?></p>
    <p><strong>Status:</strong> <span style="font-weight:bold;"><?= $order->status ?></span></p>

    <?php if ($_user->role == 'Admin'): ?>
        <hr style="border: 1px solid #1b2838; margin: 15px 0;">
        <form method="post" class="form" style="padding:0; background:transparent;">
            <label>Update Status:</label>
            <select name="status">
                <option value="Pending" <?= $order->status=='Pending'?'selected':'' ?>>Pending</option>
                <option value="Paid" <?= $order->status=='Paid'?'selected':'' ?>>Paid</option>
                <option value="Cancelled" <?= $order->status=='Cancelled'?'selected':'' ?>>Cancelled</option>
            </select>
            <section><button>Update</button></section>
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
        <td><img src="/photos/<?= $item->photo ?>" width="40" height="40" style="object-fit:cover;"></td>
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

<p style="margin-top: 20px;">
    <a href="<?= $_user->role == 'Admin' ? 'index.php' : 'history.php' ?>" class="button" style="background:#475e72; color:#fff;">Back to List</a>
</p>

<?php require '../_foot.php'; ?>