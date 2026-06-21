const { test, expect } = require("@playwright/test");
const axeScriptPath = require.resolve("axe-core/axe.min.js");
const coreAxeRules = [
  "landmark-complementary-is-top-level",
  "aria-allowed-role",
  "label",
  "link-name",
  "button-name"
];

async function ensureAxe(page) {
  const hasAxe = await page.evaluate(() => Boolean(window.axe));

  if (!hasAxe) {
    await page.addScriptTag({ path: axeScriptPath });
  }
}

async function runAxeRules(page, rules = coreAxeRules) {
  await ensureAxe(page);

  return page.evaluate(async (runOnly) => {
    const results = await axe.run(document, {
      runOnly: {
        type: "rule",
        values: runOnly
      }
    });

    return results.violations.map((violation) => ({
      id: violation.id,
      impact: violation.impact,
      nodes: violation.nodes.map((node) => node.target)
    }));
  }, rules);
}

async function expectNoAxeViolations(page, context, rules = coreAxeRules) {
  const violations = await runAxeRules(page, rules);
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

test("gallery feed renders a five-by-five desktop layout when enough Instagram posts exist", async ({ page }) => {
  await page.setViewportSize({ width: 1440, height: 1200 });
  await page.goto("/gallery/");

  const galleryGrid = page.locator(".gallery-feed__grid").first();
  const galleryCards = page.locator(".gallery-card");

  await expect(galleryCards.first()).toBeVisible();
  await expect(galleryCards).toHaveCount(25);

  const columnCount = await galleryGrid.evaluate((element) => {
    return window
      .getComputedStyle(element)
      .gridTemplateColumns.split(" ")
      .filter(Boolean).length;
  });

  expect(columnCount).toBe(5);
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
  await expect(page.getByText("Our Foundation", { exact: true })).toBeVisible();
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
  await expect(page.locator("#womens-ministry .section-card, #womens-ministry .section-story")).toBeVisible();
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
  await page.locator(".contact-form").evaluate((form) => form.submit());

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

  const themePalette = await page.evaluate(() => {
    const rootStyles = window.getComputedStyle(document.documentElement);
    const primaryAction = document.querySelector(".hero .button");
    const footer = document.querySelector(".site-footer");

    return {
      accent: rootStyles.getPropertyValue("--accent").trim(),
      accentStrong: rootStyles.getPropertyValue("--accent-strong").trim(),
      bg: rootStyles.getPropertyValue("--bg").trim(),
      surfaceDark: rootStyles.getPropertyValue("--surface-dark").trim(),
      primaryActionBackground: primaryAction ? window.getComputedStyle(primaryAction).backgroundColor : null,
      footerBackground: footer ? window.getComputedStyle(footer).backgroundColor : null
    };
  });

  expect(themePalette.accent).toBe("#c73c29");
  expect(themePalette.accentStrong).toBe("#a8331f");
  expect(themePalette.bg).toBe("#f6f3ec");
  expect(themePalette.surfaceDark).toBe("#003955");
  expect(themePalette.primaryActionBackground).toBe("rgb(199, 60, 41)");
  expect(themePalette.footerBackground).toBe("rgb(0, 57, 85)");
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

  if (sermonCount === 0) {
    test.skip(true, "No sermons were rendered.");
  }

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

  if (!foundAudioSermon) {
    test.skip(true, "No sermon with audio was rendered on the first archive page.");
  }

  await expect(page.locator(".audio-player audio[controls][aria-labelledby]")).toBeVisible();
  await expect(page.locator(".audio-player .mejs-container")).toHaveCount(0);
  await expect(page.locator(".audio-player .mejs-horizontal-volume-slider")).toHaveCount(0);
  await expect(page.getByRole("link", { name: "Open audio directly" })).toBeVisible();
  await expectNoAxeViolations(page, page.url());
});

test("footer keeps desktop labels and contact content on stable lines", async ({ page }) => {
  await page.setViewportSize({ width: 1440, height: 1200 });
  await page.goto("/contact-us/");

  const footerStyles = await page.evaluate(() => {
    const link = document.querySelector('.site-footer__column a[href^="mailto:"]');
    const text = document.querySelector(".site-footer__lines");
    const labels = [...document.querySelectorAll(".site-footer__label")];
    const addressBlocks = [...document.querySelectorAll(".site-footer__lines")];
    const locationBlock = document.querySelector(".site-footer__column--visit .site-footer__lines");
    const contactGroup = document.querySelector(".site-footer__contact-group");

    return {
      linkOverflowWrap: link ? getComputedStyle(link).overflowWrap : "",
      linkWordBreak: link ? getComputedStyle(link).wordBreak : "",
      textOverflowWrap: text ? getComputedStyle(text).overflowWrap : "",
      textWordBreak: text ? getComputedStyle(text).wordBreak : "",
      emailRectCount: link ? link.getClientRects().length : 0,
      hasContactGroup: Boolean(contactGroup),
      contactGroupTop: contactGroup ? contactGroup.getBoundingClientRect().top : 0,
      locationBottom: locationBlock ? locationBlock.getBoundingClientRect().bottom : 0,
      labelHeights: labels.map((label) =>
        Math.round(label.getBoundingClientRect().height * 10) / 10
      ),
      addressLineCounts: addressBlocks.map(
        (block) => block.querySelectorAll(".site-footer__line").length
      )
    };
  });

  expect(footerStyles.linkOverflowWrap).not.toBe("anywhere");
  expect(footerStyles.textOverflowWrap).not.toBe("anywhere");
  expect(footerStyles.linkWordBreak).toBe("normal");
  expect(footerStyles.textWordBreak).toBe("normal");
  expect(footerStyles.emailRectCount).toBe(1);
  expect(footerStyles.hasContactGroup).toBe(true);
  expect(footerStyles.contactGroupTop).toBeGreaterThan(footerStyles.locationBottom);
  expect(new Set(footerStyles.labelHeights).size).toBe(1);
  expect(footerStyles.addressLineCounts).toEqual([3, 3]);
});

test("footer stacks brand above metadata at mid-width without wrapping the email link", async ({ page }) => {
  await page.setViewportSize({ width: 1100, height: 1200 });
  await page.goto("/contact-us/");

  const footerLayout = await page.evaluate(() => {
    const brand = document.querySelector(".site-footer__brand");
    const meta = document.querySelector(".site-footer__meta");
    const email = document.querySelector('.site-footer__column a[href^="mailto:"]');
    const labels = [...document.querySelectorAll(".site-footer__label")];
    const explore = document.querySelector(".site-footer__column--explore");
    const visit = document.querySelector(".site-footer__column--visit");
    const mailing = document.querySelector(".site-footer__column--mailing");

    return {
      brandBottom: brand ? brand.getBoundingClientRect().bottom : 0,
      metaTop: meta ? meta.getBoundingClientRect().top : 0,
      metaColumnCount: meta
        ? getComputedStyle(meta).gridTemplateColumns.split(" ").length
        : 0,
      emailRectCount: email ? email.getClientRects().length : 0,
      exploreLeft: explore ? explore.getBoundingClientRect().left : 0,
      exploreRight: explore ? explore.getBoundingClientRect().right : 0,
      visitLeft: visit ? visit.getBoundingClientRect().left : 0,
      visitTop: visit ? visit.getBoundingClientRect().top : 0,
      mailingLeft: mailing ? mailing.getBoundingClientRect().left : 0,
      mailingTop: mailing ? mailing.getBoundingClientRect().top : 0,
      labelHeights: labels.map((label) =>
        Math.round(label.getBoundingClientRect().height * 10) / 10
      )
    };
  });

  expect(footerLayout.metaTop).toBeGreaterThan(footerLayout.brandBottom);
  expect(footerLayout.metaColumnCount).toBe(2);
  expect(footerLayout.emailRectCount).toBe(1);
  expect(footerLayout.visitLeft).toBeGreaterThan(footerLayout.exploreRight);
  expect(Math.abs(footerLayout.mailingLeft - footerLayout.visitLeft)).toBeLessThan(4);
  expect(footerLayout.mailingTop).toBeGreaterThan(footerLayout.visitTop);
  expect(new Set(footerLayout.labelHeights).size).toBe(1);
});

test("key templates pass the stable axe rule set", async ({ page }) => {
  const pages = ["/about-us/", "/worship/", "/events/", "/contact-us/", "/give/"];

  for (const path of pages) {
    await page.goto(path);
    await expectNoAxeViolations(page, path);
  }
});

test("hero and side panels do not expose nested complementary landmarks", async ({ page }) => {
  const pages = ["/", "/worship/", "/give/"];

  for (const path of pages) {
    await page.goto(path);
    const violations = await runAxeRules(page, ["landmark-complementary-is-top-level"]);
    expect(violations, `Unexpected complementary landmark violation on ${path}`).toEqual([]);
  }
});

test("series taxonomy page renders sermon cards", async ({ page }) => {
  await page.goto("/sermons/");

  const seriesLinks = page.locator(".sermon-card .sermon-meta a[href*='/series/']");
  const seriesCount = await seriesLinks.count();

  if (seriesCount === 0) {
    test.skip(true, "No sermons with a series term were rendered on the archive page.");
  }

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

  if ((await firstSermonLink.count()) === 0) {
    test.skip(true, "No sermons were rendered on the archive page.");
  }

  const sermonHref = await firstSermonLink.getAttribute("href");
  await page.goto(sermonHref);

  const speakerCard = page.locator(".single-sermon__meta-card").filter({ hasText: "Preacher" });

  if ((await speakerCard.count()) === 0) {
    test.skip(true, "No speaker term found on the first sermon.");
  }

  const speakerName = await speakerCard.locator("h2").textContent();

  if (!speakerName) {
    test.skip(true, "Speaker name could not be read.");
  }

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
