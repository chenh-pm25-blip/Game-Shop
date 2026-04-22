<?php
require '../_base.php';

// Clear expired tokens
$_db->query('DELETE FROM token WHERE expire < NOW()');

$id = req('id');

if (!is_exists($id, 'token', 'id')) {
    temp('info', 'This password reset link is invalid or has expired.');
    redirect('/');
}

if (is_post()) {
    $password = req('password');
    $confirm  = req('confirm');

    if ($password == '') $_err['password'] = 'Required';
    else if (strlen($password) < 5) $_err['password'] = 'Must be at least 5 characters';

    if ($confirm == '') $_err['confirm'] = 'Required';
    else if ($confirm != $password) $_err['confirm'] = 'Passwords do not match';

    if (!$_err) {
        $stm = $_db->prepare('
            UPDATE user SET password = SHA1(?) WHERE user_id = (SELECT user_id FROM token WHERE id = ?);
            DELETE FROM token WHERE id = ?;
        ');
        $stm->execute([$password, $id, $id]);

        temp('info', 'Password successfully updated. You may now login.');
        redirect('/login.php');
    }
}

$_title = 'Create New Password';
require '../_head.php';
?>

<form method="post" class="form">
    <label for="password">New Password</label>
    <?= html_password('password', 'maxlength="100"') ?>
    <?= err('password') ?>

    <label for="confirm">Confirm Password</label>
    <?= html_password('confirm', 'maxlength="100"') ?>
    <?= err('confirm') ?>

    <section>
        <button>Update Password</button>
    </section>
</form>

<?php require '../_foot.php'; ?>