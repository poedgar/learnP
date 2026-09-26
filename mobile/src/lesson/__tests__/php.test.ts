import { phpCheckMessage, withOpeningTag } from '../php';

test('withOpeningTag adds <?php only when missing', () => {
  expect(withOpeningTag('echo 1;')).toBe('<?php\n\necho 1;');
  expect(withOpeningTag('  <?php echo 1;')).toBe('  <?php echo 1;');
});

const checks = [
  { label: 'a', passed: true, message: null },
  { label: 'b', passed: false, message: 'Nope.' },
];

test('phpCheckMessage matches the web app wording', () => {
  expect(phpCheckMessage({ passed: true, loaded: true, error: null, output: '', checks: [checks[0], checks[0]] })).toBe(
    'Correct! All 2 checks pass.',
  );
  expect(phpCheckMessage({ passed: false, loaded: false, error: 'ParseError', output: '', checks })).toBe(
    'Your code has an error, so the checks could not run yet.',
  );
  expect(phpCheckMessage({ passed: false, loaded: true, error: null, output: '', checks })).toBe(
    "1 of 2 checks pass. See what's missing below.",
  );
});
