const { test, expect } = require("@playwright/test");
const axeScriptPath = require.resolve("axe-core/axe.min.js");
// Full WCAG 2.0/2.1 A+AA rule set rather than a hand-picked allowlist.
const axeTags = ["wcag2a", "wcag2aa", "wcag21a", "wcag21aa"];

async function ensureAxe(page) {
  const hasAxe = await page.evaluate(() => Boolean(window.axe));

  if (!hasAxe) {
    await page.addScriptTag({ path: axeScriptPath });
  }
}

async function runAxeRules(page, tags = axeTags) {
  await ensureAxe(page);

  return page.evaluate(
    async ({ runOnly }) => {
      const results = await axe.run(document, {
        runOnly: { type: "tag", values: runOnly }
      });

      return results.violations.map((violation) => ({
        id: violation.id,
        impact: violation.impact,
        nodes: violation.nodes.map((node) => node.target)
      }));
    },
    { runOnly: tags }
  );
}

async function runAxeRuleIds(page, ruleIds) {
  await ensureAxe(page);

  return page.evaluate(async (values) => {
    const results = await axe.run(document, {
      runOnly: { type: "rule", values }
    });

    return results.violations.map((violation) => ({
      id: violation.id,
      impact: violation.impact,
      nodes: violation.nodes.map((node) => node.target)
    }));
  }, ruleIds);
}

async function expectNoAxeViolations(page, context, tags = axeTags) {
  const violations = await runAxeRules(page, tags);
  expect(violations, `Unexpected axe violations on ${context}`).toEqual([]);
}

const publicPages = [
  "/",
  "/about-us/",
  "/worship/",
  "/gallery/",
  "/contact-us/",
  "/give/",
  "/events/",
  "/sermons/"
];

test("public pages render a visible main region", async ({ page }) => {
  for (const path of publicPages) {
    await page.goto(path);
    await expect(page.locator("main#main-content")).toBeVisible();
    await expect(page.locator("h1").first()).toBeVisible();
  }
});

test("gallery page prioritizes seeded photo albums and keeps Instagram secondary", async ({ page }) => {
  await page.setViewportSize({ width: 1440, height: 1200 });
  await page.goto("/gallery/");

  const albumSection = page.locator(".gallery-albums");
  const albumCards = page.locator(".album-card");
  const instagramSection = page.locator(".gallery-feed");
  const instagramCards = instagramSection.locator(".gallery-card");

  await expect(albumSection).toBeVisible();
  await expect(page.getByRole("heading", { name: "Photo albums" })).toBeVisible();
  await expect(albumCards).toHaveCount(3);
  await expect(albumCards.first()).toContainText("Church Retreat 2024");
  await expect(instagramSection).toBeVisible();

  const sectionOrder = await page.evaluate(() => {
    const album = document.querySelector(".gallery-albums");
    const instagram = document.querySelector(".gallery-feed");

    if (!album || !instagram) {
      return null;
    }

    return album.compareDocumentPosition(instagram);
  });

  expect(sectionOrder & 4).toBe(4);
  const instagramCardCount = await instagramCards.count();
  expect(instagramCardCount).toBeGreaterThan(0);
  expect(instagramCardCount).toBeLessThanOrEqual(10);
});

test("seeded album detail page renders photo grid and shared lightbox", async ({ page }) => {
  await page.goto("/photo-albums/church-retreat-2024/");

  await expect(page.getByRole("heading", { name: "Church Retreat 2024" })).toBeVisible();
  await expect(page.locator(".album-detail")).toBeVisible();
  await expect(page.locator(".album-photo-grid .gallery-card")).toHaveCount(4);
  await expect(page.getByRole("link", { name: "Back to gallery" })).toBeVisible();

  await page.locator(".album-photo-grid .js-lightbox").first().click();
  await expect(page.locator("dialog.lightbox[open]")).toBeVisible();
  await expect(page.locator(".lightbox__image")).toBeVisible();
  await expect(page.locator(".lightbox__link")).toHaveCount(0);
});

test("mobile navigation opens and submenu controls expand", async ({ page }) => {
  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto("/");

  const menuToggle = page.locator("[data-nav-toggle]");

  await expect(menuToggle).toHaveAccessibleName("Open main menu");
  await menuToggle.click();
  await expect(page.locator("[data-nav]")).toHaveClass(/is-open/);
  await expect(menuToggle).toHaveAttribute("aria-label", "Close main menu");

  const submenuToggle = page.locator(".site-nav__submenu-toggle").first();

  if ((await submenuToggle.count()) > 0) {
    await expect(submenuToggle).toHaveAccessibleName(/Toggle .* submenu/);
    await submenuToggle.click();
    await expect(submenuToggle).toHaveAttribute("aria-expanded", "true");
    await page.keyboard.press("Escape");
    await expect(submenuToggle).toHaveAttribute("aria-expanded", "false");
  }
});

test("homepage reveal content remains readable without JavaScript", async ({ browser }) => {
  const context = await browser.newContext({ javaScriptEnabled: false });
  const page = await context.newPage();

  await page.goto("/");

  const hiddenRevealCount = await page.evaluate(() => {
    return [...document.querySelectorAll(".reveal")].filter((element) => {
      const styles = window.getComputedStyle(element);

      return styles.opacity === "0" || styles.visibility === "hidden";
    }).length;
  });

  expect(hiddenRevealCount).toBe(0);
  await expect(
    page.getByText("Our Mission, Vision and Core Values", { exact: true })
  ).toBeVisible();
  await expect(page.getByRole("heading", { name: "Upcoming opportunities to gather." })).toBeVisible();

  await context.close();
});

test("section navigation does not scroll targets underneath the sticky header", async ({ page }) => {
  await page.goto("/about-us/");
  await page.waitForLoadState("domcontentloaded");

  const sectionLinks = page.locator(".section-nav__list a");

  if ((await sectionLinks.count()) === 0) {
    test.skip(true, "No section links were rendered.");
  }

  const firstSectionLink = sectionLinks.first();
  const targetHref = await firstSectionLink.getAttribute("href");

  if (!targetHref) {
    test.skip(true, "No section links were rendered.");
  }

  await firstSectionLink.click();

  const targetId = targetHref.replace(/^#/, "");
  const headerHeight = await page.locator(".site-header").evaluate((element) => {
    return element.getBoundingClientRect().height;
  });
  const targetTop = await page.locator(`#${targetId}`).evaluate((element) => {
    return element.getBoundingClientRect().top;
  });

  expect(targetTop).toBeGreaterThanOrEqual(headerHeight - 16);
});

test("sermon filters expose visible labels and clear state", async ({ page }) => {
  await page.goto("/sermons/");

  await expect(page.getByText("Search", { exact: true })).toBeVisible();
  await expect(page.getByText("Speaker", { exact: true })).toBeVisible();
  await expect(page.getByText("Series", { exact: true })).toBeVisible();

  await page.getByRole("searchbox").fill("grace");
  await page.getByRole("button", { name: "Filter" }).click();
  await expect(page.getByRole("link", { name: "Clear filters" })).toBeVisible();
});

test("section pages render configured section layouts and section targets", async ({ page }) => {
  await page.goto("/about-us/");
  await expect(page.locator("#elder-board .elder-card").first()).toBeVisible();
  await expect(page.locator("#elder-board .section-media-grid")).toHaveCount(0);

  await page.goto("/worship/");
  await expect(page.locator(".section-nav__list a[href='#womens-ministry']")).toBeVisible();
  await expect(
    page.locator("#womens-ministry .section-card, #womens-ministry .section-story").first()
  ).toBeVisible();
});

test("contact page renders a simplified four-field form and styled phone input", async ({ page }) => {
  await page.setViewportSize({ width: 1440, height: 1200 });
  await page.goto("/contact-us/");

  await expect(page.locator("#contact_name")).toBeVisible();
  await expect(page.locator("#contact_email")).toBeVisible();
  await expect(page.locator("#contact_phone")).toBeVisible();
  await expect(page.locator("#contact_message")).toBeVisible();
  await expect(page.getByText("Phone Number", { exact: true })).toBeVisible();
  await expect(page.getByRole("link", { name: "Email the Church" })).toBeVisible();
  await expect(page.locator("#contact_inquiry_type")).toHaveCount(0);
  await expect(page.locator("[name='contact_preferred_contact_method']")).toHaveCount(0);

  const fieldMetrics = await page.evaluate(() => {
    const emailField = document.querySelector("#contact_email");
    const phoneField = document.querySelector("#contact_phone");
    const emailStyles = emailField ? window.getComputedStyle(emailField) : null;
    const phoneStyles = phoneField ? window.getComputedStyle(phoneField) : null;

    return {
      emailWidth: emailField?.getBoundingClientRect().width ?? 0,
      phoneWidth: phoneField?.getBoundingClientRect().width ?? 0,
      emailBorderTopWidth: emailStyles?.borderTopWidth ?? "",
      phoneBorderTopWidth: phoneStyles?.borderTopWidth ?? "",
      emailBorderRadius: emailStyles?.borderRadius ?? "",
      phoneBorderRadius: phoneStyles?.borderRadius ?? ""
    };
  });

  expect(fieldMetrics.phoneWidth).toBeGreaterThan(fieldMetrics.emailWidth * 1.5);
  expect(fieldMetrics.phoneBorderTopWidth).toBe("1px");
  expect(fieldMetrics.phoneBorderTopWidth).toBe(fieldMetrics.emailBorderTopWidth);
  expect(fieldMetrics.phoneBorderRadius).toBe(fieldMetrics.emailBorderRadius);
});

test("contact form preserves simplified field values on invalid submit", async ({ page }) => {
  await page.goto("/contact-us/");

  await page.locator("#contact_name").fill("Playwright Visitor");
  await page.locator("#contact_email").fill("visitor@example.com");
  await page.locator("#contact_phone").fill("+91 98765 43210");
  await page.locator("#contact_message").fill("Testing preserved form state.");
  await page.locator("#contact_message").fill("");
  await Promise.all([
    page.waitForURL(/church_contact_status=invalid/),
    page.locator(".contact-form").evaluate((form) => form.submit()),
  ]);

  await expect(page.locator(".contact-form__notice.is-error")).toBeVisible();
  await expect(page.locator("#contact_name")).toHaveValue("Playwright Visitor");
  await expect(page.locator("#contact_email")).toHaveValue("visitor@example.com");
  await expect(page.locator("#contact_phone")).toHaveValue("+91 98765 43210");
  await expect(page.locator("#contact_message")).toHaveValue("");
});

test("contact page offers a direct Google Maps fallback action", async ({ page }) => {
  await page.goto("/contact-us/");

  const mapFallbackLink = page.getByRole("link", { name: "Open in Google Maps" });

  await expect(mapFallbackLink).toBeVisible();
  await expect(mapFallbackLink).toHaveAttribute("target", "_blank");
});

test("homepage surfaces the navy-led Wayfinding palette in primary actions and structural surfaces", async ({ page }) => {
  await page.goto("/");

  // Assert the WIRING (structural surfaces consume the palette tokens) rather than
  // hardcoded brand hex, so an intentional palette retune doesn't falsely break CI.
  const themePalette = await page.evaluate(() => {
    // Resolve any CSS color string to a normalized rgb() via a throwaway element.
    const toRgb = (value) => {
      const probe = document.createElement("span");
      probe.style.color = value;
      document.body.appendChild(probe);
      const rgb = window.getComputedStyle(probe).color;
      probe.remove();
      return rgb;
    };

    const rootStyles = window.getComputedStyle(document.documentElement);
    const accent = rootStyles.getPropertyValue("--accent").trim();
    const surfaceDark = rootStyles.getPropertyValue("--surface-dark").trim();
    const primaryAction = document.querySelector(".hero .button");
    const footer = document.querySelector(".site-footer");

    return {
      accent,
      surfaceDark,
      accentRgb: accent ? toRgb(accent) : null,
      surfaceDarkRgb: surfaceDark ? toRgb(surfaceDark) : null,
      primaryActionBackground: primaryAction ? window.getComputedStyle(primaryAction).backgroundColor : null,
      footerBackground: footer ? window.getComputedStyle(footer).backgroundColor : null
    };
  });

  // Tokens are defined...
  expect(themePalette.accent).not.toBe("");
  expect(themePalette.surfaceDark).not.toBe("");
  // ...and the primary action + footer actually render from them.
  expect(themePalette.primaryActionBackground).toBe(themePalette.accentRgb);
  expect(themePalette.footerBackground).toBe(themePalette.surfaceDarkRgb);
});

test("events archive and detail pages render expected states", async ({ page }) => {
  await page.goto("/events/");

  await expect(
    page.getByRole("heading", { name: "Join us at the next gathering." })
  ).toBeVisible();

  const eventLinks = page.locator(".event-card h2 a");
  const eventCount = await eventLinks.count();

  if (eventCount === 0) {
    await expect(
      page.getByText("No upcoming events are listed right now.")
    ).toBeVisible();
    return;
  }

  const href = await eventLinks.first().getAttribute("href");
  expect(href).toBeTruthy();

  await page.goto(href);
  await expect(page.locator(".event-meta-grid")).toBeVisible();
  await expect(
    page.getByRole("link", { name: "Back to all events" })
  ).toBeVisible();
  await expectNoAxeViolations(page, href);
});

test("single sermon audio uses native controls instead of MediaElement", async ({ page }) => {
  await page.goto("/sermons/");

  const sermonLinks = page.locator(".sermon-card h2 a");
  const sermonCount = await sermonLinks.count();

  // The bootstrap seeds sermons unconditionally, so zero here means the seed
  // failed silently — assert rather than skip, or the next four checks vanish.
  expect(sermonCount).toBeGreaterThan(0);

  const sermonHrefs = [];

  for (let index = 0; index < Math.min(sermonCount, 9); index += 1) {
    const href = await sermonLinks.nth(index).getAttribute("href");

    if (href) {
      sermonHrefs.push(href);
    }
  }

  let foundAudioSermon = false;

  for (const href of sermonHrefs) {
    await page.goto(href);

    if ((await page.locator(".audio-player").count()) > 0) {
      foundAudioSermon = true;
      break;
    }
  }

  expect(foundAudioSermon).toBe(true);

  await expect(page.locator(".audio-player audio[controls][aria-labelledby]")).toBeVisible();
  await expect(page.locator(".audio-player .mejs-container")).toHaveCount(0);
  await expect(page.locator(".audio-player .mejs-horizontal-volume-slider")).toHaveCount(0);
  await expect(page.getByRole("link", { name: "Open audio directly" })).toBeVisible();
  await expectNoAxeViolations(page, page.url());
});

test("key templates pass the WCAG A/AA rule set", async ({ page }) => {
  // Includes the templates that carry the interactive widgets — the homepage
  // slideshow and the lightbox on /gallery/ — which the old list never scanned.
  const pages = [
    "/",
    "/about-us/",
    "/worship/",
    "/events/",
    "/contact-us/",
    "/give/",
    "/gallery/",
    "/sermons/"
  ];

  for (const path of pages) {
    await page.goto(path);
    await expectNoAxeViolations(page, path);
  }
});

test("hero and side panels do not expose nested complementary landmarks", async ({ page }) => {
  const pages = ["/", "/worship/", "/give/"];

  for (const path of pages) {
    await page.goto(path);
    const violations = await runAxeRuleIds(page, ["landmark-complementary-is-top-level"]);
    expect(violations, `Unexpected complementary landmark violation on ${path}`).toEqual([]);
  }
});

test("series taxonomy page renders sermon cards", async ({ page }) => {
  await page.goto("/sermons/");

  const seriesLinks = page.locator(".sermon-card .sermon-meta a[href*='/series/']");
  const seriesCount = await seriesLinks.count();

  expect(seriesCount).toBeGreaterThan(0);

  const href = await seriesLinks.first().getAttribute("href");
  expect(href).toBeTruthy();

  await page.goto(href);
  await expect(page.locator("main#main-content")).toBeVisible();
  await expect(page.locator("h1").first()).toBeVisible();
  await expect(page.locator(".sermon-card").first()).toBeVisible();
});

test("speaker taxonomy page renders sermon cards", async ({ page }) => {
  await page.goto("/sermons/");

  const firstSermonLink = page.locator(".sermon-card h2 a").first();

  expect(await firstSermonLink.count()).toBeGreaterThan(0);

  const sermonHref = await firstSermonLink.getAttribute("href");
  await page.goto(sermonHref);

  const speakerCard = page.locator(".single-sermon__meta-card").filter({ hasText: "Preacher" });

  expect(await speakerCard.count()).toBeGreaterThan(0);

  const speakerName = await speakerCard.locator(".meta-value").textContent();
  expect(speakerName).toBeTruthy();

  const slug = speakerName.trim().toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/^-|-$/g, "");
  await page.goto(`/speaker/${slug}/`);

  await expect(page.locator("main#main-content")).toBeVisible();
  await expect(page.locator("h1").first()).toBeVisible();
  await expect(page.locator(".sermon-card").first()).toBeVisible();
});

test("contact form success path shows confirmation and clears form", async ({ page }) => {
  await page.goto("/contact-us/");

  await page.locator("#contact_name").fill("Playwright Test");
  await page.locator("#contact_email").fill("test@example.com");
  await page.locator("#contact_message").fill("This is an automated smoke test submission.");

  await page.locator(".contact-form button[type='submit']").click();

  await expect(page.locator(".contact-form__notice.is-success")).toBeVisible({ timeout: 8000 });
  await expect(page.locator("#contact_name")).toHaveValue("");
  await expect(page.locator("#contact_phone")).toHaveValue("");
});
