const { test, expect } = require("@playwright/test");

const publicPages = [
  "/",
  "/about-us/",
  "/worship/",
  "/gallery/",
  "/contact-us/",
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

test("contact form preserves values on invalid submission", async ({ page }) => {
  await page.goto("/contact-us/");

  await page.locator("#contact_name").fill("Playwright Visitor");
  await page.locator("#contact_email").fill("invalid-email");
  await page.locator("#contact_message").fill("Testing preserved form state.");
  await page.locator(".contact-form").evaluate((form) => form.submit());

  await expect(page.locator(".contact-form__notice.is-error")).toBeVisible();
  await expect(page.locator("#contact_name")).toHaveValue("Playwright Visitor");
  await expect(page.locator("#contact_message")).toHaveValue("Testing preserved form state.");
});
