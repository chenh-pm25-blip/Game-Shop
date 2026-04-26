<?php
require '../_base.php';
auth('Admin');

$id = req('id');

$stm = $_db->prepare('SELECT * FROM user WHERE user_id = ?');
$stm->execute([$id]);
$u = $stm->fetch();

if (!$u) {
    redirect('index.php');
}

// Populate global variables for html_* functions
if (is_get()) {
    $email = $u->email;
    $full_name = $u->full_name;
    $role = $u->role;
}

if (is_post()) {
    $email     = req('email');
    $full_name = req('full_name');
    $role      = req('role');
    $f         = get_file('profile_photo');

    // Email validation
    if ($email == '') {
        $_err['email'] = 'Required';
    } else if (!is_email($email)) {
        $_err['email'] = 'Invalid email format';
    } else if ($email != $u->email && !is_unique($email, 'user', 'email')) {
        $_err['email'] = 'Email is already registered';
    } else if (strlen($email) > 100) {
        $_err['email'] = 'Maximum 100 characters';
    }

    if ($full_name == '') $_err['full_name'] = 'Required';

    if (!in_array($role, ['Admin', 'Member'])) {
        $_err['role'] = 'Invalid role';
    }
    
    // Prevent removing the last admin
    if ($u->role == 'Admin' && $role == 'Member') {
        $stm_admin = $_db->prepare("SELECT COUNT(*) FROM user WHERE role = 'Admin'");
        $stm_admin->execute();
        $admin_count = $stm_admin->fetchColumn();
        if ($admin_count <= 1) {
            $_err['role'] = 'Cannot change your own role';
        }
    }

    // Photo validation
    if ($f) {
        if (!str_starts_with($f->type, 'image/')) {
            $_err['profile_photo'] = 'Must be an image file.';
        } else if ($f->size > 1 * 1024 * 1024) {
            $_err['profile_photo'] = 'Maximum 1MB size allowed.';
        }
    }

    // Database Update
    if (!$_err) {
        $photo = $u->profile_photo;
        if ($f) {
            $photo = save_photo($f, root('photos'));
            // Delete old photo if it's not a default one
            if ($u->profile_photo != 'default.jpg' && $u->profile_photo != 'default.png' && file_exists(root("photos/$u->profile_photo"))) {
                unlink(root("photos/$u->profile_photo"));
            }
        }

        $stm = $_db->prepare(
            "UPDATE user SET email = ?, full_name = ?, role = ?, profile_photo = ? WHERE user_id = ?"
        );
        $stm->execute([$email, $full_name, $role, $photo, $id]);
        
        // Update session if editing self
        if ($_user->user_id == $id) {
            $stm = $_db->prepare('SELECT * FROM user WHERE user_id = ?');
            $stm->execute([$id]);
            $_SESSION['user'] = $stm->fetch();
        }

        temp('info', 'User updated successfully.');
        redirect('index.php');
    }
}

$_title = 'Update User';
require '../_head.php';
?>

<form method="post" enctype="multipart/form-data" class="form">

    <label for="username">Username</label>
    <!-- Cannot edit username -->
    <input type="text" id="username" value="<?= encode($u->username) ?>" disabled>
    <span></span>

    <label for="full_name">Full Name</label>
    <?= html_text('full_name', 'maxlength="100"') ?>
    <?= err('full_name') ?>

    <label for="email">Email Address</label>
    <?= html_text('email', 'maxlength="100"') ?>
    <?= err('email') ?>

    <label for="role">Role</label>
    <?= html_select('role', ['Member' => 'Member', 'Admin' => 'Admin']) ?>
    <?= err('role') ?>

    <label>Profile Photo <span class="text-muted">(optional)</span></label>
    <label class="upload" for="profile_photo" title="Click to choose a photo">
        <img id="avatar-preview" src="/photos/<?= $u->profile_photo ?>" alt="Profile Photo Preview">
    </label>

    <input type="file"
           id="profile_photo"
           name="profile_photo"
           accept="image/*"
           class="hidden-file">

    <?= err('profile_photo') ?>
    <p class="text-muted">Click image to change profile photo</p>

    <section>
        <button>Update User</button>
        <button type="reset" id="reset-btn">Reset</button>
        <a href="index.php" class="button btn-cancel">Cancel</a>
    </section>
</form>

<script>
(function () {
    const input   = document.getElementById('profile_photo');
    const preview = document.getElementById('avatar-preview');
    const reset   = document.getElementById('reset-btn');
    const originalSrc = preview.src;

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
            preview.src = originalSrc;
        }, 10);
    });
})();
</script>

<?php require '../_foot.php'; ?>
