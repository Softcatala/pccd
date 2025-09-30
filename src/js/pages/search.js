/*
 * This file is part of PCCD.
 *
 * (c) Pere Orga Esteve <pere@orga.cat>
 *
 * This source file is subject to the AGPL license that is bundled with this
 * source code in the file LICENSE.
 */

const MANUALLY_ENABLED = "1";
const MANUALLY_DISABLED = "2";

const searchBox = document.querySelector("form input[type=search]");
const checkboxes = [
  { key: "variant", defaultChecked: true },
  { key: "sinonim", defaultChecked: false },
  { key: "equivalent", defaultChecked: false },
];
const nextButton = document.querySelector("a[rel=next]");
const previousButton = document.querySelector("a[rel=prev]");
const pager = document.querySelector(".pager select");

// Remember the search options, but only if the search is empty.
if (!searchBox.value) {
  for (const { key, defaultChecked } of checkboxes) {
    const checkbox = document.querySelector("#" + key);
    const storedValue = localStorage.getItem(key);

    checkbox.checked = defaultChecked;
    if (storedValue === MANUALLY_DISABLED) {
      checkbox.checked = false;
    } else if (storedValue === MANUALLY_ENABLED) {
      checkbox.checked = true;
    }
  }

  // If we are in the homepage, remember pagination if set previously.
  if (!previousButton && nextButton) {
    const storedValue = localStorage.getItem("mostra");
    if (storedValue && storedValue !== pager.value) {
      // Request the front page with the preferred pagination, if it is set and different.
      location.assign("/?mostra=" + storedValue);
    }
  }
}

// Store search options in local storage.
for (const { key } of checkboxes) {
  const checkbox = document.querySelector("#" + key);
  checkbox.addEventListener("change", () => {
    localStorage.setItem(
      key,
      checkbox.checked ? MANUALLY_ENABLED : MANUALLY_DISABLED,
    );
  });
}

pager.addEventListener("change", () => {
  // For simplicity, store empty string for the default value.
  localStorage.setItem(
    "mostra",
    pager.value === pager.dataset.default ? "" : pager.value,
  );
  // Submit the form automatically when the selector changes.
  document.querySelector("form[role=search]").submit();
});

// Ensure the following is executed with browser back/forward navigation.
window.addEventListener("pageshow", () => {
  // Ensure browser does not try to remember last form value, as it doesn't help.
  searchBox.value = new URLSearchParams(location.search).get("cerca") || "";

  // On desktop, select the searched value, so it can be replaced by simply typing.
  if (searchBox.value && !/Android|iPad|iPhone/.test(navigator.userAgent)) {
    searchBox.select();
  }
});
