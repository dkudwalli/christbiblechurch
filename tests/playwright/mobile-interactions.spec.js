const { test, expect } = require("@playwright/test");

test("compact menu labels its control and removes closed links from focus", async ({ page }) => {
  await page.goto("/");

  const toggle = page.locator("[data-nav-toggle]");
  const nav = page.locator("[data-nav]");

  await expect(toggle).toContainText("Menu");
  await expect(toggle).toHaveAccessibleName("Open main menu");
  await expect(nav).toHaveAttribute("hidden", "");

  await toggle.click();
  await expect(toggle).toHaveAccessibleName("Close main menu");
  await expect(toggle).toHaveAttribute("aria-expanded", "true");
  await expect(nav).not.toHaveAttribute("hidden", "");

  await page.keyboard.press("Escape");
  await expect(nav).toHaveAttribute("hidden", "");
  await expect(toggle).toBeFocused();
});

test("menu closes when switching to desktop and when following a same-page link", async ({ page }) => {
  await page.goto("/");

  const toggle = page.locator("[data-nav-toggle]");
  const nav = page.locator("[data-nav]");
  await toggle.click();
  await page.setViewportSize({ width: 1200, height: 800 });
  await expect(nav).not.toHaveAttribute("hidden", "");
  await expect(toggle).toHaveAttribute("aria-expanded", "false");

  await page.setViewportSize({ width: 390, height: 844 });
  await toggle.click();
  await page.evaluate(() => {
    const link = document.querySelector("[data-nav] a");
    link.setAttribute("href", "#main-content");
    link.click();
  });
  await expect(nav).toHaveAttribute("hidden", "");
});

test("section navigation is a mobile disclosure and stays expanded on desktop", async ({ page }) => {
  await page.goto("/about-us/");

  const disclosure = page.locator(".section-nav__disclosure");
  await expect(page.getByText("On this page", { exact: true })).toBeVisible();
  await expect(disclosure).not.toHaveAttribute("open", "");
  await page.getByText("On this page", { exact: true }).click();
  await expect(disclosure).toHaveAttribute("open", "");
  await expect(page.locator(".section-nav__list a").first()).toBeVisible();

  await page.setViewportSize({ width: 1000, height: 800 });
  await expect(disclosure).toHaveAttribute("open", "");
});

test("sermon search remains visible while optional filters collapse on mobile", async ({ page }) => {
  await page.goto("/sermons/");

  await expect(page.getByRole("searchbox")).toBeVisible();
  const disclosure = page.locator(".filter-bar__more");
  await expect(page.getByText("More filters", { exact: true })).toBeVisible();
  await expect(disclosure).not.toHaveAttribute("open", "");

  await page.getByText("More filters", { exact: true }).click();
  await expect(page.getByLabel("Speaker")).toBeVisible();
  await expect(page.getByLabel("Series")).toBeVisible();
});

test("active taxonomy filters keep the mobile filter disclosure open", async ({ page }) => {
  await page.goto("/sermons/");
  const speakerSlug = await page.locator("select[name='speaker'] option").evaluateAll((options) => {
    return options.map((option) => option.value).find(Boolean);
  });
  expect(speakerSlug, "Seeded sermon archive should contain a speaker").toBeTruthy();
  await page.goto(`/sermons/?speaker=${encodeURIComponent(speakerSlug)}`);
  await expect(page.locator(".filter-bar__more")).toHaveAttribute("open", "");
});

test("filter submit state is restored on pageshow", async ({ page }) => {
  await page.goto("/sermons/");
  const submit = page.getByRole("button", { name: "Filter" });

  await page.evaluate(() => {
    const button = document.querySelector(".filter-bar [type='submit']");
    button.disabled = true;
    button.setAttribute("aria-busy", "true");
    window.dispatchEvent(new PageTransitionEvent("pageshow", { persisted: true }));
  });

  await expect(submit).toBeEnabled();
  await expect(submit).not.toHaveAttribute("aria-busy", "true");
});

test("measured header row remains stable and follows the compact breakpoint", async ({ page }) => {
  await page.setViewportSize({ width: 1200, height: 800 });
  await page.goto("/");

  const measureAcrossFrames = () => page.evaluate(async () => {
    const header = document.querySelector(".site-header");
    const samples = [];
    await new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve)));
    for (let frame = 0; frame < 8; frame += 1) {
      await new Promise((resolve) => requestAnimationFrame(resolve));
      samples.push({
        height: header.getBoundingClientRect().height,
        row: parseFloat(getComputedStyle(document.documentElement).getPropertyValue("--header-row-height")),
      });
    }
    return samples;
  });

  const desktop = await measureAcrossFrames();
  expect(Math.max(...desktop.map(({ height }) => height)) - Math.min(...desktop.map(({ height }) => height))).toBeLessThan(1);
  expect(Math.max(...desktop.map(({ row }) => row)) - Math.min(...desktop.map(({ row }) => row))).toBeLessThan(1);

  await page.setViewportSize({ width: 390, height: 844 });
  const mobile = await measureAcrossFrames();
  expect(Math.max(...mobile.map(({ height }) => height)) - Math.min(...mobile.map(({ height }) => height))).toBeLessThan(1);
  expect(Math.max(...mobile.map(({ row }) => row)) - Math.min(...mobile.map(({ row }) => row))).toBeLessThan(1);
  expect(mobile.at(-1).row).toBeLessThan(desktop.at(-1).row);
});

test("compact navigation scrolls within a short landscape viewport", async ({ page }) => {
  await page.setViewportSize({ width: 720, height: 240 });
  await page.goto("/");
  await page.locator("[data-nav-toggle]").click();

  const nav = page.locator("[data-nav]");
  const bounds = await nav.evaluate((element) => {
    const rect = element.getBoundingClientRect();
    return {
      bottom: rect.bottom,
      overflowY: getComputedStyle(element).overflowY,
    };
  });
  expect(bounds.bottom).toBeLessThanOrEqual(240);
  expect(bounds.overflowY).toBe("auto");

  const lastLink = nav.locator("a").last();
  await lastLink.scrollIntoViewIfNeeded();
  await expect(lastLink).toBeVisible();
});

test("desktop disclosures remain usable without JavaScript", async ({ browser }) => {
  const context = await browser.newContext({
    javaScriptEnabled: false,
    viewport: { width: 1200, height: 800 },
  });
  const page = await context.newPage();

  await page.goto("/about-us/");
  await expect(page.locator(".section-nav__disclosure")).toHaveAttribute("open", "");
  await expect(page.locator(".section-nav__list a").first()).toBeVisible();

  await page.goto("/sermons/");
  await expect(page.locator(".filter-bar__more")).toHaveAttribute("open", "");
  await expect(page.getByLabel("Speaker")).toBeVisible();
  await context.close();
});
