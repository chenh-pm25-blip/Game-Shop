<?php
require '../_base.php';

// Redirect to home if already logged in
if ($_user) redirect('/');

if (is_post()) {
    $username  = req('username');
    $email     = req('email');
    $full_name = req('full_name');
    $password  = req('password');
    $confirm   = req('confirm');
    $f         = get_file('profile_photo');

    // Validations
    if ($username == '') {
        $_err['username'] = 'Required';
    } else if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
        $_err['username'] = 'Only letters, numbers and underscores (3–30 chars)';
    } else if (!is_unique($username, 'user', 'username')) {
        $_err['username'] = 'Username is already taken';
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
        $photo = 'default.jpg';
        if ($f) {
            $photo = save_photo($f, root('photos'));
        }

        $stm = $_db->prepare(
            "INSERT INTO user (username, email, password, full_name, role, profile_photo) 
             VALUES (?, ?, SHA1(?), ?, 'Member', ?)"
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

    <label>Profile Photo <span style="font-size:12px; color:#8f98a0;">(optional)</span></label>
    <label class="upload" for="profile_photo" style="cursor:pointer;" title="Click to choose a photo">
        <img id="avatar-preview" src="/images/photo.jpg" alt="Profile Photo Preview">
    </label>

    <input type="file"
           id="profile_photo"
           name="profile_photo"
           accept="image/*"
           style="position:absolute; width:1px; height:1px; opacity:0; overflow:hidden;">

    <?= err('profile_photo') ?>
    <p style="font-size:12px; color:#8f98a0;">Click image to upload a profile photo</p>

    <section>
        <button>Register</button>
        <button type="reset" id="reset-btn">Clear</button>
    </section>
</form>

<script>
(function () {
    const input   = document.getElementById('profile_photo');
    const preview = document.getElementById('avatar-preview');
    const reset   = document.getElementById('reset-btn');

    if (!input || !preview) return;

    input.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;

        if (!file.type.startsWith('image/')) {
            alert('Please select a valid image file.');
            this.value = '';
            return;
        }

        if (file.size > 1 * 1024 * 1024) {
            alert('Image must be 1MB or smaller.');
            this.value = '';
            return;
        }

        // Show live preview of selected photo
        const reader = new FileReader();
        reader.onload = e => { preview.src = e.target.result; };
        reader.readAsDataURL(file);
    });

    // Reset preview back to default when Clear is clicked
    reset.addEventListener('click', function () {
        preview.src = '/images/photo.jpg';
    });
})();
</script>

<?php require '../_foot.php'; ?>