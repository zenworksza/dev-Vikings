// Fits the rune line AND the hover-swap Latin label to the same rendered
// width (90% of the logo's width) — not the same font-size number, since
// the two typefaces have different metrics and equal font-size doesn't
// look equal size across them.
var RUNE_WIDTH_RATIO = 0.9;

(function () {
  function fitToWidth(el, targetWidth) {
    if (!el || targetWidth <= 0) return;
    el.style.fontSize = "";
    var baseSize = parseFloat(getComputedStyle(el).fontSize);
    var naturalWidth = el.getBoundingClientRect().width;
    if (naturalWidth > 0) {
      el.style.fontSize = (baseSize * (targetWidth / naturalWidth)) + "px";
    }
  }

  function fitRunesToLogo() {
    var logo = document.querySelector(".hero__logo");
    var runes = document.querySelector(".rune-wordmark__runes");
    var latin = document.querySelector(".rune-wordmark__latin");
    if (!logo) return;

    var logoWidth = logo.getBoundingClientRect().width;
    var targetWidth = logoWidth * RUNE_WIDTH_RATIO;

    fitToWidth(runes, targetWidth);
    fitToWidth(latin, targetWidth);
  }

  var raf = null;
  function scheduleFit() {
    if (raf) cancelAnimationFrame(raf);
    raf = requestAnimationFrame(fitRunesToLogo);
  }

  window.addEventListener("load", scheduleFit);
  window.addEventListener("resize", scheduleFit);
  if (document.fonts && document.fonts.ready) {
    document.fonts.ready.then(scheduleFit);
  }
})();
