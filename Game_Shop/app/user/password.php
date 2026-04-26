<?php
require '../_base.php';
auth(); // Any logged-in user

if (is_post()) {
    $current = req('current_password');
    $new = req('new_password');
    $confirm = req('confirm_password');

    $stm = $_db->prepare("SELECT password FROM user WHERE user_id = ?");
    $stm->execute([$_user->user_id]);
    $hashed = $stm->fetchColumn();

    if ($current == '') $_err['current_password'] = 'Required';
    else if (SHA1($current) !== $hashed) $_err['current_password'] = 'Incorrect current password';

    if ($new == '') $_err['new_password'] = 'Required';
    else if (strlen($new) < 5) $_err['new_password'] = 'Must be at least 5 characters';

    if ($confirm == '') $_err['confirm_password'] = 'Required';
    else if ($new !== $confirm) $_err['confirm_password'] = 'Passwords do not match';

    if (!$_err) {
        $stm = $_db->prepare("UPDATE user SET password = SHA1(?) WHERE user_id = ?");
        $stm->execute([$new, $_user->user_id]);
        temp('info', 'Security settings updated successfully.');
        redirect('profile.php');
    }
}

$_title = 'Change Password';
require '../_head.php';
?>

<form method="post" class="form">
    <label for="current_password">Current Password</label>
    <?= html_password('current_password') ?>
    <?= err('current_password') ?>

    <label for="new_password">New Password</label>
    <?= html_password('new_password') ?>
    <?= err('new_password') ?>

    <label for="confirm_password">Confirm Password</label>
    <?= html_password('confirm_password') ?>
    <?= err('confirm_password') ?>

    <section>
        <button>Update Password</button>
        <button type="button" data-get="profile.php">Cancel</button>
    </section>
</form>

<?php require '../_foot.php'; ?>