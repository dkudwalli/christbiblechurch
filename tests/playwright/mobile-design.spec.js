const { test, expect } = require('@playwright/test');
const axePath = require.resolve('axe-core/axe.min.js');
const pages = ['/', '/about-us/', '/worship/', '/sermons/', '/events/', '/contact-us/', '/gallery/', '/give/'];

test('mobile controls remain comfortable to tap and text fields avoid focus zoom', async ({ page }) => {
  await page.goto('/');
  const header = await page.locator('.site-header').boundingBox();
  expect(header.height).toBeLessThanOrEqual(72);
  await page.goto('/contact-us/');
  for (const field of ['#contact_name', '#contact_email', '#contact_phone', '#contact_message']) {
    expect(await page.locator(field).evaluate(el => parseFloat(getComputedStyle(el).fontSize))).toBeGreaterThanOrEqual(16);
  }
  const undersized = await page.locator('.site-footer__nav a, .site-footer__contact-group a, .site-footer__social, .button, button[type=submit]').evaluateAll(els => els.filter(el => el.getBoundingClientRect().height < 48).map(el => el.textContent.trim()));
  expect(undersized).toEqual([]);
});

test('public pages reflow across phone, tablet, and desktop widths', async ({ page }) => {
  test.setTimeout(90000);
  for (const width of [320, 430, 768, 1440]) {
    await page.setViewportSize({ width, height: 900 });
    for (const route of pages) {
      await page.goto(route);
      expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), `${route} at ${width}px`).toBe(true);
    }
  }
});

test('mobile pages have no automated WCAG A or AA violations', async ({ page }) => {
  test.setTimeout(90000);
  for (const route of pages) {
    await page.goto(route);
    await page.addScriptTag({ path: axePath });
    const violations = await page.evaluate(async () => (await axe.run(document, { runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'] } })).violations.map(v => ({ id: v.id, targets: v.nodes.map(n => n.target) })));
    expect(violations, route).toEqual([]);
  }
});

test('enlarged text and landscape lightbox retain reachable controls', async ({ page }) => {
  await page.goto('/contact-us/');
  await page.addStyleTag({ content: 'html { font-size: 200%; }' });
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1)).toBe(true);
  await page.locator('#contact_message').focus();
  await expect(page.locator('#contact_message')).toBeFocused();
  await page.setViewportSize({ width: 844, height: 390 });
  await page.goto('/photo-albums/church-retreat-2024/');
  await page.locator('.js-lightbox').first().click();
  const close = page.getByRole('button', { name: 'Close', exact: true });
  await expect(close).toBeInViewport();
  const box = await close.boundingBox();
  expect(box.y).toBeGreaterThanOrEqual(0);
  expect(box.height).toBeGreaterThanOrEqual(48);
  await close.click();
  await expect(page.locator('dialog.lightbox')).not.toBeVisible();
});

test('mobile visitors can scroll past navigation with JavaScript unavailable', async ({ browser }) => {
  const context = await browser.newContext({ javaScriptEnabled: false, viewport: { width: 390, height: 844 }, isMobile: true });
  const page = await context.newPage();
  await page.goto('/');
  await expect(page.locator('.site-nav a').first()).toBeVisible();
  await page.locator('h1').scrollIntoViewIfNeeded();
  const headingIsUncovered = await page.locator('h1').evaluate(el => {
    const rect = el.getBoundingClientRect();
    const hit = document.elementFromPoint(rect.left + rect.width / 2, rect.top + rect.height / 2);
    return hit === el || el.contains(hit);
  });
  expect(headingIsUncovered).toBe(true);
  await context.close();
});

test('tablet section links reveal their headings below sticky navigation', async ({ page }) => {
  await page.setViewportSize({ width: 768, height: 844 });
  await page.goto('/about-us/');
  await page.emulateMedia({ reducedMotion: 'reduce' });
  await page.locator('.section-nav__list a[href="#missions"]').click();
  const heading = page.locator('#missions h2').first();
  await expect.poll(async () => heading.evaluate(el => {
    const box = el.getBoundingClientRect();
    const point = document.elementFromPoint(box.left + box.width / 2, box.top + box.height / 2);
    return point === el || el.contains(point);
  })).toBe(true);
});

test('a slow navigation script does not expand the initial mobile header', async ({ page }) => {
  let releaseScript;
  const scriptGate = new Promise(resolve => { releaseScript = resolve; });
  await page.route('**/assets/js/site.js*', async route => {
    await scriptGate;
    await route.continue();
  });
  try {
    await page.goto('/', { waitUntil: 'commit' });
    await expect(page.locator('h1')).toBeVisible();
    await expect(page.locator('[data-nav]')).not.toBeVisible();
  } finally {
    releaseScript();
  }
  await page.waitForLoadState();
  await page.locator('[data-nav-toggle]').click();
  await expect(page.locator('[data-nav]')).toBeVisible();
});

test('failed navigation script restores usable menu and section links', async ({ page }) => {
  await page.route('**/assets/js/site.js*', route => route.abort());
  await page.goto('/about-us/');
  await expect(page.locator('[data-nav]')).toBeVisible();
  await expect(page.locator('[data-nav-toggle]')).not.toBeVisible();
  await expect(page.locator('.section-nav__list a').first()).toBeVisible();
  await page.locator('h1').scrollIntoViewIfNeeded();
  expect(await page.locator('.site-header').evaluate(el => getComputedStyle(el).position)).toBe('static');
});
