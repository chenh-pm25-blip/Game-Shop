<?php
require '../_base.php';
auth('Admin'); // Only admins can view the user list

$search = req('search');
$role = req('role');

// Fetch users, sorted by role (Admins first) then username
$sql = 'SELECT * FROM user WHERE (username LIKE ? OR email LIKE ?)';
$params = ["%$search%", "%$search%"];

if ($role) {
    $sql .= ' AND role = ?';
    $params[] = $role;
}

$sql .= ' ORDER BY role ASC, username ASC';

$stm = $_db->prepare($sql);
$stm->execute($params);
$users = $stm->fetchAll();

$_title = 'User Management';
require '../_head.php';
?>

<form method="get" class="form" style="margin-bottom: 20px;">
    <label for="search">Search Users</label>
    <?= html_search('search', 'placeholder="Username or Email"') ?>
    
    <label for="role" style="grid-column: 1;">Filter Role</label>
    <?= html_select('role', ['Admin' => 'Admin Only', 'Member' => 'Member Only'], 'All Roles') ?>
    
    <section style="width: 100%;">
        <button>Search</button>
        <a href="/user/add.php" class="button" style="margin-left: auto;">Add User</a>
    </section>
</form>

<table class="table">
    <tr>
        <th>Avatar</th>
        <th>Username</th>
        <th>Full Name</th>
        <th>Email</th>
        <th>Role</th>
        <th>Joined</th>
        <th>Actions</th>
    </tr>
    <?php foreach ($users as $u): ?>
    <tr>
        <td>
            <img src="/photos/<?= $u->profile_photo ?>" width="40" height="40" style="object-fit:cover; border-radius:3px;">
        </td>
        <td><?= encode($u->username) ?></td>
        <td><?= encode($u->full_name) ?></td>
        <td><?= encode($u->email) ?></td>
        <td style="color: <?= $u->role == 'Admin' ? '#66c0f4' : '#c6d4df' ?>;">
            <b><?= $u->role ?></b>
        </td>
        <td><?= date('d M Y', strtotime($u->created_at)) ?></td>
        <td>
            <a href="/user/update.php?id=<?= $u->user_id ?>">Update</a>
            <?php if ($u->user_id != $_user->user_id): // Prevent admin from deleting themselves ?>
                | <a href="/user/delete.php?id=<?= $u->user_id ?>" data-confirm="Are you sure you want to delete user <?= encode($u->username) ?>? This action cannot be undone.">Delete</a>
            <?php else: ?>
                | <span style="color:#8f98a0;">(You)</span>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach ?>
</table>

<?php require '../_foot.php'; ?>