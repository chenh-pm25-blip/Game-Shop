<?php
require '../_base.php';

if (is_post()) {
    $email = req('email');

    if ($email == '') $_err['email'] = 'Required';
    else if (!is_email($email)) $_err['email'] = 'Invalid email format';
    else if (!is_exists($email, 'user', 'email')) $_err['email'] = 'Email not found in our records';

    if (!$_err) {
        $stm = $_db->prepare('SELECT * FROM user WHERE email = ?');
        $stm->execute([$email]);
        $u = $stm->fetch();

        $id = SHA1(uniqid() . rand());

        $stm = $_db->prepare('
            DELETE FROM token WHERE user_id = ?;
            INSERT INTO token (id, expire, user_id) VALUES (?, ADDTIME(NOW(), "00:05:00"), ?);
        ');
        $stm->execute([$u->user_id, $id, $u->user_id]);

        $url = base("user/token.php?id=$id");

        // Use PHPMailer to send real email here
        $m = get_mail();
        $m->addAddress($u->email, $u->full_name);
        $m->isHTML(true);
        $m->Subject = "Steam Store: Reset Password";
        $m->Body = "
            <div style='background:#1b2838; color:#c6d4df; padding:20px; font-family:sans-serif;'>
                <h2>Dear $u->full_name,</h2>
                <p>We received a request to reset your password.</p>
                <p><a href='$url' style='color:#66c0f4;'>Click here to securely reset your password</a></p>
                <p>If you did not request this, please ignore this email.</p>
            </div>
        ";
        $m->send();
        
        temp('info', 'Password reset instructions have been sent to your email.');
        redirect('/');
    }
}

$_title = 'Reset Password';
require '../_head.php';
?>

<form method="post" class="form">
    <label for="email">Account Email</label>
    <?= html_text('email', 'maxlength="100"') ?>
    <?= err('email') ?>

    <section>
        <button>Send Reset Link</button>
        <button type="button" data-get="/login.php">Cancel</button>
    </section>
</form>

<?php require '../_foot.php'; ?>