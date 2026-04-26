<?php
require '../_base.php';
auth('Admin');

$id = req('id');

// Prevent self-deletion
if ($id == $_user->user_id) {
    temp('info', 'You cannot delete your own admin account.');
    redirect('index.php');
}

$stm = $_db->prepare('SELECT * FROM user WHERE user_id = ?');
$stm->execute([$id]);
$u = $stm->fetch();

if ($u) {
    try {
        // Delete user's shopping cart items first (to clear foreign keys)
        $stm_cart = $_db->prepare('DELETE FROM shopping_cart WHERE user_id = ?');
        $stm_cart->execute([$id]);

        // Attempt to delete user
        $stm = $_db->prepare('DELETE FROM user WHERE user_id = ?');
        $stm->execute([$id]);
        
        // Remove profile photo from server if it's not the default
        if ($u->profile_photo != 'default.png' && file_exists(root("photos/$u->profile_photo"))) {
            unlink(root("photos/$u->profile_photo"));
        }
        
        temp('info', "User {$u->username} has been permanently deleted.");
    } catch (Exception $e) {
        // Foreign key constraint failure (e.g., user exists in `purchase` table)
        temp('info', "Cannot delete {$u->username}. This user has past purchase records in the system.");
    }
}

redirect('index.php');