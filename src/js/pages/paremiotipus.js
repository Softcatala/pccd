/*
 * This file is part of PCCD.
 *
 * (c) Pere Orga Esteve <pere@orga.cat>
 *
 * This source file is subject to the AGPL license that is bundled with this
 * source code in the file LICENSE.
 */

const CV_COUNTER_THRESHOLD = 6;
const toggleAllSources = () => {
  const toggleAllButton = document.querySelector("#toggle-all");
  const isExpanded = toggleAllButton.textContent.startsWith("Contrau");
  toggleAllButton.textContent = isExpanded
    ? "Desplega-ho tot"
    : "Contrau-ho tot";
  toggleAllButton.title =
    (isExpanded ? "Mostra" : "Amaga") + " els detalls de cada font";
  for (const element of document.querySelectorAll("details")) {
    element.toggleAttribute("open", !isExpanded);
  }
};

const toggleAllButton = document.querySelector("#toggle-all");
if (toggleAllButton) {
  // Collapse all sources if this is the user's preference.
  if (localStorage.getItem("always_expand") === "2") {
    toggleAllSources();
  }
  toggleAllButton.addEventListener("click", (event) => {
    toggleAllSources();
    localStorage.setItem(
      "always_expand",
      event.target.textContent.startsWith("Desplega") ? "2" : "1",
    );
  });
}

const toggleTranslations = document.querySelector("#toggle-translations");
if (toggleTranslations) {
  toggleTranslations.addEventListener("click", () => {
    document.querySelector(".multilingue").classList.toggle("visually-hidden");
  });
}

// Show share buttons.
const shareButton = document.querySelector("#share");
const shareIcons = document.querySelector(".share-icons");
shareButton.addEventListener("click", (event) => {
  shareIcons.toggleAttribute("hidden");
  event.stopPropagation();
});
document.addEventListener("click", () => {
  shareIcons.setAttribute("hidden", true);
});

const handleShareCopyClick = async (event) => {
  event.preventDefault();
  event.stopPropagation();
  const shareIcon = event.currentTarget;
  const shareTitle = shareIcon.querySelector(".share-title");
  const shareImage = shareIcon.querySelector(".share-image");

  const successSVG =
    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="currentColor" d="m9.55 18-5.7-5.7 1.425-1.425L9.55 15.15l9.175-9.175L20.15 7.4z"/></svg>';

  try {
    await navigator.clipboard.writeText(location.href);
    shareTitle.textContent = "Copiat";
    shareImage.innerHTML = successSVG;
    shareIcon.removeAttribute("href");
    shareIcon.removeEventListener("click", handleShareCopyClick);
  } catch {
    shareTitle.textContent = "Error";
  }
};

document
  .querySelector(".share-icon.copy")
  .addEventListener("click", handleShareCopyClick);

// Play Common Voice files on click.
// TODO: remove the counter RML easter egg.
let cvCounter = 0;
for (const audio of document.querySelectorAll(".audio")) {
  // eslint-disable-next-line no-loop-func -- each listener needs its own audio reference
  audio.addEventListener("click", (event) => {
    event.preventDefault();
    audio.firstElementChild.play();
    cvCounter++;
    if (cvCounter > CV_COUNTER_THRESHOLD && toggleTranslations) {
      toggleTranslations.removeAttribute("hidden");
    }
  });
}

// Make elements with role="button" to behave consistently with buttons.
for (const button of document.querySelectorAll('[role="button"]')) {
  button.addEventListener("keydown", (event) => {
    if (event.key === " ") {
      event.preventDefault();
      button.click();
    }
  });
}
