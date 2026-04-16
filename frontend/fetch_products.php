<?php
session_start();
include("../backend/config/db.php");

header('Content-Type: application/json');

 $cat_id = isset($_GET['cat_id']) ? intval($_GET['cat_id']) : 0;

if ($cat_id > 0) {
    $query = "
    SELECT p.*, c.category_name,
    IFNULL(AVG(r.rating), 0) as avg_rating,
    COUNT(r.id) as total_reviews
    FROM products p
    JOIN categories c ON p.category_id = c.id
    LEFT JOIN reviews r ON p.id = r.product_id
    WHERE p.category_id = $cat_id
    GROUP BY p.id
    ORDER BY p.id DESC";

    $cat_res = mysqli_query($conn, "SELECT category_name FROM categories WHERE id = $cat_id");
    $cat_row = mysqli_fetch_assoc($cat_res);
    $cat_name = $cat_row ? $cat_row['category_name'] : 'Products';
} else {
    $query = "
    SELECT p.*, c.category_name,
    IFNULL(AVG(r.rating), 0) as avg_rating,
    COUNT(r.id) as total_reviews
    FROM products p
    JOIN categories c ON p.category_id = c.id
    LEFT JOIN reviews r ON p.id = r.product_id
    GROUP BY p.id
    ORDER BY p.id DESC";

    $cat_name = 'All Products';
}

 $result = mysqli_query($conn, $query);
 $count = mysqli_num_rows($result);

if ($count === 0) {
    echo json_encode([
        'html'  => 'EMPTY',
        'name'  => $cat_name,
        'count' => 0
    ]);
    exit;
}

 $delay = 0;
 $html = '';

while ($product = mysqli_fetch_assoc($result)) {
    $delay += 0.06;
    $imgPath = "../backend/uploads/" . $product['image'];
    $stars = '';
    $avg = round($product['avg_rating'], 1);

    for ($i = 1; $i <= 5; $i++) {
        if ($i <= floor($avg)) {
            $stars .= '<i class="fa-solid fa-star text-gold-400 text-[9px]"></i>';
        } elseif ($i - 0.5 <= $avg) {
            $stars .= '<i class="fa-solid fa-star-half-stroke text-gold-400 text-[9px]"></i>';
        } else {
            $stars .= '<i class="fa-regular fa-star text-white/10 text-[9px]"></i>';
        }
    }

    $html .= '
    <div class="prod-card fade-up" style="animation-delay:' . $delay . 's">

        <a href="product_detail.php?id=' . $product['id'] . '" class="prod-img-wrap block">
            <img
                src="' . $imgPath . '"
                alt="' . htmlspecialchars($product['product_name']) . '"
                onerror="this.src=\'https://picsum.photos/seed/' . $product['id'] . '/400/300.jpg\'"
            >
            <span class="cat-badge">
                <i class="fa-solid fa-folder text-[8px] mr-1"></i>' . htmlspecialchars($product['category_name']) . '
            </span>
        </a>

        <div class="p-5 relative z-10">

            <h3 class="text-sm font-bold text-white leading-snug">' . htmlspecialchars($product['product_name']) . '</h3>

            <p class="text-xs text-white/30 mt-1.5 leading-relaxed line-clamp-2">
                ' . htmlspecialchars($product['description'] ?? 'Premium quality product with exceptional performance.') . '
            </p>';

    if ($product['total_reviews'] > 0) {
        $html .= '
            <div class="flex items-center gap-1.5 mt-2.5">
                <div class="flex items-center gap-0.5">' . $stars . '</div>
                <span class="text-[10px] text-white/25 font-medium">' . $avg . ' (' . $product['total_reviews'] . ')</span>
            </div>';
    }

    $html .= '
            <div class="flex items-end justify-between mt-4 pt-4 border-t border-white/5">

                <div>
                    <p class="text-[10px] text-white/25 uppercase tracking-wider font-medium">Price</p>
                    <p class="text-xl font-extrabold text-gold-400 mt-0.5">Rs. ' . number_format($product['price'], 0) . '</p>
                </div>

                <div class="flex items-center gap-2">
                    <a href="product_detail.php?id=' . $product['id'] . '" class="btn-view w-10 h-10 flex items-center justify-center" title="View Details">
                        <i class="fa-solid fa-eye text-xs"></i>
                    </a>
                    <a href="add_to_cart.php?id=' . $product['id'] . '" class="btn-cart w-10 h-10 flex items-center justify-center" title="Add to Cart">
                        <i class="fa-solid fa-bag-shopping text-xs"></i>
                    </a>
                </div>

            </div>

        </div>

    </div>';
}

echo json_encode([
    'html'  => $html,
    'name'  => $cat_name,
    'count' => $count
]);