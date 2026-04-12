document.addEventListener("DOMContentLoaded", () => {
  const toggle = document.querySelector("[data-nav-toggle]");
  const nav = document.querySelector("[data-nav]");

  if (!toggle || !nav) {
    return;
  }

  toggle.addEventListener("click", () => {
    const isOpen = nav.classList.toggle("is-open");
    document.body.classList.toggle("nav-is-open", isOpen);
    toggle.setAttribute("aria-expanded", isOpen ? "true" : "false");
  });
});
