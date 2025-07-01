/*
 * This file is part of PCCD.
 *
 * (c) Pere Orga Esteve <pere@orga.cat>
 *
 * This source file is subject to the AGPL license that is bundled with this
 * source code in the file LICENSE.
 */

(() => {
    const CHECKBOX_MANUALLY_ENABLED = "1";
    const CHECKBOX_MANUALLY_DISABLED = "2";

    const searchBox = document.querySelector("form input[type=search]");
    const variantCheckbox = document.querySelector("#variant");
    const sinonimCheckbox = document.querySelector("#sinonim");
    const equivalentCheckbox = document.querySelector("#equivalent");
    const nextButton = document.querySelector("a[rel=next]");
    const previousButton = document.querySelector("a[rel=prev]");
    const pagerSelector = document.querySelector(".pager select");

    // Remember the search options, but only if the search is empty.
    if (!searchBox.value) {
        variantCheckbox.checked = localStorage.getItem("variant") !== CHECKBOX_MANUALLY_DISABLED;
        sinonimCheckbox.checked = localStorage.getItem("sinonim") === CHECKBOX_MANUALLY_ENABLED;
        equivalentCheckbox.checked = localStorage.getItem("equivalent") === CHECKBOX_MANUALLY_ENABLED;

        // If we are in the homepage, remember pagination if set previously.
        if (!previousButton && nextButton) {
            const storedValue = localStorage.getItem("mostra");
            if (storedValue && storedValue !== pagerSelector.value) {
                // Request the front page with the preferred pagination, if it is set and different.
                location.assign("/?mostra=" + storedValue);
            }
        }
    }

    // Store values in local storage.
    variantCheckbox.addEventListener("change", () => {
        localStorage.setItem(
            "variant",
            variantCheckbox.checked ? CHECKBOX_MANUALLY_ENABLED : CHECKBOX_MANUALLY_DISABLED,
        );
    });
    sinonimCheckbox.addEventListener("change", () => {
        localStorage.setItem(
            "sinonim",
            sinonimCheckbox.checked ? CHECKBOX_MANUALLY_ENABLED : CHECKBOX_MANUALLY_DISABLED,
        );
    });
    equivalentCheckbox.addEventListener("change", () => {
        localStorage.setItem(
            "equivalent",
            equivalentCheckbox.checked ? CHECKBOX_MANUALLY_ENABLED : CHECKBOX_MANUALLY_DISABLED,
        );
    });
    pagerSelector.addEventListener("change", () => {
        // For simplicity, store empty string for the default value.
        localStorage.setItem(
            "mostra",
            pagerSelector.value === pagerSelector.dataset.default ? "" : pagerSelector.value,
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
})();
