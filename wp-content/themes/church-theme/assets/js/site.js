document.documentElement.classList.add("has-js");

document.addEventListener("DOMContentLoaded", () => {
  // Scroll-based UI updates (header shadow and back-to-top)
  const header = document.querySelector(".site-header");
  const backToTop = document.querySelector(".back-to-top");
  let scrollTicking = false;

  const handleScroll = () => {
    const scrollY = window.scrollY;
    if (header) {
      header.classList.toggle("is-scrolled", scrollY > 10);
    }
    if (backToTop) {
      backToTop.classList.toggle("is-visible", scrollY > 500);
    }
    scrollTicking = false;
  };

  if (header || backToTop) {
    window.addEventListener(
      "scroll",
      () => {
        if (!scrollTicking) {
          window.requestAnimationFrame(handleScroll);
          scrollTicking = true;
        }
      },
      { passive: true }
    );
    handleScroll();
  }

  if (backToTop) {
    backToTop.addEventListener("click", () => {
      window.scrollTo({ top: 0, behavior: "smooth" });
    });
  }

  // Scroll-triggered reveal animations
  const reveals = document.querySelectorAll(".reveal");
  if (reveals.length > 0) {
    const prefersReduced = window.matchMedia(
      "(prefers-reduced-motion: reduce)"
    ).matches;
    if (prefersReduced || typeof IntersectionObserver !== "function") {
      reveals.forEach((el) => el.classList.add("is-visible"));
    } else {
      const revealObserver = new IntersectionObserver(
        (entries) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              entry.target.classList.add("is-visible");
              revealObserver.unobserve(entry.target);
            }
          });
        },
        { threshold: 0.15 }
      );
      reveals.forEach((el) => revealObserver.observe(el));
    }
  }

  const mapFrameIframes = document.querySelectorAll(".map-frame iframe");
  mapFrameIframes.forEach((frame) => {
    const mapFrame = frame.closest(".map-frame");

    if (!mapFrame) {
      return;
    }

    frame.addEventListener("load", () => {
      mapFrame.classList.add("is-loaded");
    });
  });

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
  const toggleAssistiveText = toggle.querySelector(".screen-reader-text");
  const openMenuLabel = "Open main menu";
  const closeMenuLabel = "Close main menu";

  const setNavState = (isOpen) => {
    nav.classList.toggle("is-open", isOpen);
    toggle.setAttribute("aria-expanded", isOpen ? "true" : "false");
    toggle.setAttribute("aria-label", isOpen ? closeMenuLabel : openMenuLabel);
    if (toggleAssistiveText) {
      toggleAssistiveText.textContent = isOpen ? closeMenuLabel : openMenuLabel;
    }
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
    button.setAttribute("aria-label", `Toggle ${label} submenu`);

    const assistiveText = document.createElement("span");
    assistiveText.className = "screen-reader-text";
    assistiveText.textContent = `Toggle ${label} submenu`;

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

  // Lightbox implementation
  const lightboxes = document.querySelectorAll(".js-lightbox");
  if (lightboxes.length > 0) {
    const dialog = document.createElement("dialog");
    dialog.className = "lightbox";
    dialog.innerHTML = `
      <div class="lightbox__content">
        <button class="lightbox__close" aria-label="Close" type="button">&times;</button>
        <img class="lightbox__image" src="" alt="">
        <a class="lightbox__link" href="" target="_blank" rel="noreferrer noopener">View on Instagram</a>
      </div>
    `;
    document.body.appendChild(dialog);

    const img = dialog.querySelector(".lightbox__image");
    const link = dialog.querySelector(".lightbox__link");
    const closeBtn = dialog.querySelector(".lightbox__close");

    const closeLightbox = () => {
      dialog.classList.remove("is-open");
      // Wait for CSS transition to finish before actually closing
      setTimeout(() => {
        dialog.close();
        img.src = "";
      }, 300);
    };

    closeBtn.addEventListener("click", closeLightbox);
    
    dialog.addEventListener("click", (e) => {
      if (e.target === dialog) {
        closeLightbox();
      }
    });

    lightboxes.forEach((trigger) => {
      trigger.addEventListener("click", (e) => {
        e.preventDefault();
        const imageUrl = trigger.getAttribute("href");
        const permalink = trigger.getAttribute("data-permalink");
        
        img.src = imageUrl;
        if (permalink) {
          link.href = permalink;
          link.style.display = "block";
        } else {
          link.style.display = "none";
        }
        
        dialog.showModal();
        dialog.classList.add("is-open");
      });
    });
  }
});
