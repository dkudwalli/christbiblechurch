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
        // The input sits inside its <label>, and a label's whole subtree feeds the
        // accessible name — so the message goes after the label, not after the
        // field, or the field's name becomes "Name <validation message>".
        const fieldLabel = field.closest(".contact-form__field") || field.parentElement;
        // Scope to this field's own next sibling — a row holds two labels, so a
        // row-wide querySelector would hand the email field the name's error.
        const sibling = fieldLabel.nextElementSibling;
        let errorEl =
          sibling && sibling.classList.contains("contact-form__error")
            ? sibling
            : null;
        if (!valid) {
          if (!errorEl) {
            errorEl = document.createElement("span");
            errorEl.className = "contact-form__error";
            errorEl.setAttribute("role", "alert");
            fieldLabel.after(errorEl);
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
});

document.addEventListener("DOMContentLoaded", () => {
  // Lightbox implementation
  const lightboxes = document.querySelectorAll(".js-lightbox");
  if (lightboxes.length > 0) {
    const dialog = document.createElement("dialog");
    dialog.className = "lightbox";
    dialog.setAttribute("aria-label", "Photo viewer");
    dialog.innerHTML = `
      <div class="lightbox__content">
        <button class="lightbox__close" aria-label="Close" type="button">&times;</button>
        <img class="lightbox__image" src="" alt="">
        <p class="lightbox__caption"></p>
        <div data-lightbox-link-shell></div>
      </div>
    `;
    document.body.appendChild(dialog);

    const img = dialog.querySelector(".lightbox__image");
    const caption = dialog.querySelector(".lightbox__caption");
    const linkShell = dialog.querySelector("[data-lightbox-link-shell]");
    const closeBtn = dialog.querySelector(".lightbox__close");

    // Escape closes a showModal() dialog natively, so all cleanup hangs off the
    // `close` event — otherwise Escape would leave `is-open` and the old image
    // behind. Closing is immediate: deferring dialog.close() to let the fade run
    // kept a transparent modal live, trapping focus inside it.
    dialog.addEventListener("close", () => {
      dialog.classList.remove("is-open");
      img.removeAttribute("src");
    });

    closeBtn.addEventListener("click", () => dialog.close());

    dialog.addEventListener("click", (e) => {
      if (e.target === dialog) {
        dialog.close();
      }
    });

    lightboxes.forEach((trigger) => {
      trigger.addEventListener("click", (e) => {
        e.preventDefault();
        const imageUrl = trigger.getAttribute("href");
        const permalink = trigger.getAttribute("data-permalink");
        const captionText = trigger.getAttribute("data-caption");
        const linkLabel =
          trigger.getAttribute("data-link-label") || "Open image";
        const altText =
          trigger.getAttribute("data-lightbox-alt") ||
          trigger.querySelector("img")?.getAttribute("alt") ||
          "";

        img.src = imageUrl;
        img.alt = altText;
        if (captionText) {
          caption.textContent = captionText;
          caption.style.display = "block";
        } else {
          caption.textContent = "";
          caption.style.display = "none";
        }

        if (linkShell) {
          linkShell.innerHTML = "";

          if (permalink) {
            const link = document.createElement("a");
            link.className = "lightbox__link";
            link.href = permalink;
            link.target = "_blank";
            link.rel = "noreferrer noopener";
            link.textContent = linkLabel;
            linkShell.appendChild(link);
          }
        }
        
        dialog.showModal();
        dialog.classList.add("is-open");
      });
    });
  }

  // Hero slideshow banner: intro animation, video reduced-motion guard, and
  // the crossfading slideshow (auto-advance + arrows + dots).
  const bannerReducedMotion = window.matchMedia(
    "(prefers-reduced-motion: reduce)"
  ).matches;

  // Pause autoplay video when the visitor prefers reduced motion.
  const bannerVideo = document.querySelector(".hero-banner__video");
  if (bannerVideo && bannerReducedMotion) {
    bannerVideo.removeAttribute("autoplay");
    bannerVideo.pause();
  }

  // Animate the overlay heading/CTA in once on load (all banner modes).
  const bannerIntro = document.querySelector("[data-hero-banner-intro]");
  if (bannerIntro) {
    window.requestAnimationFrame(() => {
      bannerIntro.classList.add("is-intro-done");
    });
  }

  // Slideshow (only emitted in markup when there are 2+ images).
  const banner = document.querySelector("[data-hero-banner]");
  if (banner) {
    const slides = Array.from(banner.querySelectorAll(".hero-banner__slide"));
    const dots = Array.from(banner.querySelectorAll("[data-hero-dot]"));
    const prevButton = banner.querySelector("[data-hero-prev]");
    const nextButton = banner.querySelector("[data-hero-next]");
    const AUTO_MS = 6000;
    let current = 0;
    let timer = null;
    // Set once the visitor takes manual control; autoplay never resumes after
    // that, so arrows/dots/arrow-keys act as the pause mechanism (WCAG 2.2.2).
    let userStopped = false;

    const go = (index) => {
      const next = (index + slides.length) % slides.length;
      slides.forEach((slide, i) => {
        const active = i === next;
        slide.classList.toggle("is-active", active);
        if (active) {
          slide.removeAttribute("aria-hidden");
        } else {
          slide.setAttribute("aria-hidden", "true");
        }
      });
      dots.forEach((dot, i) => {
        const active = i === next;
        dot.classList.toggle("is-active", active);
        if (active) {
          dot.setAttribute("aria-current", "true");
        } else {
          dot.removeAttribute("aria-current");
        }
      });
      current = next;
    };

    const stop = () => {
      if (timer !== null) {
        window.clearInterval(timer);
        timer = null;
      }
    };

    const start = () => {
      if (userStopped || bannerReducedMotion || document.hidden) {
        return;
      }
      stop();
      timer = window.setInterval(() => go(current + 1), AUTO_MS);
    };

    // Manual controls always work, and taking control stops the rotation for
    // good so the visitor keeps the slide they chose.
    const goAndRestart = (index) => {
      userStopped = true;
      go(index);
      stop();
    };

    if (prevButton) {
      prevButton.addEventListener("click", () => goAndRestart(current - 1));
    }
    if (nextButton) {
      nextButton.addEventListener("click", () => goAndRestart(current + 1));
    }
    dots.forEach((dot, i) => {
      dot.addEventListener("click", () => goAndRestart(i));
    });

    banner.addEventListener("keydown", (event) => {
      if (event.key === "ArrowLeft") {
        goAndRestart(current - 1);
      } else if (event.key === "ArrowRight") {
        goAndRestart(current + 1);
      }
    });

    banner.addEventListener("mouseenter", stop);
    banner.addEventListener("mouseleave", start);
    banner.addEventListener("focusin", stop);
    banner.addEventListener("focusout", start);
    document.addEventListener("visibilitychange", () => {
      if (document.hidden) {
        stop();
      } else {
        start();
      }
    });

    start();
  }
});
