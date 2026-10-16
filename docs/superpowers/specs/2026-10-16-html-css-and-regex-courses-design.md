# HTML & CSS and Regular expressions courses

Two courses from the "Web" list in `mobile/README.md`, both running on the learner's device in the
existing JavaScript sandbox engine (`app/resources/js/lib/js`).

## HTML & CSS (`html-css`, language `html`, position 11)

Seven lessons: Structure and meaning; Lists, tables and figures; Forms; Selectors and the cascade;
The box model; Flexbox; Grid and responsive design. Each has 8 quiz questions and 6 challenges.

The engine gains an `html` language:

- `compile()` passes the code through as `html`; `frame-runtime.js` installs it as the page
  (parsed with `DOMParser`, `<script>` tags inert, the sandbox's base style removed), so the
  learner's `<style>` applies exactly as written.
- Checks are JavaScript run in that page. New helpers: `styleOf(selector, property, pseudo?)`
  returns the computed value (colours as `rgb(...)`), `boxOf(selector)` the rounded
  `{ left, top, width, height, right, bottom }`. A missing selector gives a readable message.
- Hidden check frames are 800×600. A check may set `width`: checks are grouped by width and each
  group runs in a frame that wide, so media queries can be tested at phone and desktop sizes.
- The web sandbox re-runs the page as you type (debounced); the mobile app runs it on **Run**. The
  output panel stays hidden unless the page logs something.
- The server answers 422 for run/check requests, as for JavaScript.

Editors get CodeMirror's HTML mode (with embedded CSS); the highlighter learns HTML and CSS; the
mobile key bar gets `< >`, `</` and `=" "`.

## Regular expressions (`regex`, language `javascript`, position 16)

Six lessons in JavaScript (chosen over PHP's `preg_*` so it runs offline on the device):
Patterns and character classes; Quantifiers and anchors; Groups and alternation; Searching and
replacing; Lookarounds and Unicode; Regex in practice. No engine changes.

## Verification

`tests/js/verify-content.mjs` checks every example, quiz snippet, solution and starter. jsdom has
no layout or media queries, so HTML lessons run in a headless Chromium through `playwright-core`
(`tests/js/browser-engine.mjs`); without one they're reported as `skipped`, and
`JsLessonContentTest` marks itself incomplete. The challenges were also checked through the web UI
in a browser (all 78 pass), and the HTML theory examples in the live preview.
