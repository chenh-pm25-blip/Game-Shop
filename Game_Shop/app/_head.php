<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $_title ?? 'Untitled' ?></title>
    <link rel="shortcut icon" href="/images/favicon.png">
    <link rel="stylesheet" href="/css/app.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="/js/app.js"></script>
</head>
<body>
    <div id="info"><?= temp('info') ?></div>

    <header>
        <h1><a href="<?= $_user?->role == 'Admin' ? '/user/index.php' : '/' ?>">Game Shop</a></h1>

        <?php if ($_user): ?>
            <div>
                <?= $_user->full_name ?><br>
                <?= $_user->role ?>
            </div>
            <img src="/photos/<?= $_user->profile_photo ?>" style="object-fit:cover;">
        <?php endif ?>
    </header>

    <nav>
        <?php if ($_user?->role != 'Admin'): ?>
            <a href="/">Home</a>
        <?php endif ?>
        
        <?php if ($_user?->role !='Admin'): ?>
            <a href="/product/list.php">Game Store</a>
        <?php endif ?>
        
        <?php if ($_user?->role != 'Admin'): ?>
            <a href="/order/cart.php">
                Shopping Cart
            </a>
        <?php endif ?>

        <?php if ($_user?->role == 'Member'): ?>
            <a href="/order/history.php">Purchase History</a>
        <?php endif ?>

        <?php if ($_user?->role == 'Admin'): ?>
            <a href="/user/index.php">User Management</a>
            <a href="/product/index.php">Product Maintenance</a>
            <a href="/order/index.php">Order Management</a>
        <?php endif ?>

        <div></div>

        <?php if ($_user): ?>
            <a href="/user/profile.php">Profile</a>
            <a href="/user/password.php">Change Password</a>
            <a href="/logout.php">Logout</a>
        <?php else: ?>
            <a href="/user/register.php">Register</a>
            <a href="/user/reset.php">Reset Password</a>
            <a href="/login.php">Login</a>
        <?php endif ?>
    </nav>

    <main>
        <h1><?= $_title ?? 'Untitled' ?></h1>