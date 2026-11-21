(() => {
  const overlay = document.querySelector('#rice-preloader');
  const track = document.querySelector('[data-preloader-track]');
  const fill = document.querySelector('[data-preloader-fill]');
  const percentage = document.querySelector('[data-preloader-percentage]');
  const status = document.querySelector('[data-preloader-status]');

  if (!overlay || !track || !fill || !percentage) return;

  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const startedAt = performance.now();
  const minimumDuration = reducedMotion ? 0 : 600;
  const failSafeDuration = reducedMotion ? 0 : 780;
  const finishHold = reducedMotion ? 0 : 170;
  let domReady = document.readyState !== 'loading';
  let pageLoaded = document.readyState === 'complete';
  let progress = 0;
  let completedAt = 0;
  let exiting = false;

  document.documentElement.classList.add('rice-preloader-enabled');

  document.addEventListener('DOMContentLoaded', () => {
    domReady = true;
  }, {once: true});

  window.addEventListener('load', () => {
    pageLoaded = true;
  }, {once: true});

  function setProgress(value) {
    const next = Math.min(100, Math.max(0, Math.round(value)));
    if (next === Number(percentage.textContent.replace('%', ''))) return;
    fill.style.width = `${next}%`;
    percentage.textContent = `${next}%`;
    track.setAttribute('aria-valuenow', String(next));
    if (status) status.textContent = next === 100 ? 'Page ready' : 'Preparing your page';
  }

  function removePreloader() {
    document.documentElement.classList.add('rice-preloader-ready');
    overlay.classList.add('is-exiting');

    const exitDuration = reducedMotion ? 0 : 320;
    window.setTimeout(() => {
      overlay.remove();
      document.documentElement.classList.remove('rice-preloader-enabled', 'rice-preloader-ready');
    }, exitDuration);
  }

  if (reducedMotion) {
    setProgress(100);
    removePreloader();
    return;
  }

  function animate(now) {
    const elapsed = now - startedAt;
    const simulatedProgress = Math.min(72, elapsed / 850 * 72);
    const readinessProgress = pageLoaded ? 96 : domReady ? 82 : 0;
    const finishing = (pageLoaded && elapsed >= minimumDuration) || elapsed >= failSafeDuration;
    const target = finishing ? 100 : Math.max(simulatedProgress, readinessProgress);

    progress += (target - progress) * (finishing ? .42 : .12);
    if (finishing && 100 - progress < .45) {
      progress = 100;
      setProgress(100);
      if (!completedAt) completedAt = now;
      if (now - completedAt >= finishHold) {
        if (!exiting) {
          exiting = true;
          removePreloader();
        }
        return;
      }
    } else {
      setProgress(progress);
    }

    window.requestAnimationFrame(animate);
  }

  setProgress(0);
  window.requestAnimationFrame(animate);
})();
