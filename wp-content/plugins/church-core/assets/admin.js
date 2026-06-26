document.addEventListener("DOMContentLoaded", () => {
  const defaultSpeakerChoice = document.querySelector(
    "[data-default-speaker-choice]"
  );
  const defaultSpeakerCustom = document.querySelector(
    "[data-default-speaker-custom]"
  );
  const defaultSpeakerInput = defaultSpeakerCustom
    ? defaultSpeakerCustom.querySelector("input")
    : null;

  if (defaultSpeakerChoice && defaultSpeakerCustom) {
    const toggleDefaultSpeakerInput = () => {
      const shouldShowInput = defaultSpeakerChoice.value === "other";

      defaultSpeakerCustom.hidden = !shouldShowInput;

      if (defaultSpeakerInput) {
        defaultSpeakerInput.disabled = !shouldShowInput;
      }
    };

    defaultSpeakerChoice.addEventListener("change", toggleDefaultSpeakerInput);
    toggleDefaultSpeakerInput();
  }

  const resolveMediaElement = (button, attributeName) => {
    const selector = button.getAttribute(attributeName);

    if (!selector) {
      return null;
    }

    if (button.getAttribute("data-media-scope") === "row") {
      const row = button.closest("[data-profile-row]");

      if (row) {
        return row.querySelector(selector);
      }
    }

    return document.querySelector(selector);
  };

  const mediaButtons = document.querySelectorAll("[data-media-target]");

  if (mediaButtons.length && window.wp && window.wp.media) {
    mediaButtons.forEach((button) => {
      button.addEventListener("click", (event) => {
        event.preventDefault();

        const target = resolveMediaElement(button, "data-media-target");
        const preview = resolveMediaElement(button, "data-media-preview");
        const mediaType = button.getAttribute("data-media-type") || "audio";
        const mediaTitle =
          button.getAttribute("data-media-title") ||
          (mediaType === "image" ? "Choose an image" : "Choose an audio file");
        const mediaButton =
          button.getAttribute("data-media-button") ||
          (mediaType === "image" ? "Use this image" : "Use this audio");

        if (!target) {
          return;
        }

        const frame = window.wp.media({
          title: mediaTitle,
          button: {
            text: mediaButton
          },
          library: {
            type: [mediaType]
          },
          multiple: false
        });

        frame.on("select", () => {
          const attachment = frame.state().get("selection").first().toJSON();
          target.value = attachment.url || "";

          if (mediaType === "image") {
            target.value = attachment.id || "";
          }

          if (preview && mediaType === "image") {
            preview.src =
              attachment?.sizes?.medium?.url ||
              attachment?.sizes?.thumbnail?.url ||
              attachment.url ||
              "";
            preview.hidden = !preview.src;
          }
        });

        frame.open();
      });
    });
  }

  const sectionMetas = document.querySelectorAll("[data-section-meta]");

  sectionMetas.forEach((root) => {
    const layoutSelect = root.querySelector("[data-section-layout-select]");
    const panels = root.querySelectorAll("[data-section-layout-panel]");
    const profileList = root.querySelector("[data-profile-list]");
    const profileTemplate = root.querySelector("[data-profile-template]");

    const syncProfileRows = () => {
      if (!profileList) {
        return;
      }

      profileList.querySelectorAll("[data-profile-row]").forEach((row, index) => {
        row.querySelectorAll("[name]").forEach((field) => {
          field.name = field.name.replace(
            /church_section_profiles\[(?:__INDEX__|\d+)\]/,
            `church_section_profiles[${index}]`
          );
        });

        const number = row.querySelector("[data-profile-number]");

        if (number) {
          number.textContent = ` ${index + 1}`;
        }
      });
    };

    const toggleLayoutPanels = () => {
      if (!layoutSelect) {
        return;
      }

      panels.forEach((panel) => {
        panel.hidden =
          panel.getAttribute("data-section-layout-panel") !== layoutSelect.value;
      });
    };

    root.addEventListener("click", (event) => {
      const addButton = event.target.closest("[data-profile-add]");

      if (addButton && profileList && profileTemplate) {
        event.preventDefault();

        const fragment = profileTemplate.content.cloneNode(true);
        profileList.appendChild(fragment);
        syncProfileRows();
        return;
      }

      const removeButton = event.target.closest("[data-profile-remove]");

      if (removeButton) {
        event.preventDefault();
        const row = removeButton.closest("[data-profile-row]");

        if (row) {
          row.remove();
          syncProfileRows();
        }

        return;
      }

      const moveButton = event.target.closest("[data-profile-move]");

      if (moveButton) {
        event.preventDefault();

        const row = moveButton.closest("[data-profile-row]");

        if (!row || !row.parentElement) {
          return;
        }

        if (moveButton.getAttribute("data-profile-move") === "up") {
          const previousRow = row.previousElementSibling;

          if (previousRow) {
            row.parentElement.insertBefore(row, previousRow);
          }
        } else {
          const nextRow = row.nextElementSibling;

          if (nextRow) {
            row.parentElement.insertBefore(nextRow, row);
          }
        }

        syncProfileRows();
        return;
      }

      const clearButton = event.target.closest("[data-profile-image-clear]");

      if (clearButton) {
        event.preventDefault();

        const row = clearButton.closest("[data-profile-row]");
        const input = row?.querySelector("[data-profile-image-input]");
        const preview = row?.querySelector("[data-profile-image-preview]");

        if (input) {
          input.value = "";
        }

        if (preview) {
          preview.src = "";
          preview.hidden = true;
        }
      }
    });

    layoutSelect?.addEventListener("change", toggleLayoutPanels);
    toggleLayoutPanels();
    syncProfileRows();
  });

  const albumPhotoRoots = document.querySelectorAll("[data-album-photos-root]");

  albumPhotoRoots.forEach((root) => {
    const photoList = root.querySelector("[data-album-photo-list]");
    const photoTemplate = root.querySelector("[data-album-photo-template]");
    const pickerButton = root.querySelector("[data-album-photo-picker]");
    let dragItem = null;

    if (!photoList || !photoTemplate || !pickerButton) {
      return;
    }

    const createPlaceholder = () => {
      const placeholder = document.createElement("span");
      placeholder.className = "church-core-album-photo__placeholder";
      placeholder.textContent = "No image selected";
      return placeholder;
    };

    const populatePhotoItem = (item, attachment) => {
      const input = item.querySelector("[data-album-photo-input]");
      const preview = item.querySelector("[data-album-photo-preview]");
      const title = item.querySelector("[data-album-photo-title]");
      const caption = item.querySelector("[data-album-photo-caption]");
      const previewUrl =
        attachment?.sizes?.thumbnail?.url ||
        attachment?.sizes?.medium?.url ||
        attachment?.url ||
        "";

      if (input) {
        input.value = String(attachment?.id || "");
      }

      if (preview) {
        preview.innerHTML = "";

        if (previewUrl) {
          const image = document.createElement("img");
          image.src = previewUrl;
          image.alt = "";
          image.loading = "lazy";
          preview.appendChild(image);
        } else {
          preview.appendChild(createPlaceholder());
        }
      }

      if (title) {
        title.textContent = attachment?.title || "Selected photo";
      }

      if (caption) {
        caption.textContent =
          attachment?.caption || "No caption set for this image.";
      }
    };

    const appendPhotoItem = (attachment) => {
      const existingIds = new Set(
        [...photoList.querySelectorAll("[data-album-photo-input]")].map(
          (input) => input.value
        )
      );

      if (!attachment?.id || existingIds.has(String(attachment.id))) {
        return;
      }

      const fragment = photoTemplate.content.cloneNode(true);
      const item = fragment.querySelector("[data-album-photo-item]");

      if (!item) {
        return;
      }

      populatePhotoItem(item, attachment);
      photoList.appendChild(fragment);
    };

    const getDragTarget = (container, y) => {
      const items = [...container.querySelectorAll("[data-album-photo-item]")].filter(
        (item) => item !== dragItem
      );

      return items.reduce(
        (closest, item) => {
          const box = item.getBoundingClientRect();
          const offset = y - box.top - box.height / 2;

          if (offset < 0 && offset > closest.offset) {
            return { offset, element: item };
          }

          return closest;
        },
        { offset: Number.NEGATIVE_INFINITY, element: null }
      ).element;
    };

    pickerButton.addEventListener("click", (event) => {
      event.preventDefault();

      if (!(window.wp && window.wp.media)) {
        return;
      }

      const frame = window.wp.media({
        title:
          pickerButton.getAttribute("data-media-title") ||
          "Choose album photos",
        button: {
          text:
            pickerButton.getAttribute("data-media-button") ||
            "Use these photos"
        },
        library: {
          type: ["image"]
        },
        multiple: true
      });

      frame.on("select", () => {
        const attachments = frame.state().get("selection").toJSON();

        attachments.forEach((attachment) => appendPhotoItem(attachment));
      });

      frame.open();
    });

    root.addEventListener("click", (event) => {
      const removeButton = event.target.closest("[data-album-photo-remove]");

      if (!removeButton) {
        return;
      }

      event.preventDefault();

      const item = removeButton.closest("[data-album-photo-item]");

      if (item) {
        item.remove();
      }
    });

    photoList.addEventListener("dragstart", (event) => {
      const item = event.target.closest("[data-album-photo-item]");

      if (!item) {
        return;
      }

      dragItem = item;
      item.classList.add("is-dragging");
      event.dataTransfer.effectAllowed = "move";
    });

    photoList.addEventListener("dragover", (event) => {
      if (!dragItem) {
        return;
      }

      event.preventDefault();
      const target = getDragTarget(photoList, event.clientY);

      if (!target) {
        photoList.appendChild(dragItem);
        return;
      }

      photoList.insertBefore(dragItem, target);
    });

    photoList.addEventListener("dragend", () => {
      if (!dragItem) {
        return;
      }

      dragItem.classList.remove("is-dragging");
      dragItem = null;
    });
  });
});
