<?php
/** Expects $product (array) in scope. Optional $reveal class string. */
$imgs = product_images((int)$product['id']);
$main = $imgs[0];
$alt  = $imgs[1] ?? $imgs[0];
$cat  = $product['category_name'] ?? '';
$onSale = !empty($product['sale_price']) && $product['sale_price'] > 0;
?>
<div class="product-card <?= $reveal ?? '' ?>">
  <div class="pc-media">
    <?php if (!empty($product['badge'])): ?>
      <span class="pc-badge <?= $onSale ? 'sale' : '' ?>"><?= e($product['badge']) ?></span>
    <?php endif; ?>
    <button class="pc-wish" aria-label="Wishlist" data-wish="<?= (int)$product['id'] ?>"><i class="fa-regular fa-heart"></i></button>
    <a href="<?= e(product_url($product)) ?>">
      <img class="main" src="<?= e($main) ?>" alt="<?= e($product['name']) ?>" loading="lazy" decoding="async">
      <img class="alt" src="<?= e($alt) ?>" alt="" loading="lazy" decoding="async">
    </a>
    <div class="pc-quick">
      <?php if ((int)$product['stock'] > 0): ?>
        <button class="btn btn-gold btn-block" data-add-to-cart="<?= (int)$product['id'] ?>" data-qty="1">
          <i class="fa-solid fa-bag-shopping"></i> Add to Bag
        </button>
      <?php else: ?>
        <a class="btn btn-ghost btn-block" href="<?= e(product_url($product)) ?>" style="opacity:.85">Sold Out — Notify Me</a>
      <?php endif; ?>
    </div>
  </div>
  <div class="pc-body">
    <?php if ($cat): ?><div class="pc-cat"><?= e($cat) ?></div><?php endif; ?>
    <h3 class="pc-title"><a href="<?= e(product_url($product)) ?>"><?= e($product['name']) ?></a></h3>
    <div class="pc-stars"><?= str_repeat('★', 5) ?><small>(<?= (int)$product['review_count'] ?>)</small></div>
    <div class="pc-price">
      <?php if ($onSale): ?>
        <span class="was"><?= money((float)$product['price']) ?></span><?= money((float)$product['sale_price']) ?>
      <?php else: ?>
        <?= money((float)$product['price']) ?>
      <?php endif; ?>
    </div>
  </div>
</div>
