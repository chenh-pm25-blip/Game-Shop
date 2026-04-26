<?php
require '../_base.php';
auth('Member');

$purchase_id = req('id');

$stm = $_db->prepare("SELECT * FROM purchase WHERE purchase_id = ? AND user_id = ?");
$stm->execute([$purchase_id, $_user->user_id]);
$order = $stm->fetch();

if (!$order || $order->status != 'Pending') {
    temp('info', 'Invalid or already processed order.');
    redirect('history.php');
}

if (is_post()) {
    $card_number = req('card_number');
    $card_name = req('card_name');
    $expiry_date = req('expiry_date');
    $cvv = req('cvv');

    // Fake Validation
    if (empty($card_number) || empty($card_name) || empty($expiry_date) || empty($cvv)) {
        $_err['payment'] = 'All fields are required.';
    } elseif (!preg_match('/^\d{16}$/', str_replace([' ', '-'], '', $card_number))) {
        $_err['payment'] = 'Invalid credit card number.';
    } elseif (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $expiry_date)) {
        $_err['payment'] = 'Invalid expiry date. Month must be 01-12.';
    } elseif (explode('/', $expiry_date)[1] < date('y') || (explode('/', $expiry_date)[1] == date('y') && explode('/', $expiry_date)[0] < date('m'))) {
        $_err['payment'] = 'Card has expired.';
    } elseif (!preg_match('/^\d{3,4}$/', $cvv)) {
        $_err['payment'] = 'Invalid CVV.';
    }

    if (empty($_err)) {
        $stm = $_db->prepare("UPDATE purchase SET status = 'Paid' WHERE purchase_id = ?");
        $stm->execute([$purchase_id]);
        temp('info', 'Payment successful! Thank you for your purchase.');
        redirect('history.php');
    }
}

$_title = 'Payment';
require '../_head.php';
?>

<div class="payment-container">
    <h2>Complete Your Payment</h2>
    <p>Order #<?= $order->purchase_id ?> - Total: RM <?= number_format($order->total_price, 2) ?></p>

    <form method="post" class="cc-form">
        <?php if (isset($_err['payment'])): ?>
            <div class="err" style="margin-bottom: 15px;"><?= $_err['payment'] ?></div>
        <?php endif; ?>

        <div class="form-group">
            <label for="card_name">Name on Card</label>
            <input type="text" id="card_name" name="card_name" required placeholder="John Doe" value="<?= encode(req('card_name')) ?>">
        </div>

        <div class="form-group">
            <label for="card_number">Card Number</label>
            <input type="text" id="card_number" name="card_number" required placeholder="0000 0000 0000 0000" maxlength="19" value="<?= encode(req('card_number')) ?>">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="expiry_date">Expiry Date</label>
                <input type="text" id="expiry_date" name="expiry_date" required placeholder="MM/YY" maxlength="5" value="<?= encode(req('expiry_date')) ?>">
            </div>
            <div class="form-group">
                <label for="cvv">CVV</label>
                <input type="text" id="cvv" name="cvv" required placeholder="123" maxlength="4" value="<?= encode(req('cvv')) ?>">
            </div>
        </div>

        <button type="submit" class="button pay-btn">Pay RM <?= number_format($order->total_price, 2) ?></button>
    </form>
</div>

<script>
// Format card number as user types
document.getElementById('card_number').addEventListener('input', function (e) {
    var target = e.target;
    var position = target.selectionEnd;
    var length = target.value.length;
    
    target.value = target.value.replace(/[^\d]/g, '').replace(/(.{4})/g, '$1 ').trim();
    
    if(position !== length) {
        target.selectionEnd = position;
    }
});

// Format expiry date
document.getElementById('expiry_date').addEventListener('input', function (e) {
    var target = e.target;
    target.value = target.value.replace(/[^\d]/g, '').replace(/^(\d{2})(\d{1,2})/, '$1/$2');
});
</script>

<?php require '../_foot.php'; ?>
