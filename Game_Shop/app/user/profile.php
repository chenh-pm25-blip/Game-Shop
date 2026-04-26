<?php
require '../_base.php';
auth(); // Any logged-in user

// Fetch current user data from DB to ensure it's fresh
$stm = $_db->prepare("SELECT * FROM user WHERE user_id = ?");
$stm->execute([$_user->user_id]);
$u = $stm->fetch();

if (is_post()) {
    $action = req('action');

    // Update Profile Info
    if ($action == 'update_profile') {
        $full_name = req('full_name');
        $email     = req('email');
        $username  = req('username');

        if ($username == '') $_err['username'] = 'Required';
        else if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) $_err['username'] = 'Only letters, numbers and underscores (3–30 chars)';
        else if ($username != $u->username && !is_unique($username, 'user', 'username')) $_err['username'] = 'Username already taken';

        if ($full_name == '') $_err['full_name'] = 'Required';

        if ($email == '') $_err['email'] = 'Required';
        else if (!is_email($email)) $_err['email'] = 'Invalid email format';
        else if ($email != $u->email && !is_unique($email, 'user', 'email')) $_err['email'] = 'Email already in use';

        if (!$_err) {
            $stm = $_db->prepare("UPDATE user SET username = ?, full_name = ?, email = ? WHERE user_id = ?");
            $stm->execute([$username, $full_name, $email, $u->user_id]);

            $_SESSION['user']->username  = $username;
            $_SESSION['user']->full_name = $full_name;
            $_SESSION['user']->email     = $email;

            temp('info', 'Profile details updated.');
            redirect('profile.php');
        }
    }

    // Update Photo
    if ($action == 'update_photo') {
        $f = get_file('profile_photo');

        if (!$f) $_err['profile_photo'] = 'Please select a photo.';
        elseif (!str_starts_with($f->type, 'image/')) $_err['profile_photo'] = 'Must be an image file.';

        if (!$_err) {
            // Delete old photo if not the default
            if ($u->profile_photo != 'default.jpg' && file_exists(root("photos/{$u->profile_photo}"))) {
                unlink(root("photos/{$u->profile_photo}"));
            }

            $new_photo = save_photo($f, root('photos'));

            $stm = $_db->prepare("UPDATE user SET profile_photo = ? WHERE user_id = ?");
            $stm->execute([$new_photo, $u->user_id]);

            $_SESSION['user']->profile_photo = $new_photo;

            temp('info', 'Profile avatar updated.');
            redirect('profile.php');
        }
    }
}

// Prefill form
extract((array)$u);
$_title = 'My Profile';
require '../_head.php';
?>

<div style="display:flex; gap:40px; align-items:flex-start;">

    <!-- Photo upload form -->
    <form method="post" enctype="multipart/form-data" class="form" id="photo-form">
        <input type="hidden" name="action" value="update_photo">

        <label>Avatar</label>
        <label class="upload" for="profile_photo" style="cursor:pointer;" title="Click to change avatar">
            <img id="avatar-preview"
                 src="/photos/<?= htmlspecialchars($u->profile_photo) ?>"
                 alt="Avatar">
        </label>

        <input type="file"
               id="profile_photo"
               name="profile_photo"
               accept="image/*"
               style="position:absolute; width:1px; height:1px; opacity:0; overflow:hidden;">

        <?= err('profile_photo') ?>
        <p style="font-size:12px; color:#8f98a0;">Click image to upload new avatar</p>
    </form>

    <!-- Profile info form -->
    <form method="post" class="form" style="flex:1;">
        <input type="hidden" name="action" value="update_profile">

        <label for="username">Username</label>
        <?= html_text('username', 'maxlength="30"') ?>
        <?= err('username') ?>

        <label for="full_name">Full Name</label>
        <?= html_text('full_name', 'maxlength="100"') ?>
        <?= err('full_name') ?>

        <label for="email">Email</label>
        <?= html_text('email', 'maxlength="100"') ?>
        <?= err('email') ?>

        <section>
            <button>Save Changes</button>
        </section>
    </form>
</div>

<script>
(function () {
    const input   = document.getElementById('profile_photo');
    const preview = document.getElementById('avatar-preview');
    const form    = document.getElementById('photo-form');

    if (!input || !form) return;

    input.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;

        // Validate client-side before submitting
        if (!file.type.startsWith('image/')) {
            alert('Please select a valid image file.');
            this.value = '';
            return;
        }

        // Show instant preview before the round-trip
        const reader = new FileReader();
        reader.onload = e => { if (preview) preview.src = e.target.result; };
        reader.readAsDataURL(file);

        // Auto-submit the photo form
        form.submit();
    });
})();
</script>

<?php require '../_foot.php'; ?>