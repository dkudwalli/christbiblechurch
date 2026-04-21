document.addEventListener("DOMContentLoaded", () => {
  const toggle = document.querySelector("[data-nav-toggle]");
  const nav = document.querySelector("[data-nav]");

  if (toggle && nav) {
    const desktopNavQuery = window.matchMedia("(min-width: 961px)");

    const syncNavState = (isOpen) => {
      nav.classList.toggle("is-open", isOpen);
      toggle.setAttribute("aria-expanded", isOpen ? "true" : "false");
    };

    const resetMobileNav = () => {
      if (desktopNavQuery.matches) {
        syncNavState(false);
      }
    };

    toggle.addEventListener("click", () => {
      syncNavState(!nav.classList.contains("is-open"));
    });

    if (typeof desktopNavQuery.addEventListener === "function") {
      desktopNavQuery.addEventListener("change", resetMobileNav);
    } else if (typeof desktopNavQuery.addListener === "function") {
      desktopNavQuery.addListener(resetMobileNav);
    }

    resetMobileNav();
  }
});
