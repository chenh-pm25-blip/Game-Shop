<?php
require '../_base.php';
auth('Admin');

if (is_post()) {
    $username  = req('username');
    $email     = req('email');
    $full_name = req('full_name');
    $password  = req('password');
    $confirm   = req('confirm');
    $role      = req('role');
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

    if (!in_array($role, ['Admin', 'Member'])) {
        $_err['role'] = 'Invalid role';
    }

    // Photo validation
    if ($f) {
        if (!str_starts_with($f->type, 'image/')) {
            $_err['profile_photo'] = 'Must be an image file.';
        } else if ($f->size > 1 * 1024 * 1024) {
            $_err['profile_photo'] = 'Maximum 1MB size allowed.';
        }
    }

    // Database Insertion
    if (!$_err) {
        $photo = 'default.jpg';
        if ($f) {
            $photo = save_photo($f, root('photos'));
        }

        $stm = $_db->prepare(
            "INSERT INTO user (username, email, password, full_name, role, profile_photo) 
             VALUES (?, ?, SHA1(?), ?, ?, ?)"
        );
        $stm->execute([$username, $email, $password, $full_name, $role, $photo]);

        temp('info', 'User added successfully.');
        redirect('index.php');
    }
}

$_title = 'Add New User';
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

    <label for="role">Role</label>
    <?= html_select('role', ['Member' => 'Member', 'Admin' => 'Admin']) ?>
    <?= err('role') ?>

    <label for="password">Password</label>
    <?= html_password('password', 'maxlength="100"') ?>
    <?= err('password') ?>

    <label for="confirm">Confirm Password</label>
    <?= html_password('confirm', 'maxlength="100"') ?>
    <?= err('confirm') ?>

    <label>Profile Photo <span class="text-muted">(optional)</span></label>
    <label class="upload" for="profile_photo" title="Click to choose a photo">
        <img id="avatar-preview" src="/images/photo.jpg" alt="Profile Photo Preview">
    </label>

    <input type="file"
           id="profile_photo"
           name="profile_photo"
           accept="image/*"
           class="hidden-file">

    <?= err('profile_photo') ?>
    <p class="text-muted">Click image to upload a profile photo</p>

    <section>
        <button>Add User</button>
        <button type="reset" id="reset-btn">Clear</button>
        <a href="index.php" class="button btn-cancel">Cancel</a>
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

        const reader = new FileReader();
        reader.onload = e => { preview.src = e.target.result; };
        reader.readAsDataURL(file);
    });

    reset.addEventListener('click', function () {
        setTimeout(() => {
            preview.src = '/images/photo.jpg';
        }, 10);
    });
})();
</script>

<?php require '../_foot.php'; ?>
