# Mobile-first refresh

Approved direction: serve newcomers and regular attendees equally; retain logo, navy/cream/rust palette and local fonts; compact header and homepage Visit/Sermons/Events shortcuts.

Implementation sequence:
1. Shared tokens, mobile-first spacing, restrained surfaces and navigation.
2. Homepage: welcome/time/venue/directions, shortcuts, photo, latest sermon, next events, compact mission/values, contact. Contact: direct actions/details, form, map, office/mailing. Worship: practical visit details before long content.
3. Expandable mobile section navigation and sermon filters, shared inner-page layouts, accessibility and browser verification.

Compatibility: retain URLs, Customizer settings, menus, content, forms, media behavior and data model. No payment integration or database migration. No production deployment in this task.

Acceptance: 48px standalone controls; 16px+ inputs; 320px reflow; mobile Chromium/WebKit, desktop and landscape checks; reduced motion, no-JS content/navigation, keyboard and filter state; existing lint and smoke suite. Real Android/iPhone, TalkBack/VoiceOver, staging content and field performance remain release checks when devices/staging are available.

## Work log
- Initial tree clean on crossroads. Work on design/mobile-first-refresh using existing local Docker mount, so browser checks exercise actual edited theme without duplicating the database.
- Graph church generation 2026-09-12T17:37:54Z has stale/partial PHP template coverage and excluded frontend assets. Direct source is authoritative for these changes.
- Ownership: page templates worker owns front-page/contact/worship only; interaction worker owns header/section-nav/archive-sermon/site.js and dedicated tests; root owns CSS, general mobile tests, integration and review. Shared class contracts coordinated in messages.

## Implemented behavior

- Shared mobile-first spacing, navy headings, restrained cream/white surfaces, locally hosted fonts, 48px primary/menu/disclosure/footer action targets, responsive images and a shorter homepage.
- Homepage directions and Visit/Sermons/Events shortcuts precede the community photograph. Configured hero invitation remains available. Sermons and upcoming events now precede the compact mission/vision/values summary.
- Contact and Worship lead with gathering details and directions. Existing form and content sources remain intact. The contact map retains a nearby external directions fallback.
- Compact navigation hides closed links from keyboard access, supports Escape and short landscape viewports, and remains usable without JavaScript or if its script fails to download. Initial enhancement styles prevent the fallback menu and mobile disclosures from jumping during slow script loads. Mobile section links and optional sermon filters use native disclosures; active filters remain expanded.
- Desktop section navigation uses its measured height for anchor offsets. Tablet section navigation and no-JavaScript compact headers stay in document flow so they cannot cover content.
- Photo viewer close controls remain on-screen in landscape. Breadcrumb links remain identifiable beyond color. Reduced-motion and no-JavaScript content remain readable.

## Verification — 2026-09-13

- Complete Playwright run: **103 passed** across desktop Chromium and mobile Chromium/WebKit at 360px and 390px. Includes public-page WCAG A/AA checks, reflow at 320/430/768/1440px, menu/disclosure behavior, slow and failed script downloads, gallery, contact state, sermon playback/filtering and route regressions.
- PHP 8.2 syntax, PHPCS, Stylelint, JavaScript syntax and `git diff --check` passed. PHP ran using the existing WordPress Docker image because the host has no PHP executable.
- WebKit ran in the matching Playwright v1.60.0 Ubuntu container because Fedora lacks the downloaded WebKit runtime libraries. CI now installs Chromium and WebKit.
- Independent code review found three issues (no-JavaScript sticky-header occlusion, custom-CTA suppression, wrapped section-nav occlusion); all were reproduced with failing tests, fixed, and accepted on re-review. Additional desktop anchor checks at 200% text passed. Throttled startup exposed a menu layout shift; an early enhancement marker fixed it, with a script-error fallback verified in a further scoped review.

Reproduce the complete browser run locally with the existing running WordPress site:

```bash
docker run --rm --user "$(id -u):$(id -g)" --network host \
  -v "$PWD":/work -w /work mcr.microsoft.com/playwright:v1.60.0-noble \
  npx playwright test --output=/tmp/church-verification
```

On a supported host with the browser dependencies installed, use `npm run test:smoke` and `npm run lint` normally.

## Local performance sample

Same Chromium viewport (390×844), 4× CPU throttling, 150ms latency, 200,000 bytes/s download and 93,750 bytes/s upload:

| Metric | Before | After |
| --- | --- | --- |
| Homepage document height | 5,923px | 3,962px |
| Largest Contentful Paint | 1,516ms | 856ms |
| Cumulative Layout Shift | 0 | 0 |

The homepage shortcut row ends at 630px, within the initial 844px viewport. These are individual local lab samples using the seeded content, not field measurements or a production performance guarantee.

## Release checks

The changes are local on `design/mobile-first-refresh`; no production deployment was performed. Before release, check representative staging content, real Android Chrome and iPhone Safari (including the software keyboard), TalkBack and VoiceOver, and actual external map/video behavior. Lab results do not establish real-user INP or production Core Web Vitals. Deploy through the existing theme process and retain the previous theme package for rollback; there is no data migration.
