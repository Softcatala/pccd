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
const searchBox = document.querySelector("form input[type=search]");
const previousButton = document.querySelector("a[rel=prev]");
const nextButton = document.querySelector("a[rel=next]");
const pager = document.querySelector(".pager select");
const isHomepage = !searchBox.value;

// If we are in the first page of the homepage, remember pagination if set previously.
if (isHomepage && !previousButton) {
  const pagerStoredValue = localStorage.getItem("mostra");
  if (pagerStoredValue && pagerStoredValue !== pager.value) {
    // Request the front page with the preferred pagination, if it is set and different.
    location.assign("/?mostra=" + pagerStoredValue);
  }
}

const MANUALLY_ENABLED = "1";
const MANUALLY_DISABLED = "2";

addEventListener("keydown", (event) => {
  if (event.key !== "ArrowLeft" && event.key !== "ArrowRight") {
    return;
  }

  const isSearchEmpty = !searchBox.value;
  const isSearchUnfocused = document.activeElement !== searchBox;
  const isSearchSelected = searchBox.selectionStart === 0 && searchBox.selectionEnd === searchBox.value.length;

  if (isSearchEmpty || isSearchUnfocused || isSearchSelected) {
    if (event.key === "ArrowRight" && nextButton) {
      nextButton.click();
    } else if (event.key === "ArrowLeft" && previousButton) {
      previousButton.click();
    }
  }
});

for (const { key, defaultChecked } of checkboxDefaults) {
  const checkbox = document.querySelector("#" + key);

  // Store search options in local storage.
  checkbox.addEventListener("change", () => {
    localStorage.setItem(key, checkbox.checked ? MANUALLY_ENABLED : MANUALLY_DISABLED);
  });

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

pager.addEventListener("change", () => {
  localStorage.setItem(
    "mostra",
    // For simplicity, store empty string for the default value.
    pager.value === pager.dataset.default ? "" : pager.value,
  );
  document.querySelector("form[role=search]").submit();
});

// Ensure the following is executed with browser back/forward navigation.
addEventListener("pageshow", () => {
  // Ensure browser does not try to remember last form value, as it doesn't help.
  const urlSearchParameters = new URLSearchParams(location.search);
  searchBox.value = urlSearchParameters.get("cerca") || "";

  // On desktop, select the searched value, so it can be replaced by simply typing.
  if (searchBox.value && !/Android|iPad|iPhone/u.test(navigator.userAgent)) {
    searchBox.select();
  }
});
