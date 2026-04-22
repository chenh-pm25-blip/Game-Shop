<?php
require '../_base.php';
auth('Member');

$stm = $_db->prepare("SELECT * FROM purchase WHERE user_id = ? ORDER BY purchase_date DESC");
$stm->execute([$_user->user_id]);
$orders = $stm->fetchAll();

$_title = 'Purchase History';
require '../_head.php';
?>

<?php if (empty($orders)): ?>
    <p style="color:#8f98a0;">You have not made any purchases yet.</p>
    <a href="/product/list.php" class="button">Browse the Store</a>
<?php else: ?>
    <table class="table">
        <tr>
            <th>Order ID</th>
            <th>Date</th>
            <th>Total (RM)</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($orders as $o): ?>
        <tr>
            <td>#<?= $o->purchase_id ?></td>
            <td><?= $o->purchase_date ?></td>
            <td><?= number_format($o->total_price, 2) ?></td>
            <td style="color: <?= $o->status == 'Pending' ? '#ff9800' : ($o->status == 'Cancelled' ? '#f44336' : '#4CAF50') ?>;">
                <?= $o->status ?>
            </td>
            <td>
                <a href="detail.php?id=<?= $o->purchase_id ?>">View Details</a>
                <?php if ($o->status == 'Pending'): ?>
                    | <a href="cancel.php?id=<?= $o->purchase_id ?>" data-confirm="Are you sure you want to cancel this order?">Cancel Order</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<?php require '../_foot.php'; ?>