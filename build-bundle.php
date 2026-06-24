<?php
require_once __DIR__ . '/includes/header.php';
$pageTitle = 'Build Your Bundle — ' . SITE_NAME;
$textures = fetchAll("SELECT DISTINCT texture FROM products WHERE texture IS NOT NULL AND texture<>'' AND category_id=(SELECT id FROM categories WHERE slug='bundles') ORDER BY texture");
?>
<section class="page-hero"><div class="container">
  <div class="breadcrumb"><a href="<?= url('index.php') ?>">Home</a> / Build a Bundle</div>
  <span class="eyebrow" style="margin-top:14px">Your Install, Your Way</span>
  <h1>Build Your Bundle Deal</h1>
  <p class="muted">Choose your texture, pick your bundles &amp; lace — get <strong style="color:var(--gold)">10% off</strong> a complete set (3+ bundles with a closure or frontal).</p>
</div></section>

<section class="section" style="padding-top:46px"><div class="container">
  <!-- Step 1: texture -->
  <div class="reveal" style="margin-bottom:34px">
    <h2 style="font-size:1.5rem;margin-bottom:16px"><span class="text-gold">1.</span> Choose your texture</h2>
    <div class="opt-pills" id="texPills">
      <?php foreach ($textures as $i => $t): ?>
        <span class="opt-pill <?= $i===0?'active':'' ?>" data-tex="<?= e($t['texture']) ?>"><?= e($t['texture']) ?></span>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="stack-mobile" style="display:grid;grid-template-columns:1fr 360px;gap:36px;align-items:start">
    <div id="builder">
      <!-- Step 2: bundles -->
      <div class="reveal" style="margin-bottom:30px">
        <h2 style="font-size:1.5rem;margin-bottom:8px"><span class="text-gold">2.</span> Pick your bundles</h2>
        <p class="muted" style="font-size:.9rem;margin-bottom:14px">We recommend 3 bundles for a full install.</p>
        <div id="bundleRows"></div>
        <button class="btn btn-outline btn-sm" id="addBundle" style="margin-top:12px"><i class="fa-solid fa-plus"></i> Add another bundle</button>
      </div>

      <!-- Step 3: lace -->
      <div class="reveal" style="margin-bottom:30px">
        <h2 style="font-size:1.5rem;margin-bottom:8px"><span class="text-gold">3.</span> Add lace <span class="muted" style="font-size:.9rem;text-transform:none">(optional — needed for the 10% deal)</span></h2>
        <div id="laceOptions" style="display:flex;flex-direction:column;gap:12px"></div>
      </div>
    </div>

    <!-- Summary -->
    <div class="filters" style="position:sticky;top:110px">
      <h3 style="font-size:1.4rem;margin-bottom:16px"><i class="fa-solid fa-crown" style="color:var(--gold)"></i> Your Set</h3>
      <div id="setSummary"><p class="muted">Start adding bundles…</p></div>
      <div id="setTotals" style="margin-top:16px"></div>
      <button class="btn btn-gold btn-block btn-lg" id="addSet" style="margin-top:18px"><i class="fa-solid fa-bag-shopping"></i> Add Set to Bag</button>
      <p class="muted text-center" style="font-size:.76rem;margin-top:12px">Free shipping over <?= money(150) ?></p>
    </div>
  </div>
</div></section>

<script>
(function(){
  const NGN = OMB.currency==='NGN', sym = NGN?'₦':'$';
  const money = v => sym + (NGN?Math.round(v).toLocaleString():Number(v).toFixed(2));
  let data = {bundle:null, closures:[], frontals:[]};
  let rows = [];           // {length}
  let lace = {type:null, product:null, length:null}; // selected lace

  const texPills = document.getElementById('texPills');
  const bundleRows = document.getElementById('bundleRows');
  const laceOptions = document.getElementById('laceOptions');

  function lengthsOf(p){ return (p && p.texture!==undefined && p.lengths)?p.lengths:[]; }

  async function loadTexture(tex){
    const r = await fetch(OMB.base+'/api/products.php?action=bundle&texture='+encodeURIComponent(tex));
    const d = await r.json();
    data.bundle = d.products.find(p=>p.category==='Bundles');
    data.closures = d.products.filter(p=>p.category==='Closures');
    data.frontals = d.products.filter(p=>p.category==='Frontals');
    rows = data.bundle ? [{},{},{}] : [];   // default 3 bundles
    lace = {type:null, product:null, length:null};
    renderBundles(); renderLace(); renderSummary();
  }

  // Lengths come from product.length_inches via API? We expose via product page only.
  // Use a sensible default length list per product if not provided.
  const DEFAULT_LENGTHS = ['14"','16"','18"','20"','22"','24"'];

  function renderBundles(){
    if(!data.bundle){ bundleRows.innerHTML='<p class="muted">No bundles available for this texture yet.</p>'; return; }
    bundleRows.innerHTML = rows.map((row,i)=>`
      <div style="display:flex;gap:10px;align-items:center;padding:12px;border:1px solid var(--line-soft);border-radius:10px;margin-bottom:10px">
        <img src="${data.bundle.image}" style="width:48px;height:58px;object-fit:cover;border-radius:6px">
        <div style="flex:1">
          <div style="font-family:var(--serif)">${data.bundle.name}</div>
          <div style="color:var(--gold);font-size:.86rem">${data.bundle.price_display}</div>
        </div>
        <select data-row="${i}" class="form-control" style="width:90px;padding:8px">
          ${DEFAULT_LENGTHS.map(l=>`<option ${row.length===l?'selected':''}>${l}</option>`).join('')}
        </select>
        <button data-rm="${i}" class="icon-btn" style="color:var(--muted-2)"><i class="fa-solid fa-xmark"></i></button>
      </div>`).join('');
    bundleRows.querySelectorAll('select[data-row]').forEach(s=>s.addEventListener('change',e=>{rows[+e.target.dataset.row].length=e.target.value;renderSummary();}));
    bundleRows.querySelectorAll('[data-rm]').forEach(b=>b.addEventListener('click',()=>{rows.splice(+b.dataset.rm,1);renderBundles();renderSummary();}));
    // ensure defaults
    rows.forEach((r,i)=>{ if(!r.length) r.length = DEFAULT_LENGTHS[2]; });
  }

  function renderLace(){
    const opts = [...data.frontals.map(p=>({...p,kind:'Frontal'})), ...data.closures.map(p=>({...p,kind:'Closure'}))];
    if(!opts.length){ laceOptions.innerHTML='<p class="muted">No matching lace for this texture.</p>'; return; }
    laceOptions.innerHTML = `<div style="display:flex;gap:10px;flex-wrap:wrap">
      <span class="opt-pill ${lace.product===null?'active':''}" data-lace="none">No lace</span>
      ${opts.map(p=>`<span class="opt-pill ${lace.product===p.id?'active':''}" data-lace="${p.id}" data-kind="${p.kind}">${p.kind} · ${p.price_display}</span>`).join('')}
    </div>`;
    laceOptions.querySelectorAll('[data-lace]').forEach(el=>el.addEventListener('click',()=>{
      const id = el.dataset.lace;
      if(id==='none'){ lace={type:null,product:null,length:null}; }
      else { const p=opts.find(o=>o.id==id); lace={type:p.kind,product:p.id,length:DEFAULT_LENGTHS[2],_p:p}; }
      renderLace(); renderSummary();
    }));
  }

  function calc(){
    let items=[], subtotal=0;
    if(data.bundle) rows.forEach(r=>{ items.push({id:data.bundle.id,name:data.bundle.name,variant:r.length||DEFAULT_LENGTHS[2],price:data.bundle.price_cad}); subtotal+=data.bundle.price_cad; });
    if(lace.product && lace._p){ items.push({id:lace._p.id,name:lace._p.name,variant:lace.length,price:lace._p.price_cad}); subtotal+=lace._p.price_cad; }
    const qualifies = rows.length>=3 && !!lace.product;
    const discount = qualifies ? subtotal*0.10 : 0;
    return {items, subtotal, discount, total:subtotal-discount, qualifies};
  }

  function renderSummary(){
    const c = calc();
    const sum = document.getElementById('setSummary');
    if(!c.items.length){ sum.innerHTML='<p class="muted">Start adding bundles…</p>'; document.getElementById('setTotals').innerHTML=''; return; }
    sum.innerHTML = c.items.map(it=>`<div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--line-soft);font-size:.9rem">
        <span>${it.name} <span class="muted">· ${it.variant}</span></span><span style="color:var(--gold)">${money(disp(it.price))}</span></div>`).join('');
    document.getElementById('setTotals').innerHTML = `
      <div style="display:flex;justify-content:space-between;padding:6px 0"><span class="muted">Subtotal</span><span>${money(disp(c.subtotal))}</span></div>
      ${c.discount>0?`<div style="display:flex;justify-content:space-between;padding:6px 0"><span style="color:var(--gold)">Bundle Deal −10%</span><span style="color:var(--gold)">−${money(disp(c.discount))}</span></div>`:''}
      <div style="display:flex;justify-content:space-between;padding:10px 0;border-top:1px solid var(--line);font-size:1.2rem;color:var(--gold);font-family:var(--serif)"><span>Total</span><span>${money(disp(c.total))}</span></div>
      ${c.qualifies?'<div class="badge-pill" style="margin-top:6px"><i class="fa-solid fa-check"></i> 10% deal applied at checkout</div>':'<p class="muted" style="font-size:.78rem;margin-top:6px">Add 3+ bundles &amp; a closure/frontal to unlock 10% off.</p>'}`;
  }

  // price_cad are CAD base; convert to display currency
  const RATES = <?= json_encode(array_map(fn($c)=>$c['rate'],$GLOBALS['CURRENCIES'])) ?>;
  function disp(cad){ return cad * (RATES[OMB.currency]||1); }

  document.getElementById('addBundle').addEventListener('click',()=>{ if(data.bundle){rows.push({length:DEFAULT_LENGTHS[2]});renderBundles();renderSummary();} });
  texPills.querySelectorAll('[data-tex]').forEach(p=>p.addEventListener('click',()=>{
    texPills.querySelectorAll('.opt-pill').forEach(x=>x.classList.remove('active')); p.classList.add('active');
    loadTexture(p.dataset.tex);
  }));

  document.getElementById('addSet').addEventListener('click', async ()=>{
    const c = calc();
    if(!c.items.length){ alert('Please add at least one bundle.'); return; }
    for(const it of c.items){
      await fetch(OMB.base+'/api/cart.php',{method:'POST',headers:{'Content-Type':'application/json'},
        body:JSON.stringify({action:'add',product_id:it.id,qty:1,variant:it.variant})});
    }
    if(c.qualifies){
      await fetch(OMB.base+'/api/cart.php',{method:'POST',headers:{'Content-Type':'application/json'},
        body:JSON.stringify({action:'set_coupon',code:'BUNDLE10'})});
    }
    location.href = OMB.base+'/cart';
  });

  // init
  const first = texPills.querySelector('[data-tex]');
  if(first) loadTexture(first.dataset.tex);
})();
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
