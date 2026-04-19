<?php
include("../backend/config/db.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

$cat_id = isset($_GET['cat_id']) ? intval($_GET['cat_id']) : 0;

/* ================= QUERY ================= */
if ($cat_id > 0) {
    $sql = "
    SELECT p.*, c.category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.category_id = $cat_id
    ORDER BY p.id DESC
    ";
} else {
    $sql = "
    SELECT p.*, c.category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    ORDER BY p.id DESC
    ";
}

$result = mysqli_query($conn, $sql);

if (!$result) {
    echo json_encode([
        "html" => "EMPTY",
        "count" => 0,
        "name" => "Error",
        "error" => mysqli_error($conn)
    ]);
    exit;
}

/* ================= HTML BUILD ================= */
$html = "";
$count = mysqli_num_rows($result);

while ($p = mysqli_fetch_assoc($result)) {

    $img = "../backend/uploads/" . $p['image'];

    $html .= "
    <div class='prod-card fade-up'>

        <a href='product_detail.php?id={$p['id']}' class='prod-img-wrap block'>
            <img src='{$img}' onerror=\"this.src='https://picsum.photos/400/300?random={$p['id']}'\">
            <span class='cat-badge'>{$p['category_name']}</span>
        </a>

        <div class='p-5'>

            <h3 class='text-sm font-bold text-white'>{$p['product_name']}</h3>

            <p class='text-xs text-white/30 mt-1'>
                {$p['description']}
            </p>

            <div class='flex items-end justify-between mt-4 pt-4 border-t border-white/5'>

                <div>
                    <p class='text-[10px] text-white/25'>Price</p>
                    <p class='text-xl font-extrabold text-gold-400'>Rs. {$p['price']}</p>
                </div>

                <div class='flex gap-2'>
                    <a href='product_detail.php?id={$p['id']}' class='btn-view w-10 h-10 flex items-center justify-center'>
                        <i class='fa-solid fa-eye text-xs'></i>
                    </a>

                    <button data-add-cart='{$p['id']}' class='btn-cart w-10 h-10 flex items-center justify-center'>
                        <i class='fa-solid fa-bag-shopping text-xs'></i>
                    </button>
                </div>

            </div>

        </div>
    </div>
    ";
}

if ($count == 0) {
    $html = "EMPTY";
}

/* ================= RESPONSE ================= */
echo json_encode([
    "html" => $html,
    "count" => $count,
    "name" => $cat_id > 0 ? "Filtered Products" : "All Products"
]);
exit;
?>