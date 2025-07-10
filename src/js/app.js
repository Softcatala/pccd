/*
 * This file is part of PCCD.
 *
 * (c) Pere Orga Esteve <pere@orga.cat>
 *
 * This source file is subject to the AGPL license that is bundled with this
 * source code in the file LICENSE.
 */

// Google Tag Manager code.
// eslint-disable-next-line unicorn/prefer-global-this
window.dataLayer = window.dataLayer || [];
const gtag = function () {
    // eslint-disable-next-line no-undef
    dataLayer.push(arguments);
};
gtag("js", new Date());
gtag("config", "G-CP42Y3NK1R");

(() => {
    // Show the cookie alert if it hasn't been accepted.
    if (!localStorage.getItem("accept_cookies")) {
        const cookieBanner = document.querySelector("#cookie-banner");
        cookieBanner.removeAttribute("hidden");
        cookieBanner.querySelector("button").addEventListener("click", () => {
            cookieBanner.remove();
            localStorage.setItem("accept_cookies", "1");
        });
    }

    // Prefetch internal links on hover.
    // Inspired by https://github.com/instantpage/instant.page/blob/master/instantpage.js
    const preloadedLinks = new Set();
    const prefetchLink = (href) => {
        if (!preloadedLinks.has(href)) {
            preloadedLinks.add(href);
            const link = document.createElement("link");
            link.rel = "prefetch";
            link.href = href;
            document.head.append(link);
        }
    };
    for (const a of document.querySelectorAll("a")) {
        if (a.href && a.origin === location.origin) {
            a.addEventListener("mouseenter", () => prefetchLink(a.href));
        }
    }
})();
