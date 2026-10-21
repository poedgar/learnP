const { existsSync, readFileSync } = require('node:fs');
const { join } = require('node:path');

/** The runtime is shared with the web app; scripts/sync-js-runtime.mjs keeps these copies current. */
const web = join(__dirname, '../../../../app/resources/js/lib/js');
const here = join(__dirname, '..');
const run = existsSync(web) ? test : test.skip;

run.each(['prepare.js', 'loop-guard.js', 'engine.js', 'ts-version.js'])('%s matches the web app (run npm run sync-js-runtime)', (name) => {
  expect(readFileSync(join(here, name), 'utf8')).toBe(readFileSync(join(web, name), 'utf8'));
});

run.each(['git-sim.js', 'engine.js'])('git/%s matches the web app (run npm run sync-js-runtime)', (name) => {
  expect(readFileSync(join(here, '../git', name), 'utf8')).toBe(readFileSync(join(web, '../git', name), 'utf8'));
});

run('sources.ts matches the web app (run npm run sync-js-runtime)', () => {
  const sources = readFileSync(join(here, 'sources.ts'), 'utf8');
  expect(sources).toContain(JSON.stringify(readFileSync(join(web, 'frame-runtime.js'), 'utf8')));
  expect(sources).toContain(JSON.stringify(readFileSync(join(web, 'ts-service.js'), 'utf8')));
  expect(sources).toContain(JSON.stringify(readFileSync(join(web, 'loop-guard.js'), 'utf8')));
});
