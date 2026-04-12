document.addEventListener("DOMContentLoaded", () => {
  const mediaButtons = document.querySelectorAll("[data-media-target]");

  if (!mediaButtons.length || !window.wp || !window.wp.media) {
    return;
  }

  mediaButtons.forEach((button) => {
    button.addEventListener("click", (event) => {
      event.preventDefault();

      const selector = button.getAttribute("data-media-target");
      const target = selector ? document.querySelector(selector) : null;

      if (!target) {
        return;
      }

      const frame = window.wp.media({
        title: "Choose an audio file",
        button: {
          text: "Use this audio",
        },
        library: {
          type: ["audio"],
        },
        multiple: false,
      });

      frame.on("select", () => {
        const attachment = frame.state().get("selection").first().toJSON();
        target.value = attachment.url || "";
      });

      frame.open();
    });
  });
});
