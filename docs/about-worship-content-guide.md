# About Us & Worship — content guide

The P2 update added typographic breathing room (larger section text, more padding, a
pull-quote style) to the About Us and Worship pages via CSS. The remaining density
improvements need **content** you author in WordPress — this checklist covers them.

Each section on About Us and Worship is a **child page** (Admin → Pages). The "Section
Presentation" meta box on each child page sets its layout: `default`, `feature`, or
`elder_board`.

## 1. Give some sections a feature image (`feature` layout)

The `feature` layout shows an image beside the text instead of a plain card — the
single biggest way to break up the wall of cards.

For a child page:
1. Set a **Featured image** (Admin → edit the child page → Featured image panel). Use a
   landscape photo, ~1200px wide.
2. In **Section Presentation**, choose **Feature (image + content)**.

Good candidates (alternate them so the page rhythm changes every few sections):
- **About Us:** Beliefs, Mission, Missions, Membership
- **Worship:** Corporate Worship, Kids Ministry, a ministry with a strong photo

Leave text-only sections (e.g. Governance, Stewardship) on `default`.

## 2. Add a pull-quote to break up long sections

A `<blockquote>` inside a section now renders as a styled pull-quote (gold rule, italic,
larger). In the block editor, add a **Quote block** with a short, punchy line pulled from
the section — e.g. a one-line summary of a belief or value. One per long section is plenty.

## 3. Break paragraphs with subheadings and lists

Where a section is several long paragraphs:
- Add **Heading (H3)** blocks to chunk it into scannable parts.
- Convert dense "we believe X, Y, and Z" prose into **bulleted lists** where it reads as a list.

## Quick checklist per page
- [ ] 2–3 sections switched to `feature` layout with a featured image set
- [ ] 1 pull-quote (Quote block) added to each long section
- [ ] Long paragraphs broken with H3 subheadings / lists
- [ ] Re-check on mobile (390px) after editing
