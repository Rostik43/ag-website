/* ═══════════════════════════════════════════════
   АГ Проджект Групп · Visual effects
   1. Lenis smooth scroll
   2. Внутри-картиночный параллакс (Ken Burns)
   3. Marquee — pause-on-hover
   ═══════════════════════════════════════════════ */

(function(){
  /* ── 1. Lenis smooth scroll ── */
  if (window.Lenis){
    const lenis = new window.Lenis({
      duration: 1.15,
      easing: t => 1 - Math.pow(1 - t, 3),
      smoothWheel: true,
      smoothTouch: false,
      wheelMultiplier: 1.0,
    });
    function raf(time){ lenis.raf(time); requestAnimationFrame(raf); }
    requestAnimationFrame(raf);
    window.__lenis = lenis;
  }
})();

(function(){
  /* ── 2. Inner-image parallax ──
     Каждый <img class="parallax-inner"> внутри круга
     слегка панится по Y в зависимости от своего положения в viewport.
     Изображение масштабируется до 1.12, чтобы было место для движения. */

  const items = document.querySelectorAll('.parallax-inner');
  if (!items.length) return;

  // baseline: scale up so we can pan without exposing edges.
  // transition:none — отключаем любые CSS-transition (даже от Tailwind-классов
  // .transition-transform / .duration-700), иначе при скролле картинка
  // плавно интерполируется к каждой новой цели и «отстаёт» от позиции.
  items.forEach(el => {
    el.style.transition = 'none';
    el.style.transform = 'translateY(0) scale(1.12)';
    el.style.willChange = 'transform';
  });

  let ticking = false;
  function update(){
    const vh = window.innerHeight;
    items.forEach(el => {
      const rect = el.getBoundingClientRect();
      // skip if completely out of view
      if (rect.bottom < -50 || rect.top > vh + 50) return;
      // -1 = top of viewport, 0 = centered, 1 = bottom
      const t = (rect.top + rect.height/2 - vh/2) / vh;
      const ty = t * 40; // ±40px max
      // transition:none гарантирует моментальное применение transform
      el.style.transition = 'none';
      el.style.transform = `translateY(${-ty}px) scale(1.12)`;
    });
    ticking = false;
  }
  function onScroll(){
    if (!ticking){
      ticking = true;
      requestAnimationFrame(update);
    }
  }
  window.addEventListener('scroll', onScroll, {passive:true});
  window.addEventListener('resize', onScroll, {passive:true});
  // first paint
  setTimeout(update, 100);
})();

/* ── 4. Уведомление о cookie ──
   Показывается один раз, выбор запоминается в localStorage. */
(function(){
  var KEY = 'agpg_cookie_ok';
  try { if (localStorage.getItem(KEY)) return; } catch(e) { return; }

  var css = document.createElement('style');
  css.textContent =
    '#agCookie{position:fixed;left:16px;right:16px;bottom:16px;z-index:70;background:#000;color:#fff;' +
    'padding:18px 20px;display:flex;flex-wrap:wrap;align-items:center;gap:14px 20px;' +
    'font-family:"Golos Text",sans-serif;font-size:13px;line-height:1.55;max-width:820px;margin:0 auto;' +
    'box-shadow:0 8px 40px rgba(0,0,0,.25);transform:translateY(140%);transition:transform .5s cubic-bezier(.16,1,.3,1)}' +
    '#agCookie.on{transform:translateY(0)}' +
    '#agCookie p{margin:0;flex:1 1 320px;color:#e6e6e2}' +
    '#agCookie a{color:#fff;text-decoration:underline;text-underline-offset:3px}' +
    '#agCookie button{background:#fff;color:#000;border:0;padding:12px 22px;font:600 12px/1 "Golos Text",sans-serif;' +
    'letter-spacing:.14em;text-transform:uppercase;cursor:pointer;white-space:nowrap}' +
    '#agCookie button:hover{background:#c9c9c4}' +
    '@media(max-width:600px){#agCookie{padding:16px}#agCookie button{width:100%}}';
  document.head.appendChild(css);

  var box = document.createElement('div');
  box.id = 'agCookie';
  box.setAttribute('role', 'region');
  box.setAttribute('aria-label', 'Уведомление о файлах cookie');
  box.innerHTML = '<p>Сайт использует файлы cookie и Яндекс.Метрику, чтобы понимать, как им пользуются, и делать его удобнее. ' +
                  'Подробности — в <a href="privacy.html">политике обработки персональных данных</a>.</p>' +
                  '<button type="button">Хорошо</button>';
  document.body.appendChild(box);
  requestAnimationFrame(function(){ requestAnimationFrame(function(){ box.classList.add('on'); }); });

  box.querySelector('button').addEventListener('click', function(){
    try { localStorage.setItem(KEY, '1'); } catch(e) {}
    box.classList.remove('on');
    setTimeout(function(){ box.remove(); }, 500);
  });
})();
