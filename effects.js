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

