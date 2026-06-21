/*
 * This file is part of PCCD.
 *
 * (c) Pere Orga Esteve <pere@orga.cat>
 *
 * This source file is subject to the AGPL license that is bundled with this
 * source code in the file LICENSE.
 */

// Google Tag Manager code.
/* eslint-disable unicorn/prefer-global-this, no-undef, prefer-rest-params, unicorn/no-invalid-argument-count, unicorn/logical-assignment-operators */
window.dataLayer = window.dataLayer || [];
const gtag = function () {
  dataLayer.push(arguments);
};
gtag("js", new Date());
gtag("config", "G-CP42Y3NK1R");
/* eslint-enable unicorn/prefer-global-this, no-undef, prefer-rest-params, unicorn/no-invalid-argument-count, unicorn/logical-assignment-operators */

if (!localStorage.getItem("accept_cookies")) {
  const cookieBanner = document.createElement("div");
  cookieBanner.id = "cookie-banner";
  cookieBanner.setAttribute("role", "alert");
  cookieBanner.innerHTML =
    "<p>Aquest lloc web fa servir galetes de Google per analitzar el trànsit.</p><button type='button'>D'acord</button>";
  document.body.append(cookieBanner);

  cookieBanner.querySelector("button").addEventListener("click", () => {
    cookieBanner.remove();
    localStorage.setItem("accept_cookies", "1");
  });
}

// Prefetch internal links on hover.
// Inspired by https://github.com/instantpage/instant.page/blob/master/instantpage.js
const preloadedLinks = new Set();
const prefetchLink = (href) => {
  if (preloadedLinks.has(href)) {
    return;
  }

  preloadedLinks.add(href);
  const link = document.createElement("link");
  link.rel = "prefetch";
  link.href = href;
  document.head.append(link);
};

for (const a of document.querySelectorAll("a")) {
  if (a.href && a.origin === location.origin) {
    a.addEventListener("mouseenter", () => prefetchLink(a.href));
  }
}
