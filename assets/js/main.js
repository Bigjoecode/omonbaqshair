/* Omonblaq's Hair — interactions */
(function () {
  'use strict';
  const $ = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => [...c.querySelectorAll(s)];
  const api = (p) => OMB.base + '/' + p.replace(/^\//, '');

  /* ---- Header scroll state ---- */
  const header = $('#siteHeader');
  const onScroll = () => {
    const scrolled = window.scrollY > 40;
    if (header) header.classList.toggle('solid', scrolled);
    document.body.classList.toggle('scrolled', scrolled);
  };
  onScroll();
  window.addEventListener('scroll', onScroll, { passive: true });

  /* ---- Mobile nav ---- */
  const navMenu = $('#navMenu'), navToggle = $('#navToggle');
  if (navToggle) {
    navToggle.addEventListener('click', () => {
      navMenu.classList.toggle('open');
      navToggle.classList.toggle('active');
    });
  }
  $$('.has-mega > a').forEach(a => {
    a.addEventListener('click', (e) => {
      if (window.innerWidth <= 1100) { e.preventDefault(); a.parentElement.classList.toggle('open-sub'); }
    });
  });

  /* ---- Reveal on scroll ---- */
  const io = new IntersectionObserver((entries) => {
    entries.forEach(en => { if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); } });
  }, { threshold: 0.12 });
  $$('.reveal').forEach(el => io.observe(el));

  /* ---- Gold dust particles in hero ---- */
  const pc = $('.particles');
  if (pc) {
    for (let i = 0; i < 40; i++) {
      const p = document.createElement('span');
      p.className = 'particle';
      const size = Math.random() * 3 + 1;
      p.style.width = p.style.height = size + 'px';
      p.style.left = Math.random() * 100 + '%';
      p.style.top = Math.random() * 100 + '%';
      p.style.animationDuration = (Math.random() * 8 + 6) + 's';
      p.style.animationDelay = (Math.random() * 8) + 's';
      pc.appendChild(p);
    }
  }

  /* ---- Cart drawer ---- */
  const drawer = $('#cartDrawer'), overlay = $('#drawerOverlay');
  const openCart = () => { drawer.classList.add('open'); overlay.classList.add('open'); renderCart(); };
  const closeCart = () => { drawer.classList.remove('open'); overlay.classList.remove('open'); };
  $('#cartToggle') && $('#cartToggle').addEventListener('click', openCart);
  $('#cartClose') && $('#cartClose').addEventListener('click', closeCart);
  overlay && overlay.addEventListener('click', closeCart);

  function money(v) {
    const c = OMB.currency;
    const sym = c === 'NGN' ? '₦' : '$';
    return sym + (c === 'NGN' ? Math.round(v).toLocaleString() : Number(v).toFixed(2));
  }

  async function postCart(action, data) {
    const res = await fetch(api('api/cart.php'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(Object.assign({ action }, data))
    });
    return res.json();
  }

  function renderCart() {
    fetch(api('api/cart.php?action=get')).then(r => r.json()).then(updateCartUI);
  }

  function updateCartUI(d) {
    const count = $('#cartCount'); if (count) count.textContent = d.count;
    const lines = $('#cartLines'), foot = $('#cartFoot');
    if (!lines) return;
    if (!d.lines || !d.lines.length) {
      lines.innerHTML = '<div style="text-align:center;padding:60px 0;color:var(--muted-2)"><i class="fa-solid fa-bag-shopping" style="font-size:2.4rem;color:var(--line);margin-bottom:16px;display:block"></i>Your bag is empty</div>';
      foot.innerHTML = '<a href="' + api('shop') + '" class="btn btn-outline btn-block">Start Shopping</a>';
      return;
    }
    lines.innerHTML = d.lines.map(l => `
      <div class="cart-line" data-key="${l.key}">
        <img src="${l.image}" alt="">
        <div class="info">
          <h5>${l.name}</h5>
          ${l.variant ? `<div class="var">${l.variant}</div>` : ''}
          <div class="qty">
            <button data-act="dec">−</button><span>${l.qty}</span><button data-act="inc">+</button>
          </div>
        </div>
        <div style="text-align:right">
          <div style="color:var(--gold)">${money(l.line_display)}</div>
          <button data-act="rm" style="color:var(--muted-2);font-size:.74rem;margin-top:10px"><i class="fa-solid fa-trash"></i></button>
        </div>
      </div>`).join('');
    foot.innerHTML = `
      <div class="row"><span class="muted">Subtotal</span><span>${money(d.subtotal_display)}</span></div>
      <div class="row total"><span>Total</span><span>${money(d.subtotal_display)}</span></div>
      <a href="${api('checkout')}" class="btn btn-gold btn-block">Secure Checkout</a>
      <a href="${api('cart')}" class="btn btn-ghost btn-block" style="margin-top:10px">View Bag</a>`;
    bindLineEvents(d);
  }

  function bindLineEvents() {
    $$('#cartLines .cart-line').forEach(line => {
      const key = line.dataset.key;
      line.querySelectorAll('[data-act]').forEach(btn => {
        btn.addEventListener('click', async () => {
          const act = btn.dataset.act;
          let d;
          if (act === 'rm') d = await postCart('remove', { key });
          else d = await postCart(act === 'inc' ? 'inc' : 'dec', { key });
          updateCartUI(d);
        });
      });
    });
  }

  /* ---- Add to cart (delegated) ---- */
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-add-to-cart]');
    if (!btn) return;
    e.preventDefault();
    const id = btn.dataset.addToCart;
    const qty = parseInt(btn.dataset.qty || '1', 10);
    const variant = btn.dataset.variant || (window.__selectedVariant || null);
    btn.classList.add('loading');
    const original = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-check"></i> Added';
    const d = await postCart('add', { product_id: id, qty, variant });
    updateCartUI(d);
    openCart();
    setTimeout(() => { btn.innerHTML = original; btn.classList.remove('loading'); }, 1400);
  });

  // initial count sync
  renderCart();

  /* ---- AI Stylist chat ---- */
  const chatPanel = $('#chatPanel');
  const toggleChat = (open) => chatPanel.classList.toggle('open', open);
  $('#chatToggle') && $('#chatToggle').addEventListener('click', () => toggleChat(!chatPanel.classList.contains('open')));
  $('#chatClose') && $('#chatClose').addEventListener('click', () => toggleChat(false));

  const chatBody = $('#chatBody'), chatForm = $('#chatForm'), chatText = $('#chatText');
  function addMsg(text, who) {
    const m = document.createElement('div');
    m.className = 'chat-msg ' + who;
    m.innerHTML = text;
    chatBody.appendChild(m);
    chatBody.scrollTop = chatBody.scrollHeight;
    return m;
  }
  async function sendChat(text) {
    addMsg(text.replace(/</g, '&lt;'), 'user');
    const typing = addMsg('<span class="typing"><span></span><span></span><span></span></span>', 'bot');
    try {
      const res = await fetch(api('api/chatbot.php'), {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: text })
      });
      const data = await res.json();
      typing.innerHTML = data.reply || "I'm here to help you find the perfect hair! Could you tell me a little more?";
      if (data.products && data.products.length) renderChatProducts(data.products);
    } catch (err) {
      typing.innerHTML = "I'm having a little trouble right now — please reach us on WhatsApp and we'll help you personally 💛";
    }
  }
  function renderChatProducts(products) {
    const wrap = document.createElement('div');
    wrap.className = 'chat-msg bot';
    wrap.style.maxWidth = '92%';
    wrap.style.padding = '8px';
    wrap.innerHTML = products.map(p => `
      <div class="chat-prod">
        <img src="${p.image}" alt="">
        <div class="cp-info">
          <a href="${p.url}" class="cp-name">${p.name}</a>
          <span class="cp-price">${p.price}</span>
        </div>
        ${p.in_stock
          ? `<button class="cp-add" data-add-to-cart="${p.id}" data-qty="1" title="Add to bag"><i class="fa-solid fa-bag-shopping"></i></button>`
          : `<span class="cp-out">Sold out</span>`}
      </div>`).join('');
    chatBody.appendChild(wrap);
    chatBody.scrollTop = chatBody.scrollHeight;
  }

  chatForm && chatForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const t = chatText.value.trim();
    if (!t) return;
    chatText.value = '';
    sendChat(t);
  });
  $$('.chat-chip').forEach(chip => chip.addEventListener('click', () => { toggleChat(true); sendChat(chip.dataset.q); }));

  /* ---- Wishlist (localStorage) ---- */
  const WKEY = 'omb_wishlist';
  const getWish = () => { try { return JSON.parse(localStorage.getItem(WKEY)) || []; } catch (e) { return []; } };
  const setWish = (a) => localStorage.setItem(WKEY, JSON.stringify([...new Set(a)]));
  window.ombWishlist = getWish;
  function updateWishCount() {
    const c = getWish().length, el = $('#wishCount');
    if (el) { el.textContent = c; el.style.display = c ? 'grid' : 'none'; }
  }
  function paintHearts() {
    const w = getWish();
    $$('[data-wish]').forEach(b => {
      const on = w.includes(parseInt(b.dataset.wish, 10));
      const ic = b.querySelector('i');
      if (ic) { ic.className = on ? 'fa-solid fa-heart' : 'fa-regular fa-heart'; }
      b.style.color = on ? 'var(--gold)' : '';
    });
  }
  document.addEventListener('click', (e) => {
    const b = e.target.closest('[data-wish]');
    if (!b) return;
    e.preventDefault();
    const id = parseInt(b.dataset.wish, 10);
    let w = getWish();
    w = w.includes(id) ? w.filter(x => x !== id) : [...w, id];
    setWish(w);
    paintHearts(); updateWishCount();
    if (typeof window.renderWishlist === 'function') window.renderWishlist();
  });
  updateWishCount(); paintHearts();

  /* ---- Product gallery thumbs ---- */
  $$('.pd-thumbs img').forEach(t => t.addEventListener('click', () => {
    $('.pd-gallery .main-img img').src = t.dataset.full || t.src;
    $$('.pd-thumbs img').forEach(x => x.classList.remove('active'));
    t.classList.add('active');
  }));

  /* ---- Option pills ---- */
  $$('.opt-row').forEach(row => {
    row.querySelectorAll('.opt-pill').forEach(pill => {
      pill.addEventListener('click', () => {
        row.querySelectorAll('.opt-pill').forEach(p => p.classList.remove('active'));
        pill.classList.add('active');
        const vals = $$('.opt-row .opt-pill.active').map(p => p.textContent.trim());
        window.__selectedVariant = vals.join(' · ');
        const addBtn = $('[data-add-to-cart]');
        if (addBtn) addBtn.dataset.variant = window.__selectedVariant;
      });
    });
  });
})();
