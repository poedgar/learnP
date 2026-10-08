import { KEYS } from '../keys';

test('PHP keys include the symbols phone keyboards hide', () => {
  expect(KEYS.php.map((k) => k.label)).toEqual(['Tab', '$', '->', ';', '{ }', '( )', '[ ]', "' '", '=>']);
});

test('SQL keys', () => {
  expect(KEYS.sql.map((k) => k.label)).toEqual(['Tab', '*', ';', '( )', "' '", ',', '=']);
});

test('paired keys put the cursor between the pair', () => {
  for (const key of [...KEYS.php, ...KEYS.sql]) {
    if (['{}', '()', '[]', "''"].includes(key.text)) {
      expect(key.cursorOffset).toBe(1);
    } else {
      expect(key.cursorOffset).toBe(key.text.length);
    }
  }
});

test('Tab inserts four spaces', () => {
  expect(KEYS.php[0]).toMatchObject({ text: '    ', cursorOffset: 4 });
});
