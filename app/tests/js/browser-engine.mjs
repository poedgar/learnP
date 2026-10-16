/*
 * The sandbox engine with real Chromium frames, for content jsdom can't check: CSS needs real
 * layout and the cascade. Each frame is a fresh page running frame-runtime.js, sized to the width a
 * check asks for. Uses playwright-core with an installed Chromium: set LEARNP_CHROMIUM to its
 * executable, or install one with `npx playwright install chromium-headless-shell`.
 */
import { existsSync, readdirSync, readFileSync } from 'node:fs';
import { createRequire } from 'node:module';
import { homedir } from 'node:os';
import { join } from 'node:path';
import { chromium } from 'playwright-core';
import { createJsEngine, frameHtml } from '../../resources/js/lib/js/engine.js';

const require = createRequire(import.meta.url);
const source = (path) => readFileSync(new URL(path, import.meta.url), 'utf8');

/** A Chromium executable: LEARNP_CHROMIUM, or the newest one Playwright has installed. */
export function findChromium() {
    if (process.env.LEARNP_CHROMIUM) {
        return existsSync(process.env.LEARNP_CHROMIUM) ? process.env.LEARNP_CHROMIUM : null;
    }
    const caches = [join(homedir(), 'Library/Caches/ms-playwright'), join(homedir(), '.cache/ms-playwright')];
    const candidates = [
        ['chromium_headless_shell-', ['chrome-headless-shell-mac-arm64/chrome-headless-shell', 'chrome-headless-shell-mac-x64/chrome-headless-shell', 'chrome-headless-shell-linux64/chrome-headless-shell']],
        ['chromium-', ['chrome-linux/chrome', 'chrome-linux64/chrome']],
    ];
    for (const cache of caches.filter(existsSync)) {
        for (const [prefix, paths] of candidates) {
            const builds = readdirSync(cache).filter((name) => name.startsWith(prefix)).sort().reverse();
            for (const build of builds) {
                const found = paths.map((path) => join(cache, build, path)).find(existsSync);
                if (found) return found;
            }
        }
    }
    return null;
}

let browser = null;

export async function createBrowserEngine(executablePath) {
    browser ??= await chromium.launch({ executablePath, headless: true });
    const html = frameHtml({
        runtime: source('../../resources/js/lib/js/frame-runtime.js'),
        loopGuard: source('../../resources/js/lib/js/loop-guard.js'),
        acorn: readFileSync(require.resolve('acorn'), 'utf8'),
    });

    async function openFrame({ width = null } = {}) {
        const page = await browser.newPage({ viewport: { width: width ?? 800, height: 600 } });
        let listener = () => {};
        await page.exposeFunction('__learnpSend', (message) => listener(message));
        await page.setContent(html);
        return {
            send: (message) => page.evaluate((m) => window.__learnpReceive(m), message),
            onMessage: (callback) => (listener = callback),
            close: () => page.close().catch(() => {}),
        };
    }

    return createJsEngine({ openFrame });
}

export async function closeBrowser() {
    await browser?.close();
    browser = null;
}
