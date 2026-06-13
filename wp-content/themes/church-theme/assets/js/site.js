document.documentElement.classList.add("has-js");

document.addEventListener("DOMContentLoaded", () => {
  // Section nav scrollspy
  const sectionLinks = document.querySelectorAll(".section-nav__list a[href^='#']");
  if (sectionLinks.length > 0) {
    const scrollspyObserver = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            const id = entry.target.id;
            sectionLinks.forEach((link) => {
              link.classList.toggle("is-active", link.getAttribute("href") === "#" + id);
            });
          }
        });
      },
      { rootMargin: "-20% 0px -70% 0px", threshold: 0 }
    );
    sectionLinks.forEach((link) => {
      const target = document.querySelector(link.getAttribute("href"));
      if (target) scrollspyObserver.observe(target);
    });
  }

  // Filter bar loading state
  const filterBar = document.querySelector(".filter-bar");
  if (filterBar) {
    filterBar.addEventListener("submit", () => {
      const submitBtn = filterBar.querySelector("[type='submit']");
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.setAttribute("aria-busy", "true");
      }
    });
  }

  // Contact form client-side validation
  const contactForm = document.querySelector(".contact-form");
  if (contactForm) {
    const requiredFields = contactForm.querySelectorAll("[required]");
    requiredFields.forEach((field) => {
      field.addEventListener("blur", () => {
        const valid = field.checkValidity();
        field.setAttribute("aria-invalid", valid ? "false" : "true");
        let errorEl = field.parentElement.querySelector(".contact-form__error");
        if (!valid) {
          if (!errorEl) {
            errorEl = document.createElement("span");
            errorEl.className = "contact-form__error";
            errorEl.setAttribute("role", "alert");
            field.after(errorEl);
          }
          errorEl.textContent = field.validationMessage;
        } else if (errorEl) {
          errorEl.remove();
        }
      });
    });

  }
});

document.addEventListener("DOMContentLoaded", () => {
  const toggle = document.querySelector("[data-nav-toggle]");
  const nav = document.querySelector("[data-nav]");

  if (!toggle || !nav) {
    return;
  }

  const desktopNavQuery = window.matchMedia("(min-width: 961px)");
  const submenuControls = [];

  const setNavState = (isOpen) => {
    nav.classList.toggle("is-open", isOpen);
    toggle.setAttribute("aria-expanded", isOpen ? "true" : "false");
  };

  const setSubmenuState = (control, isOpen) => {
    control.item.classList.toggle("is-submenu-open", isOpen);
    control.button.setAttribute("aria-expanded", isOpen ? "true" : "false");

    if (desktopNavQuery.matches) {
      control.submenu.hidden = false;
      return;
    }

    control.submenu.hidden = !isOpen;
  };

  const closeOtherSubmenus = (exception = null) => {
    submenuControls.forEach((control) => {
      if (control !== exception) {
        setSubmenuState(control, false);
      }
    });
  };

  const syncSubmenusForViewport = () => {
    submenuControls.forEach((control) => {
      if (desktopNavQuery.matches) {
        setSubmenuState(control, false);
        control.submenu.hidden = false;
        return;
      }

      control.submenu.hidden =
        control.button.getAttribute("aria-expanded") !== "true";
    });
  };

  nav.querySelectorAll(".menu-item-has-children").forEach((item, index) => {
    const link = item.querySelector(":scope > a");
    const submenu = item.querySelector(":scope > .sub-menu");

    if (!link || !submenu) {
      return;
    }

    const button = document.createElement("button");
    const submenuId = submenu.id || `primary-submenu-${index + 1}`;
    const label = link.textContent.trim() || "submenu";

    submenu.id = submenuId;
    button.type = "button";
    button.className = "site-nav__submenu-toggle";
    button.setAttribute("aria-controls", submenuId);
    button.setAttribute("aria-expanded", "false");
    button.setAttribute("aria-label", `Toggle submenu for ${label}`);

    const assistiveText = document.createElement("span");
    assistiveText.className = "screen-reader-text";
    assistiveText.textContent = `Toggle submenu for ${label}`;

    button.appendChild(assistiveText);
    link.after(button);

    const control = { item, button, submenu };

    button.addEventListener("click", (event) => {
      event.preventDefault();
      event.stopPropagation();

      const shouldOpen = button.getAttribute("aria-expanded") !== "true";

      closeOtherSubmenus(shouldOpen ? control : null);
      setSubmenuState(control, shouldOpen);
    });

    submenu.addEventListener("keydown", (event) => {
      if (event.key !== "Escape") {
        return;
      }

      setSubmenuState(control, false);
      button.focus();
    });

    submenuControls.push(control);
  });

  toggle.addEventListener("click", () => {
    const shouldOpen = !nav.classList.contains("is-open");

    if (!shouldOpen) {
      closeOtherSubmenus();
    }

    setNavState(shouldOpen);
  });

  nav.addEventListener("keydown", (event) => {
    if (event.key !== "Escape") {
      return;
    }

    closeOtherSubmenus();

    if (!desktopNavQuery.matches) {
      setNavState(false);
      toggle.focus();
    }
  });

  document.addEventListener("click", (event) => {
    const target = event.target;

    if (!(target instanceof Element)) {
      return;
    }

    if (nav.contains(target) || toggle.contains(target)) {
      return;
    }

    closeOtherSubmenus();

    if (!desktopNavQuery.matches) {
      setNavState(false);
    }
  });

  const syncViewportState = () => {
    if (desktopNavQuery.matches) {
      setNavState(false);
    }

    syncSubmenusForViewport();
  };

  if (typeof desktopNavQuery.addEventListener === "function") {
    desktopNavQuery.addEventListener("change", syncViewportState);
  } else if (typeof desktopNavQuery.addListener === "function") {
    desktopNavQuery.addListener(syncViewportState);
  }

  syncViewportState();
});
