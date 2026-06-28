#!/usr/bin/env node
/**
 * Static, content-independent validator for the committed file-based route shims
 * (the Hostinger pretty-URL fallback). Runs in CI with no database — it only reads
 * the committed shim tree and checks structural correctness, catching exactly the
 * drift classes the audit found (a duplicate-encoding sibling directory) plus
 * malformed / wrong-depth / wrong-type shims.
 *
 * Scope: the drift-prone dynamic-content roots sermons/ series/ speaker/. (The
 * static page-route dirs are syntax-checked by `php -l` via lint:php.)
 *
 * For each <root>/.../index.php:
 *   - depth = number of path segments of its directory (e.g. sermons/<slug> = 2).
 *   - The require must wrap __DIR__ in exactly `depth` dirname() calls.
 *   - sermons/* and the series|speaker listing (depth 1) are page-style:
 *       require <dirname^depth>(__DIR__) . '/index.php';   (no boot call)
 *   - series|speaker term shims (depth >= 2) are taxonomy-style:
 *       require <dirname^depth>(__DIR__) . '/taxonomy-route-shim.php';
 *       church_route_shim_boot_taxonomy('<tax>', '<term>'[, <page>]);
 *   - No two sibling directories may collapse to the same slug once percent-decoded.
 */

import { readdirSync, readFileSync, statSync } from "node:fs";
import { join, dirname, resolve } from "node:path";
import { fileURLToPath } from "node:url";

// Defaults to the repo root; an optional CLI arg lets it validate a tree produced
// elsewhere (e.g. `wp church-core regenerate-shims --root=<dir>` output), which is
// how we confirm the regenerator and this validator agree on the shim spec.
const repoRoot = process.argv[2]
  ? resolve(process.cwd(), process.argv[2])
  : join(dirname(fileURLToPath(import.meta.url)), "..");
const ROOTS = ["sermons", "series", "speaker"];
const TAXONOMY_ROOTS = new Set(["series", "speaker"]);

const errors = [];
const error = (rel, msg) => errors.push(`${rel}: ${msg}`);

/** Recursively collect directories that directly contain an index.php. */
function findShimDirs(absDir, relSegments, out) {
  let entries;
  try {
    entries = readdirSync(absDir, { withFileTypes: true });
  } catch {
    return;
  }

  const childDirs = entries.filter((e) => e.isDirectory());

  // Duplicate-sibling check: two child dir names that percent-decode to the same
  // value (e.g. `…-%e2%80%95-…` vs the raw `…-―-…`).
  const decoded = new Map();
  for (const d of childDirs) {
    let key;
    try {
      key = decodeURIComponent(d.name);
    } catch {
      key = d.name;
    }
    if (decoded.has(key)) {
      error(
        relSegments.concat(d.name).join("/"),
        `duplicate sibling shim directory — collapses to the same slug as "${decoded.get(key)}"`
      );
    } else {
      decoded.set(key, d.name);
    }
  }

  if (entries.some((e) => e.isFile() && e.name === "index.php")) {
    out.push(relSegments.slice());
  }

  for (const d of childDirs) {
    findShimDirs(join(absDir, d.name), relSegments.concat(d.name), out);
  }
}

function validateShim(segments) {
  const rel = segments.concat("index.php").join("/");
  const root = segments[0];
  const depth = segments.length;
  let body;
  try {
    body = readFileSync(join(repoRoot, rel), "utf8");
  } catch (e) {
    error(rel, `unreadable: ${e.message}`);
    return;
  }

  if (!/^<\?php\s/.test(body)) {
    error(rel, "does not start with a PHP opening tag");
    return;
  }

  const dirnameCount = (body.match(/dirname\s*\(/g) || []).length;
  if (dirnameCount !== depth) {
    error(rel, `require should wrap __DIR__ in ${depth} dirname() call(s) for its nesting, found ${dirnameCount}`);
  }

  // Is this the listing (page-style) or a taxonomy term shim?
  const isTaxonomyTerm = TAXONOMY_ROOTS.has(root) && depth >= 2;
  const requiresIndex = /require\s+[^;]*'\/index\.php'\s*;/.test(body);
  const requiresTaxShim = /require\s+[^;]*'\/taxonomy-route-shim\.php'\s*;/.test(body);
  const bootCall = body.match(
    /church_route_shim_boot_taxonomy\(\s*'([^']+)'\s*,\s*'([^']+)'\s*(?:,\s*(\d+)\s*)?\)/
  );

  if (isTaxonomyTerm) {
    if (!requiresTaxShim) {
      error(rel, "taxonomy term shim must require '/taxonomy-route-shim.php'");
    }
    if (!bootCall) {
      error(rel, "taxonomy term shim must call church_route_shim_boot_taxonomy()");
      return;
    }
    const [, tax, slug, page] = bootCall;
    if (tax !== root) {
      error(rel, `boot taxonomy "${tax}" does not match its directory root "${root}"`);
    }
    // segments: [tax, term] or [tax, term, 'page', N]
    const expectedTerm = segments[1];
    if (decodeOrSelf(slug) !== decodeOrSelf(expectedTerm)) {
      error(rel, `boot term "${slug}" does not match its directory "${expectedTerm}"`);
    }
    const isPagination = depth === 4 && segments[2] === "page";
    if (isPagination) {
      if (page !== segments[3]) {
        error(rel, `boot page "${page ?? "(none)"}" does not match directory page "${segments[3]}"`);
      }
    } else if (depth === 2) {
      if (page !== undefined) {
        error(rel, "non-paginated term shim should not pass a page argument");
      }
    } else {
      error(rel, `unexpected taxonomy shim nesting depth ${depth}`);
    }
  } else {
    if (!requiresIndex) {
      error(rel, "page-style shim must require '/index.php'");
    }
    if (requiresTaxShim || bootCall) {
      error(rel, "page-style shim must not boot a taxonomy");
    }
  }
}

function decodeOrSelf(s) {
  try {
    return decodeURIComponent(s);
  } catch {
    return s;
  }
}

for (const root of ROOTS) {
  const absRoot = join(repoRoot, root);
  try {
    statSync(absRoot);
  } catch {
    continue; // root not present — nothing to validate
  }
  const dirs = [];
  findShimDirs(absRoot, [root], dirs);
  for (const segments of dirs) {
    validateShim(segments);
  }
}

if (errors.length > 0) {
  console.error(`Route-shim validation failed (${errors.length} issue(s)):`);
  for (const e of errors) {
    console.error(`  - ${e}`);
  }
  process.exit(1);
}

console.log("Route-shim validation passed.");
