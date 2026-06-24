<?php
require_once __DIR__ . '/includes/header.php';
$pageTitle = 'My Wishlist — ' . SITE_NAME;
?>
<section class="page-hero"><div class="container">
  <div class="breadcrumb"><a href="<?= url('index.php') ?>">Home</a> / Wishlist</div>
  <span class="eyebrow" style="margin-top:14px">Saved For Later</span>
  <h1><i class="fa-solid fa-heart" style="color:var(--gold)"></i> My Wishlist</h1>
</div></section>

<section class="section" style="padding-top:50px"><div class="container">
  <div id="wishEmpty" class="text-center" style="padding:60px 0;display:none">
    <i class="fa-regular fa-heart" style="font-size:3rem;color:var(--line);margin-bottom:20px"></i>
    <h3>Your wishlist is empty</h3>
    <p class="muted mt-1">Tap the heart on any product to save it here.</p>
    <a href="<?= url('shop.php') ?>" class="btn btn-gold mt-3">Discover Hair</a>
  </div>
  <div class="product-grid" id="wishGrid"></div>
</div></section>

<script>
window.renderWishlist = async function(){
  const ids = window.ombWishlist ? window.ombWishlist() : [];
  const grid = document.getElementById('wishGrid'), empty = document.getElementById('wishEmpty');
  if(!ids.length){ grid.innerHTML=''; empty.style.display='block'; return; }
  empty.style.display='none';
  const r = await fetch(OMB.base+'/api/products.php?action=byids&ids='+ids.join(','));
  const d = await r.json();
  grid.innerHTML = d.products.map(p=>`
    <div class="product-card">
      <div class="pc-media">
        <button class="pc-wish" data-wish="${p.id}" style="opacity:1;transform:none;color:var(--gold)"><i class="fa-solid fa-heart"></i></button>
        <a href="${p.url}"><img class="main" src="${p.image}" alt="${p.name}"></a>
        <div class="pc-quick"><button class="btn btn-gold btn-block" data-add-to-cart="${p.id}" data-qty="1"><i class="fa-solid fa-bag-shopping"></i> Add to Bag</button></div>
      </div>
      <div class="pc-body">
        <div class="pc-cat">${p.category||''}</div>
        <h3 class="pc-title"><a href="${p.url}">${p.name}</a></h3>
        <div class="pc-price">${p.sale?('<span class="was" style="color:var(--muted-2);text-decoration:line-through;margin-right:8px">'+ (OMB.currency==='NGN'?'₦':'$')+p.price+'</span>'):''}${p.price_display}</div>
      </div>
    </div>`).join('');
};
window.addEventListener('load', renderWishlist);
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
