<?php
require '_base.php';
auth('Admin');

$search = req('search');
$status = req('status');

$sql = "SELECT p.*, u.username, u.full_name FROM purchase p JOIN user u ON p.user_id = u.user_id WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (u.username LIKE ? OR u.full_name LIKE ? OR p.purchase_id LIKE ?)";
    $like = "%$search%";
    array_push($params, $like, $like, $like);
}
if ($status) {
    $sql .= " AND p.status = ?";
    $params[] = $status;
}
$sql .= " ORDER BY p.purchase_date DESC";

$stm = $_db->prepare($sql);
$stm->execute($params);
$orders = $stm->fetchAll();

$_title = 'Order Management';
require '../_head.php';
?>

<form method="get" class="form" style="margin-bottom: 20px;">
    <label for="search">Customer / ID</label>
    <?= html_search('search', 'placeholder="Search orders..."') ?>
    
    <label for="status">Status</label>
    <select name="status" data-autosubmit>
        <option value="">All Statuses</option>
        <option value="Pending" <?= $status=='Pending'?'selected':'' ?>>Pending</option>
        <option value="Paid" <?= $status=='Paid'?'selected':'' ?>>Paid</option>
        <option value="Cancelled" <?= $status=='Cancelled'?'selected':'' ?>>Cancelled</option>
    </select>
    
    <section>
        <button>Filter</button>
    </section>
</form>

<table class="table">
    <tr>
        <th>Order ID</th>
        <th>Customer</th>
        <th>Date</th>
        <th>Total (RM)</th>
        <th>Status</th>
        <th>Action</th>
    </tr>
    <?php foreach ($orders as $o): ?>
    <tr>
        <td>#<?= $o->purchase_id ?></td>
        <td><?= encode($o->full_name) ?> (<?= $o->username ?>)</td>
        <td><?= $o->purchase_date ?></td>
        <td><?= number_format($o->total_price, 2) ?></td>
        <td><?= $o->status ?></td>
        <td><a href="detail.php?id=<?= $o->purchase_id ?>">Manage</a></td>
    </tr>
    <?php endforeach; ?>
</table>

<?php require '../_foot.php'; ?>