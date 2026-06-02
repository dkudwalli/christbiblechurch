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

test("mobile navigation opens and submenu controls expand", async ({ page }) => {
  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto("/");

  await page.getByRole("button", { name: "Menu" }).click();
  await expect(page.locator("[data-nav]")).toHaveClass(/is-open/);

  const submenuToggle = page.locator(".site-nav__submenu-toggle").first();

  if ((await submenuToggle.count()) > 0) {
    await submenuToggle.click();
    await expect(submenuToggle).toHaveAttribute("aria-expanded", "true");
    await page.keyboard.press("Escape");
    await expect(submenuToggle).toHaveAttribute("aria-expanded", "false");
  }
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

test("section pages use structured media layouts", async ({ page }) => {
  await page.goto("/about-us/");
  await expect(page.locator("#elder-board .elder-card").first()).toBeVisible();
  await expect(page.locator("#elder-board .section-media-grid")).toHaveCount(0);

  await page.goto("/worship/");
  await expect(page.locator("#womens-ministry .section-visual img")).toBeVisible();
});

test("contact page renders visitor intake fields and a full-width phone field", async ({ page }) => {
  await page.setViewportSize({ width: 1440, height: 1200 });
  await page.goto("/contact-us/");

  await expect(page.locator("#contact_inquiry_type")).toBeVisible();
  await expect(page.getByText("Preferred Contact Method", { exact: true })).toBeVisible();
  await expect(page.getByRole("link", { name: "Email the Church" })).toBeVisible();

  const widths = await page.evaluate(() => {
    const nameField = document.querySelector("#contact_name");
    const phoneField = document.querySelector("#contact_phone");

    return {
      name: nameField?.getBoundingClientRect().width ?? 0,
      phone: phoneField?.getBoundingClientRect().width ?? 0
    };
  });

  expect(widths.phone).toBeGreaterThan(widths.name * 1.5);
});

test("contact form preserves values and enforces phone for phone follow-up", async ({ page }) => {
  await page.goto("/contact-us/");

  await page.locator("#contact_name").fill("Playwright Visitor");
  await page.locator("#contact_email").fill("visitor@example.com");
  await page.locator("#contact_inquiry_type").selectOption("first_visit");
  await page.locator("label[for='contact_preferred_contact_method_phone']").click();
  await page.locator("#contact_message").fill("Testing preserved form state.");
  await page.locator(".contact-form").evaluate((form) => form.submit());

  await expect(page.locator(".contact-form__notice.is-error")).toBeVisible();
  await expect(page.locator("#contact_name")).toHaveValue("Playwright Visitor");
  await expect(page.locator("#contact_email")).toHaveValue("visitor@example.com");
  await expect(page.locator("#contact_inquiry_type")).toHaveValue("first_visit");
  await expect(page.locator("#contact_preferred_contact_method_phone")).toBeChecked();
  await expect(page.locator("#contact_message")).toHaveValue("Testing preserved form state.");
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

    return {
      linkOverflowWrap: link ? getComputedStyle(link).overflowWrap : "",
      linkWordBreak: link ? getComputedStyle(link).wordBreak : "",
      textOverflowWrap: text ? getComputedStyle(text).overflowWrap : "",
      textWordBreak: text ? getComputedStyle(text).wordBreak : "",
      emailRectCount: link ? link.getClientRects().length : 0,
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

    return {
      brandBottom: brand ? brand.getBoundingClientRect().bottom : 0,
      metaTop: meta ? meta.getBoundingClientRect().top : 0,
      metaColumnCount: meta
        ? getComputedStyle(meta).gridTemplateColumns.split(" ").length
        : 0,
      emailRectCount: email ? email.getClientRects().length : 0,
      labelHeights: labels.map((label) =>
        Math.round(label.getBoundingClientRect().height * 10) / 10
      )
    };
  });

  expect(footerLayout.metaTop).toBeGreaterThan(footerLayout.brandBottom);
  expect(footerLayout.metaColumnCount).toBe(3);
  expect(footerLayout.emailRectCount).toBe(1);
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
