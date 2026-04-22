<?php
require '../_base.php';

// Redirect to home if already logged in
if ($_user) redirect('/');

if (is_post()) {
    $username = req('username');
    $email = req('email');
    $full_name = req('full_name');
    $password = req('password');
    $confirm = req('confirm');
    $f = get_file('profile_photo');

    // Validations
    if ($username == '') {
        $_err['username'] = 'Required';
    } else if (!is_unique($username, 'user', 'username')) {
        $_err['username'] = 'Username is already taken';
    } else if (strlen($username) > 100) {
        $_err['username'] = 'Maximum 100 characters';
    }

    if ($email == '') {
        $_err['email'] = 'Required';
    } else if (!is_email($email)) {
        $_err['email'] = 'Invalid email format';
    } else if (!is_unique($email, 'user', 'email')) {
        $_err['email'] = 'Email is already registered';
    } else if (strlen($email) > 100) {
        $_err['email'] = 'Maximum 100 characters';
    }

    if ($full_name == '') $_err['full_name'] = 'Required';

    if ($password == '') {
        $_err['password'] = 'Required';
    } else if (strlen($password) < 5 || strlen($password) > 100) {
        $_err['password'] = 'Between 5-100 characters';
    }

    if ($confirm == '') {
        $_err['confirm'] = 'Required';
    } else if ($password != $confirm) {
        $_err['confirm'] = 'Passwords do not match';
    } else if (strlen($confirm) < 5 || strlen($confirm) > 100) {
        $_err['confirm'] = 'Between 5-100 characters';
    }

    // Photo validation (optional)
    if ($f) {
        if (!str_starts_with($f->type, 'image/')) {
            $_err['profile_photo'] = 'Must be an image file.';
        } else if ($f->size > 1 * 1024 * 1024) {
            $_err['profile_photo'] = 'Maximum 1MB size allowed.';
        }
    }

    // Database Insertion
    if (!$_err) {
        // Use default avatar if no photo uploaded
        $photo = 'default.png';
        if ($f) {
            // Save uploaded photo and override default
            $photo = save_photo($f, root('photos'));
        }
        $stm = $_db->prepare(
            "INSERT INTO user (username, email, password, full_name, role, profile_photo) 
                VALUES (?, ?, SHA1(?), ?, \"Member\", ?)"
        );
        $stm->execute([$username, $email, $password, $full_name, $photo]);
        temp('info', 'Registration successful! Welcome to the store.');
        redirect('/login.php');
    }
}

$_title = 'Create an Account';
require '../_head.php';
?>

<form method="post" enctype="multipart/form-data" class="form">
    <label for="username">Username</label>
    <?= html_text('username', 'maxlength="30"') ?>
    <?= err('username') ?>

    <label for="full_name">Full Name</label>
    <?= html_text('full_name', 'maxlength="100"') ?>
    <?= err('full_name') ?>

    <label for="email">Email Address</label>
    <?= html_text('email', 'maxlength="100"') ?>
    <?= err('email') ?>

    <label for="password">Password</label>
    <?= html_password('password', 'maxlength="100"') ?>
    <?= err('password') ?>

    <label for="confirm">Confirm Password</label>
    <?= html_password('confirm', 'maxlength="100"') ?>
    <?= err('confirm') ?>

    <label for="profile_photo">Profile Photo</label>
    <label class="upload">
        <img src="/images/photo.jpg">
        <?= html_file('profile_photo', 'image/*', 'hidden') ?>
    </label>
    <?= err('profile_photo') ?>

    <section>
        <button>Register</button>
        <button type="reset">Clear</button>
    </section>
</form>

<?php require '../_foot.php'; ?>