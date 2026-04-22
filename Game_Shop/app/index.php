<?php
require '_base.php';
auth('Admin'); // Only admins can view the user list

$search = req('search');

// Fetch users, sorted by role (Admins first) then username
$stm = $_db->prepare('SELECT * FROM user WHERE username LIKE ? OR email LIKE ? ORDER BY role ASC, username ASC');
$stm->execute(["%$search%", "%$search%"]);
$users = $stm->fetchAll();

$_title = 'User Management';
require '_head.php';
?>

<form method="get" class="form" style="margin-bottom: 20px;">
    <label for="search">Search Users</label>
    <?= html_search('search', 'placeholder="Username or Email"') ?>
    <section>
        <button>Search</button>
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
            <?php if ($u->user_id != $_user->user_id): // Prevent admin from deleting themselves ?>
                <a href="delete.php?id=<?= $u->user_id ?>" data-confirm="Are you sure you want to delete user <?= encode($u->username) ?>? This action cannot be undone.">Delete</a>
            <?php else: ?>
                <span style="color:#8f98a0;">(You)</span>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach ?>
</table>

<?php require '_foot.php'; ?>