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
        $email = req('email');

        if ($full_name == '') $_err['full_name'] = 'Required';
        
        if ($email == '') $_err['email'] = 'Required';
        else if (!is_email($email)) $_err['email'] = 'Invalid email format';
        else if ($email != $u->email && !is_unique($email, 'user', 'email')) $_err['email'] = 'Email already in use';

        if (!$_err) {
            $stm = $_db->prepare("UPDATE user SET full_name = ?, email = ? WHERE user_id = ?");
            $stm->execute([$full_name, $email, $u->user_id]);
            
            // Update session variable
            $_SESSION['user']->full_name = $full_name;
            $_SESSION['user']->email = $email;
            
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
            // Delete old photo if it's not a generic default one
            if ($u->profile_photo != 'default.jpg' && file_exists(root("photos/$u->profile_photo"))) {
                unlink(root("photos/$u->profile_photo"));
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

<div style="display:flex; gap: 40px; align-items:flex-start;">
    <form method="post" enctype="multipart/form-data" class="form">
        <input type="hidden" name="action" value="update_photo">
        <label for="profile_photo">Avatar</label>
        <label class="upload">
            <img src="/photos/<?= $u->profile_photo ?>">
            <?= html_file('profile_photo', 'image/*', 'hidden data-autosubmit') ?>
        </label>
        <?= err('profile_photo') ?>
        <p style="font-size:12px; color:#8f98a0;">Click image to upload new avatar</p>
    </form>

    <form method="post" class="form" style="flex:1;">
        <input type="hidden" name="action" value="update_profile">
        
        <label>Username</label>
        <input type="text" value="<?= $u->username ?>" disabled>
        <span></span>

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

<?php require '../_foot.php'; ?>