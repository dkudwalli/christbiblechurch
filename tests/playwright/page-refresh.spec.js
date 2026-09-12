const { test, expect } = require("@playwright/test");

test("mobile homepage puts visit essentials and primary shortcuts first", async ({ page }) => {
  await page.goto("/");

  const visit = page.locator(".home-visit");
  const shortcuts = page.locator(".home-shortcuts");

  await expect(visit).toBeVisible();
  await expect(visit.getByRole("link", { name: "Get Directions" })).toBeVisible();
  await expect(shortcuts.getByRole("link", { name: "Visit" })).toBeVisible();
  await expect(shortcuts.getByRole("link", { name: "Sermons" })).toBeVisible();
  await expect(shortcuts.getByRole("link", { name: "Events" })).toBeVisible();

  const shortcutBottom = await shortcuts.evaluate((element) => element.getBoundingClientRect().bottom);
  expect(shortcutBottom).toBeLessThanOrEqual(844);

  const order = await page.evaluate(() => {
    const visitSection = document.querySelector(".home-visit");
    const photo = document.querySelector(".hero__media");
    return visitSection && photo
      ? Boolean(visitSection.compareDocumentPosition(photo) & Node.DOCUMENT_POSITION_FOLLOWING)
      : false;
  });
  expect(order).toBe(true);
});

test("homepage uses a linked sermon preview and keeps supporting sections concise", async ({ page }) => {
  await page.goto("/");

  const sermon = page.locator(".sermon-feature");
  await expect(sermon).toBeVisible();
  await expect(sermon.locator("iframe")).toHaveCount(0);
  await expect(sermon.locator(".sermon-feature__preview")).toHaveAttribute("href", /.+/);
  await expect(page.getByText("More sermons", { exact: true })).toHaveCount(0);
  const eventCount = await page.locator(".event-grid > *").count();
  expect(eventCount).toBeGreaterThan(0);
  expect(eventCount).toBeLessThanOrEqual(3);
  await expect(page.locator(".home-mission")).toBeVisible();
});

test("contact and worship pages lead with practical visit information", async ({ page }) => {
  await page.goto("/contact-us/");

  const contactDetails = page.locator(".contact-panel--details");
  await expect(contactDetails.getByRole("link", { name: "Get Directions" })).toBeVisible();
  await expect(contactDetails.getByRole("link", { name: /Call/i })).toBeVisible();
  await expect(contactDetails.getByRole("link", { name: /Email/i })).toBeVisible();
  await expect(page.locator(".contact-panel--form")).toBeVisible();
  await expect(page.locator(".map-frame iframe, .map-frame__placeholder")).toBeVisible();

  await page.goto("/worship/");
  const gathering = page.locator(".worship-gathering");
  await expect(gathering).toBeVisible();
  await expect(gathering.getByRole("link", { name: "Get Directions" })).toBeVisible();

  const gatheringBeforeIntroduction = await page.evaluate(() => {
    const practical = document.querySelector(".worship-gathering");
    const introduction = document.querySelector(".worship-introduction");
    return practical && introduction
      ? Boolean(practical.compareDocumentPosition(introduction) & Node.DOCUMENT_POSITION_FOLLOWING)
      : false;
  });
  expect(gatheringBeforeIntroduction).toBe(true);
});

test('homepage retains the configured visit invitation', async ({ page }) => {
  await page.goto('/');
  const invitation = page.locator('.home-hero__custom-cta');
  await expect(invitation).toHaveText('Plan Your Visit');
  await expect(invitation).toHaveAttribute('href', /\/contact-us\/$/);
});
