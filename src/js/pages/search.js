/*
 * This file is part of PCCD.
 *
 * (c) Pere Orga Esteve <pere@orga.cat>
 *
 * This source file is subject to the AGPL license that is bundled with this
 * source code in the file LICENSE.
 */

const checkboxDefaults = [
  { key: "variant", defaultChecked: true },
  { key: "sinonim", defaultChecked: false },
  { key: "equivalent", defaultChecked: false },
];

const MANUALLY_ENABLED = "1";
const MANUALLY_DISABLED = "2";

const initSearchPage = () => {
  const searchBox = document.querySelector("form input[type=search]");
  const isHomepage = !searchBox.value;

  for (const { key, defaultChecked } of checkboxDefaults) {
    const checkbox = document.querySelector("#" + key);
    if (!checkbox) {
      continue;
    }

    // Remember the search options, only in the homepage.
    if (isHomepage) {
      checkbox.checked = defaultChecked;
      const checkboxStoredValue = localStorage.getItem(key);
      if (checkboxStoredValue === MANUALLY_DISABLED) {
        checkbox.checked = false;
      } else if (checkboxStoredValue === MANUALLY_ENABLED) {
        checkbox.checked = true;
      }
    }
  }

  // Ensure browser does not try to remember last form value.
  const urlSearchParameters = new URLSearchParams(location.search);
  searchBox.value = urlSearchParameters.get("cerca") || "";
};

initSearchPage();

addEventListener("keydown", (event) => {
  const searchBox = document.querySelector("form input[type=search]");

  if (event.key.length === 1 && event.key !== " " && !event.ctrlKey && !event.altKey && !event.metaKey) {
    if (document.activeElement === searchBox) {
      return;
    }

    const activeTag = document.activeElement ? document.activeElement.tagName : "";
    if (activeTag === "SELECT") {
      return;
    }

    searchBox.focus();
    searchBox.value = event.key;
    event.preventDefault();
  }
});

document.addEventListener("change", (event) => {
  if (!event.target.matches("input[type=checkbox]")) {
    return;
  }

  const key = event.target.id;
  if (checkboxDefaults.some((c) => c.key === key)) {
    localStorage.setItem(key, event.target.checked ? MANUALLY_ENABLED : MANUALLY_DISABLED);
  }
});

// Ensure the following is executed with browser back/forward navigation.
addEventListener("pageshow", () => {
  const searchBox = document.querySelector("form input[type=search]");
  const urlSearchParameters = new URLSearchParams(location.search);
  searchBox.value = urlSearchParameters.get("cerca") || "";
});

// Infinite scroll: append the next page when the next link nears the viewport.
const style = document.createElement("style");
style.textContent = ".pager { visibility: hidden; }";
document.head.append(style);

const infiniteScroll = { loading: false };

const observeInfiniteScroll = () => {
  infiniteScroll.observer.disconnect();
  const nextLink = document.querySelector("a[rel=next]");
  if (nextLink) {
    infiniteScroll.observer.observe(nextLink);
  }
};

infiniteScroll.observer = new IntersectionObserver(
  async ([entry]) => {
    if (infiniteScroll.loading || !entry.isIntersecting) {
      return;
    }

    const nextLink = document.querySelector("a[rel=next]");
    const list = document.querySelector("form[role=search] ol");
    if (!list || !nextLink) {
      return;
    }

    infiniteScroll.loading = true;
    try {
      const response = await fetch(nextLink.href, { headers: { "X-Requested-With": "XMLHttpRequest" } });
      if (!response.ok) {
        throw new Error("Network error");
      }
      const data = await response.json();
      const temporary = document.createElement("div");
      temporary.innerHTML = data.mainContent;
      list.append(...temporary.querySelectorAll("ol > li"));

      const pager = document.querySelector(".pager");
      const nextPagePager = temporary.querySelector(".pager");
      pager.replaceWith(nextPagePager);

      infiniteScroll.loading = false;
      observeInfiniteScroll();
    } catch {
      // Keep the pager usable if the request fails.
      infiniteScroll.loading = false;
    }
  },
  { rootMargin: "400px" },
);

observeInfiniteScroll();
