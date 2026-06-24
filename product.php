<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/seo.php';

$slug = $_GET['slug'] ?? '';
$product = fetch(
    "SELECT p.*, c.name AS category_name, c.slug AS category_slug
     FROM products p LEFT JOIN categories c ON c.id = p.category_id
     WHERE p.slug = ? AND p.is_active = 1",
    [$slug]
);

if (!$product) {
    http_response_code(404);
    $pageTitle = 'Product not found — ' . SITE_NAME;
    $noindex = true;
    require __DIR__ . '/includes/header.php';
    echo '<section class="page-hero"><div class="container"><h1>Product not found</h1><a href="' . url('shop.php') . '" class="btn btn-outline mt-3">Back to Shop</a></div></section>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$pid    = (int)$product['id'];
$images = product_images($pid);
$onSale = !empty($product['sale_price']) && $product['sale_price'] > 0;
$lengths = array_filter(array_map('trim', explode(',', $product['length_inches'] ?? '')));
$reviews = fetchAll('SELECT * FROM reviews WHERE product_id = ? AND is_approved = 1 ORDER BY id DESC', [$pid]);
$related = fetchAll(
    "SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id
     WHERE p.category_id = ? AND p.id <> ? AND p.is_active = 1 ORDER BY RAND() LIMIT 4",
    [$product['category_id'], $pid]
);

$pageTitle = $product['name'] . ' — ' . SITE_NAME;
$pageDesc  = $product['short_desc'] ?: ('Shop ' . $product['name'] . ' — premium ' . ($product['category_name'] ?? 'hair') . ' at ' . SITE_NAME . '. Worldwide delivery.');
$pageImage = $images[0] ?? asset('img/hero-main.jpg');
$ogType    = 'product';

$crumbs = [['name' => 'Home', 'url' => url('index.php')]];
if (!empty($product['category_name'])) {
    $crumbs[] = ['name' => $product['category_name'], 'url' => url('shop.php?category=' . urlencode($product['category_slug'] ?? ''))];
}
$crumbs[] = ['name' => $product['name'], 'url' => product_url($product)];
$jsonLd = [ld_breadcrumb($crumbs), ld_product($product, $images)];

require __DIR__ . '/includes/header.php';
?>

<section class="page-hero" style="padding-bottom:30px">
  <div class="container">
    <div class="breadcrumb">
      <a href="<?= url('index.php') ?>">Home</a> /
      <a href="<?= url('shop.php?category=' . urlencode($product['category_slug'] ?? '')) ?>"><?= e($product['category_name']) ?></a> /
      <?= e($product['name']) ?>
    </div>
  </div>
</section>

<section class="section" style="padding-top:20px">
  <div class="container pd-grid">
    <!-- Gallery -->
    <div class="pd-gallery reveal">
      <div class="main-img"><img src="<?= e($images[0]) ?>" alt="<?= e($product['name']) ?>" id="pdMain"></div>
      <?php if (count($images) > 1): ?>
      <div class="pd-thumbs">
        <?php foreach ($images as $i => $img): ?>
          <img src="<?= e($img) ?>" data-full="<?= e($img) ?>" class="<?= $i === 0 ? 'active' : '' ?>" alt="View <?= $i + 1 ?>">
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Info -->
    <div class="pd-info reveal d1">
      <span class="eyebrow"><?= e($product['category_name']) ?></span>
      <h1><?= e($product['name']) ?></h1>
      <div class="pc-stars" style="font-size:1rem">
        <?= str_repeat('★', 5) ?>
        <small style="color:var(--muted-2);margin-left:8px"><?= number_format($product['rating'], 1) ?> · <?= (int)$product['review_count'] ?> reviews</small>
      </div>

      <div class="pd-price">
        <?php if ($onSale): ?>
          <span class="was"><?= money((float)$product['price']) ?></span><?= money((float)$product['sale_price']) ?>
          <span class="badge-pill" style="margin-left:10px;border-color:var(--gold);color:var(--gold)">Save <?= money((float)$product['price'] - (float)$product['sale_price']) ?></span>
        <?php else: ?>
          <?= money((float)$product['price']) ?>
        <?php endif; ?>
      </div>

      <p class="muted"><?= e($product['short_desc']) ?></p>

      <div class="pd-options">
        <?php if ($lengths): ?>
        <div class="opt-row">
          <label>Length <span class="muted" style="text-transform:none;letter-spacing:0">(inches)</span></label>
          <div class="opt-pills">
            <?php foreach ($lengths as $i => $len): ?>
              <span class="opt-pill <?= $i === 0 ? 'active' : '' ?>"><?= e($len) ?></span>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
        <div class="opt-row">
          <label>Quantity</label>
          <div class="qty" style="border-color:var(--gold)">
            <button type="button" id="qtyDec">−</button><span id="qtyVal">1</span><button type="button" id="qtyInc">+</button>
          </div>
        </div>
      </div>

      <?php $inStock = (int)$product['stock'] > 0; ?>
      <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center">
        <?php if ($inStock): ?>
          <button class="btn btn-gold btn-lg" data-add-to-cart="<?= $pid ?>" data-qty="1" id="pdAdd">
            <i class="fa-solid fa-bag-shopping"></i> Add to Bag
          </button>
        <?php else: ?>
          <button class="btn btn-outline btn-lg" id="notifyBtn"><i class="fa-regular fa-bell"></i> Notify When Back</button>
        <?php endif; ?>
        <a class="btn btn-ghost btn-lg" href="https://wa.me/<?= e(SITE_WHATSAPP) ?>?text=<?= urlencode('Hi, I\'m interested in: ' . $product['name']) ?>" target="_blank" rel="noopener">
          <i class="fa-brands fa-whatsapp"></i> Order on WhatsApp
        </a>
        <button class="icon-btn" data-wish="<?= $pid ?>" title="Save to wishlist" style="font-size:1.4rem"><i class="fa-regular fa-heart"></i></button>
      </div>

      <?php if (!$inStock): ?>
      <form id="notifyForm" style="display:none;margin-top:16px;gap:8px;max-width:420px" onsubmit="return false">
        <div style="display:flex;gap:8px">
          <input class="form-control" type="email" id="notifyEmail" placeholder="Your email" required>
          <button class="btn btn-gold" id="notifySubmit" style="white-space:nowrap">Notify Me</button>
        </div>
        <div id="notifyMsg" class="muted" style="font-size:.82rem;margin-top:8px"></div>
      </form>
      <script>
        document.getElementById('notifyBtn').addEventListener('click',()=>{document.getElementById('notifyForm').style.display='block';document.getElementById('notifyEmail').focus();});
        document.getElementById('notifySubmit').addEventListener('click',async()=>{
          const em=document.getElementById('notifyEmail').value.trim(); const msg=document.getElementById('notifyMsg');
          if(!em.includes('@')){msg.textContent='Please enter a valid email.';return;}
          const r=await fetch(OMB.base+'/api/stock-alert.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({product_id:<?= $pid ?>,email:em})});
          const d=await r.json(); msg.innerHTML='<span style="color:var(--gold)">'+(d.message||'You\'re on the list!')+'</span>';
        });
      </script>
      <?php endif; ?>

      <div class="pd-meta">
        <?php
        $meta = [
            'Origin'   => $product['origin'],
            'Texture'  => $product['texture'],
            'Lace'     => $product['lace_type'],
            'Density'  => $product['density'],
            'Colour'   => $product['color_shade'],
            'SKU'      => $product['sku'],
        ];
        foreach ($meta as $label => $val): if (!$val) continue; ?>
          <div><strong><?= e($label) ?></strong> <?= e($val) ?></div>
        <?php endforeach; ?>
        <div style="margin-top:14px;display:flex;gap:20px;flex-wrap:wrap;color:var(--gold)">
          <span><i class="fa-solid fa-truck-fast"></i> Fast worldwide shipping</span>
          <span><i class="fa-solid fa-lock"></i> Secure checkout</span>
          <span><i class="fa-solid fa-rotate-left"></i> Easy returns</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Length Visualizer + Virtual Try-On -->
<?php $vizLengths = $lengths ?: ['10"','12"','14"','16"','18"','20"','22"','24"','26"']; ?>
<section class="section" style="padding-top:0">
  <div class="container">
    <div class="filters reveal" style="padding:36px">
      <div class="stack-mobile" style="display:grid;grid-template-columns:auto 1fr;gap:40px;align-items:center">
        <!-- Figure -->
        <div style="text-align:center">
          <div id="lenFigure" style="position:relative;width:160px;height:300px;margin:0 auto">
            <svg viewBox="0 0 160 300" width="160" height="300" style="position:relative;z-index:1">
              <defs><linearGradient id="hairG" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0" stop-color="#f6e6a4"/><stop offset="1" stop-color="#a9842a"/></linearGradient></defs>
              <!-- body silhouette -->
              <g fill="#2a2620">
                <circle cx="80" cy="38" r="26"/>
                <path d="M58 60 Q80 74 102 60 L116 92 Q120 104 110 110 L104 104 L108 150 Q110 200 100 250 L92 296 L70 296 Q66 250 60 200 L52 150 L56 104 L50 110 Q40 104 44 92 Z"/>
              </g>
              <!-- hair overlay (height scales with length) -->
              <rect id="hairBar" x="52" y="20" width="56" height="40" rx="26" fill="url(#hairG)" opacity="0.92"/>
            </svg>
          </div>
          <div style="font-size:.72rem;letter-spacing:.16em;text-transform:uppercase;color:var(--muted-2);margin-top:8px">Approx. on a 5'6" frame</div>
        </div>
        <!-- Controls -->
        <div>
          <span class="eyebrow">Length &amp; Try-On</span>
          <h2 style="font-size:1.8rem;margin:8px 0 6px">See Your Length</h2>
          <p class="muted" style="margin-bottom:16px">Tap a length to see roughly where it falls — then preview it on your own photo.</p>
          <div class="opt-pills" id="lenViz">
            <?php foreach ($vizLengths as $i => $len): ?>
              <span class="opt-pill <?= $i===min(3,count($vizLengths)-1)?'active':'' ?>" data-len="<?= e(preg_replace('/[^0-9]/','',$len)) ?>"><?= e($len) ?></span>
            <?php endforeach; ?>
          </div>
          <div id="lenLabel" style="margin-top:16px;font-family:var(--serif);font-size:1.3rem;color:var(--gold)"></div>
          <button class="btn btn-gold mt-3" id="tryOnBtn"><i class="fa-solid fa-camera"></i> Try It On Virtually</button>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Try-On Modal -->
<div class="drawer-overlay" id="tryOverlay"></div>
<div id="tryModal" style="position:fixed;inset:0;z-index:420;display:none;place-items:center;padding:20px">
  <div style="background:var(--noir);border:1px solid var(--line);border-radius:16px;max-width:560px;width:100%;max-height:92vh;overflow:auto;box-shadow:var(--shadow)">
    <div style="display:flex;justify-content:space-between;align-items:center;padding:20px 24px;border-bottom:1px solid var(--line-soft)">
      <h3 style="font-size:1.4rem"><i class="fa-solid fa-wand-magic-sparkles" style="color:var(--gold)"></i> Virtual Try-On</h3>
      <button class="icon-btn" id="tryClose"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div style="padding:24px">
      <p class="muted" style="font-size:.86rem;margin-bottom:14px">Upload a front-facing selfie, then position the hair over your head. Everything stays on your device — nothing is uploaded.</p>
      <input type="file" id="tryFile" accept="image/*" class="form-control" style="margin-bottom:14px">
      <div id="tryCanvasWrap" style="position:relative;display:none;background:#000;border-radius:12px;overflow:hidden;text-align:center">
        <canvas id="tryCanvas" style="max-width:100%"></canvas>
      </div>
      <div id="tryControls" style="display:none;margin-top:16px">
        <label class="muted" style="font-size:.78rem">Size</label>
        <input type="range" id="trySize" min="20" max="160" value="80" style="width:100%">
        <label class="muted" style="font-size:.78rem">Vertical position</label>
        <input type="range" id="tryY" min="-50" max="100" value="0" style="width:100%">
        <label class="muted" style="font-size:.78rem">Horizontal position</label>
        <input type="range" id="tryX" min="-50" max="50" value="0" style="width:100%">
        <label class="muted" style="font-size:.78rem">Opacity</label>
        <input type="range" id="tryOpacity" min="30" max="100" value="100" style="width:100%">
        <div style="display:flex;gap:10px;margin-top:14px">
          <button class="btn btn-gold" id="tryDownload" style="flex:1;justify-content:center"><i class="fa-solid fa-download"></i> Save Image</button>
          <a class="btn btn-outline" href="https://wa.me/<?= e(SITE_WHATSAPP) ?>" target="_blank" rel="noopener" style="justify-content:center">Ask a Stylist</a>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  // ---- Length visualizer ----
  const bar = document.getElementById('hairBar'), label = document.getElementById('lenLabel');
  const TOP=20, MAX_Y=292; // svg coords
  const landmark = inch => inch<=10?'Chin-grazing bob':inch<=12?'Chin length':inch<=14?'Shoulder length':inch<=16?'Collarbone':inch<=18?'Armpit length':inch<=20?'Bust length':inch<=22?'Mid-back':inch<=24?'Waist length':inch<=26?'Below waist':'Hip length';
  function setLen(inch){
    const pct = Math.min(1, Math.max(0,(inch-8)/(30-8)));
    const h = 40 + pct*(MAX_Y-TOP-40);
    bar.setAttribute('height', h.toFixed(0));
    label.textContent = inch + '" — ' + landmark(inch);
  }
  document.querySelectorAll('#lenViz .opt-pill').forEach(p=>p.addEventListener('click',()=>{
    document.querySelectorAll('#lenViz .opt-pill').forEach(x=>x.classList.remove('active'));
    p.classList.add('active'); setLen(parseInt(p.dataset.len,10));
  }));
  const act = document.querySelector('#lenViz .opt-pill.active'); if(act) setLen(parseInt(act.dataset.len,10));

  // ---- Virtual try-on ----
  const overlay=document.getElementById('tryOverlay'), modal=document.getElementById('tryModal');
  const open=()=>{overlay.classList.add('open');modal.style.display='grid';};
  const close=()=>{overlay.classList.remove('open');modal.style.display='none';};
  document.getElementById('tryOnBtn').addEventListener('click',open);
  document.getElementById('tryClose').addEventListener('click',close);
  overlay.addEventListener('click',close);

  const canvas=document.getElementById('tryCanvas'), ctx=canvas.getContext('2d');
  const hair=new Image(); hair.crossOrigin='anonymous'; hair.src='<?= e($images[0]) ?>';
  let selfie=null;
  const ctl=id=>document.getElementById(id);
  document.getElementById('tryFile').addEventListener('change',e=>{
    const f=e.target.files[0]; if(!f) return;
    const img=new Image(); img.onload=()=>{ selfie=img;
      const w=Math.min(480,img.width); const scale=w/img.width;
      canvas.width=w; canvas.height=img.height*scale;
      document.getElementById('tryCanvasWrap').style.display='block';
      document.getElementById('tryControls').style.display='block';
      draw();
    }; img.src=URL.createObjectURL(f);
  });
  ['trySize','tryY','tryX','tryOpacity'].forEach(id=>ctl(id).addEventListener('input',draw));
  function draw(){
    if(!selfie) return;
    ctx.clearRect(0,0,canvas.width,canvas.height);
    ctx.drawImage(selfie,0,0,canvas.width,canvas.height);
    if(hair.complete && hair.naturalWidth){
      const sizePct=+ctl('trySize').value/100;
      const hw=canvas.width*sizePct, hh=hw*(hair.naturalHeight/hair.naturalWidth);
      const x=(canvas.width-hw)/2 + (+ctl('tryX').value/100*canvas.width);
      const y=(+ctl('tryY').value/100*canvas.height);
      ctx.globalAlpha=+ctl('tryOpacity').value/100;
      ctx.drawImage(hair,x,y,hw,hh);
      ctx.globalAlpha=1;
    }
  }
  document.getElementById('tryDownload').addEventListener('click',()=>{
    const a=document.createElement('a'); a.download='omonblaq-tryon.png'; a.href=canvas.toDataURL('image/png'); a.click();
  });
})();
</script>

<!-- Description + Reviews -->
<section class="section" style="background:var(--noir);padding-top:60px">
  <div class="container" style="max-width:900px">
    <div class="reveal">
      <h2 style="font-size:2rem;margin-bottom:18px">The Details</h2>
      <div class="divider-gold" style="margin-left:0"></div>
      <p class="muted" style="font-size:1.05rem"><?= nl2br(e($product['description'])) ?></p>
    </div>

    <div class="reveal" style="margin-top:60px">
      <h2 style="font-size:2rem;margin-bottom:18px">Reviews (<?= count($reviews) ?>)</h2>
      <div class="divider-gold" style="margin-left:0"></div>
      <?php foreach ($reviews as $r): ?>
        <div style="padding:22px 0;border-bottom:1px solid var(--line-soft)">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
            <strong style="font-family:var(--serif);font-size:1.15rem"><?= e($r['author']) ?></strong>
            <span style="color:var(--gold)"><?= str_repeat('★', (int)$r['rating']) ?></span>
          </div>
          <?php if ($r['title']): ?><div style="color:var(--cream);margin-bottom:4px"><?= e($r['title']) ?></div><?php endif; ?>
          <p class="muted"><?= e($r['body']) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Related -->
<?php if ($related): ?>
<section class="section">
  <div class="container">
    <div class="section-head reveal">
      <span class="eyebrow">You May Also Love</span>
      <h2>Complete The Look</h2>
      <div class="divider-gold"></div>
    </div>
    <div class="product-grid">
      <?php foreach ($related as $i => $product):
        $reveal = 'reveal d' . (($i % 4) + 1);
        include __DIR__ . '/includes/product-card.php';
      endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<script>
(function(){
  let q=1; const val=document.getElementById('qtyVal'), add=document.getElementById('pdAdd');
  document.getElementById('qtyInc').onclick=()=>{q++;val.textContent=q;add.dataset.qty=q;};
  document.getElementById('qtyDec').onclick=()=>{if(q>1){q--;val.textContent=q;add.dataset.qty=q;}};
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
