(() => {
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  const revealItems = new Set();
  const parallaxItems = new Set();
  const revealRoot = document.documentElement;
  let animationFrame = 0;

  function revealAll(scope = document) {
    if (scope.matches?.('[data-reveal]')) scope.classList.add('is-visible');
    scope.querySelectorAll?.('[data-reveal]').forEach(element => element.classList.add('is-visible'));
  }

  function revealAnchorTarget() {
    if (!window.location.hash) return;
    let id;
    try {
      id = decodeURIComponent(window.location.hash.slice(1));
    } catch {
      return;
    }
    const target = document.getElementById(id);
    if (target) revealAll(target);
    target?.closest('[data-reveal]')?.classList.add('is-visible');
  }

  if (reducedMotion.matches || !('IntersectionObserver' in window)) {
    revealAll();
    return;
  }

  revealRoot.classList.add('rice-motion-ready');

  const revealObserver = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return;
      entry.target.classList.add('is-visible');
      revealItems.delete(entry.target);
      revealObserver.unobserve(entry.target);
    });
  }, {rootMargin: '0px 0px -7% 0px', threshold: 0.08});

  function observeReveal(element) {
    if (revealItems.has(element) || element.classList.contains('is-visible')) return;
    revealItems.add(element);
    const group = element.closest('[data-reveal-stagger]');
    if (group) {
      const siblings = [...group.querySelectorAll('[data-reveal]')];
      const index = siblings.indexOf(element);
      if (index >= 0) element.style.setProperty('--rice-reveal-delay', `${Math.min(index * 75, 375)}ms`);
    }
    revealObserver.observe(element);
  }

  const parallaxObserver = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (entry.isIntersecting) parallaxItems.add(entry.target);
      else parallaxItems.delete(entry.target);
    });
    scheduleParallax();
  }, {rootMargin: '100px 0px'});

  function updateParallax() {
    animationFrame = 0;
    const viewportHeight = window.innerHeight || 1;
    parallaxItems.forEach(element => {
      const rect = element.getBoundingClientRect();
      const centerOffset = (rect.top + rect.height / 2 - viewportHeight / 2) / (viewportHeight / 2);
      const offset = Math.max(-1, Math.min(1, centerOffset)) * -13;
      element.style.setProperty('--rice-parallax-offset', `${offset.toFixed(1)}px`);
    });
  }

  function scheduleParallax() {
    if (!animationFrame) animationFrame = window.requestAnimationFrame(updateParallax);
  }

  function observeWithin(scope) {
    if (scope.matches?.('[data-reveal]')) observeReveal(scope);
    scope.querySelectorAll?.('[data-reveal]').forEach(observeReveal);
    if (scope.matches?.('[data-parallax]')) parallaxObserver.observe(scope);
    scope.querySelectorAll?.('[data-parallax]').forEach(element => parallaxObserver.observe(element));
  }

  function releaseWithin(scope) {
    const revealTargets = [];
    if (scope.matches?.('[data-reveal]')) revealTargets.push(scope);
    revealTargets.push(...(scope.querySelectorAll?.('[data-reveal]') || []));
    revealTargets.forEach(element => {
      revealItems.delete(element);
      revealObserver.unobserve(element);
    });

    const parallaxTargets = [];
    if (scope.matches?.('[data-parallax]')) parallaxTargets.push(scope);
    parallaxTargets.push(...(scope.querySelectorAll?.('[data-parallax]') || []));
    parallaxTargets.forEach(element => {
      parallaxItems.delete(element);
      parallaxObserver.unobserve(element);
    });
  }

  observeWithin(document);
  revealAnchorTarget();
  window.addEventListener('hashchange', revealAnchorTarget);
  window.addEventListener('scroll', scheduleParallax, {passive: true});
  window.addEventListener('resize', scheduleParallax, {passive: true});
  window.addEventListener('pageshow', revealAnchorTarget);

  const additionsObserver = new MutationObserver(records => {
    records.forEach(record => {
      record.removedNodes.forEach(node => {
        if (node.nodeType === Node.ELEMENT_NODE) releaseWithin(node);
      });
      record.addedNodes.forEach(node => {
        if (node.nodeType === Node.ELEMENT_NODE) observeWithin(node);
      });
    });
  });
  additionsObserver.observe(document.body, {childList: true, subtree: true});
})();
