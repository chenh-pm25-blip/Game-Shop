<?php
require '_base.php';

if ($_user?->role === 'Admin') {
    redirect('/user/index.php');
}

// Fetch the 5 most recently added games for the Featured Carousel
$stm_featured = $_db->query('
    SELECT p.*, c.category_name 
    FROM product p 
    JOIN category c ON p.category_id = c.category_id 
    ORDER BY p.product_id DESC 
    LIMIT 5
');
$featured_games = $stm_featured->fetchAll();

// Fetch all categories
$stm_categories = $_db->query('SELECT * FROM category ORDER BY category_name');
$categories = $stm_categories->fetchAll();

// Fetch up to 10 latest games for each category
$category_games = [];
$stm_cat_games = $_db->prepare('
    SELECT p.*, c.category_name 
    FROM product p 
    JOIN category c ON p.category_id = c.category_id 
    WHERE p.category_id = ? 
    ORDER BY p.product_id DESC 
    LIMIT 10
');

foreach ($categories as $cat) {
    $stm_cat_games->execute([$cat->category_id]);
    $games = $stm_cat_games->fetchAll();
    if (count($games) > 0) {
        $category_games[$cat->category_name] = $games;
    }
}

$_title = 'Welcome to Game Shop';
require '_head.php';
?>

<!-- Featured Carousel -->
<h2 class="section-title">Featured & Recommended</h2>
<div class="carousel-container">
    <button class="carousel-btn prev-btn" id="carousel-prev">&#10094;</button>
    <div class="featured-carousel" id="featured-carousel">
        <?php foreach ($featured_games as $p): ?>
            <a href="/product/detail.php?product_id=<?= $p->product_id ?>" class="featured-item">
                <img src="/photos/<?= $p->photo ?>" class="featured-img" alt="<?= encode($p->name) ?>">
                <div class="featured-details">
                    <h3><?= encode($p->name) ?></h3>
                    <div class="featured-cat"><?= encode($p->category_name) ?></div>
                    <div class="featured-price">RM <?= $p->price ?></div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
    <button class="carousel-btn next-btn" id="carousel-next">&#10095;</button>
</div>

<!-- Categorized Games -->
<?php foreach ($category_games as $category_name => $games): ?>
    <h2 class="section-title mt-20"><?= encode($category_name) ?> Games</h2>
    <div class="scrolling-wrapper">
        <?php foreach ($games as $p): ?>
            <div class="product-card">
                <a href="/product/detail.php?product_id=<?= $p->product_id ?>" style="text-decoration:none;">
                    <img src="/photos/<?= $p->photo ?>" class="product-img">
                    <h3 class="product-title"><?= encode($p->name) ?></h3>
                    <p class="product-price">RM <?= $p->price ?></p>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>

<?php require '_foot.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const carousel = document.getElementById('featured-carousel');
    const items = carousel.querySelectorAll('.featured-item');
    const prevBtn = document.getElementById('carousel-prev');
    const nextBtn = document.getElementById('carousel-next');
    let currentIndex = 0;
    const totalItems = items.length;
    let interval;

    if (totalItems <= 1) {
        prevBtn.style.display = 'none';
        nextBtn.style.display = 'none';
        return;
    }

    function showItem(index) {
        if (index >= totalItems) currentIndex = 0;
        else if (index < 0) currentIndex = totalItems - 1;
        else currentIndex = index;
        
        const offset = -currentIndex * 100;
        carousel.style.transform = `translateX(${offset}%)`;
    }

    function nextItem() {
        showItem(currentIndex + 1);
    }

    function prevItem() {
        showItem(currentIndex - 1);
    }

    nextBtn.addEventListener('click', () => {
        nextItem();
        resetInterval();
    });

    prevBtn.addEventListener('click', () => {
        prevItem();
        resetInterval();
    });

    function startInterval() {
        interval = setInterval(nextItem, 5000);
    }

    function resetInterval() {
        clearInterval(interval);
        startInterval();
    }

    startInterval();
});
</script>
