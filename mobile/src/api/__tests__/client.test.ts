import { clearToken, saveToken } from '../../auth/token';
import { api, ApiError } from '../client';

const fetchMock = jest.fn();

beforeEach(() => {
  fetchMock.mockReset();
  globalThis.fetch = fetchMock as unknown as typeof fetch;
});

function respond(status: number, body: unknown) {
  fetchMock.mockResolvedValueOnce({
    ok: status >= 200 && status < 300,
    status,
    json: async () => {
      if (body === undefined) throw new SyntaxError('no json');
      return body;
    },
  });
}

test('GET courses unwraps data and asks for JSON', async () => {
  respond(200, { data: [{ id: 1, slug: 'sql' }] });

  await expect(api.courses()).resolves.toEqual([{ id: 1, slug: 'sql' }]);

  const [url, init] = fetchMock.mock.calls[0];
  expect(url).toBe('http://api.test/api/courses');
  expect(init.method).toBe('GET');
  expect(init.headers.Accept).toBe('application/json');
  expect(init.body).toBeUndefined();
});

test('slugs are URL-encoded', async () => {
  respond(200, { data: {} });

  await api.lesson('php oop', 'a/b');

  expect(fetchMock.mock.calls[0][0]).toBe('http://api.test/api/courses/php%20oop/lessons/a%2Fb');
});

test('runPhp posts the code as JSON', async () => {
  respond(200, { output: 'hi', fatal: false, durationMs: 3 });

  await expect(api.runPhp(7, 'echo "hi";')).resolves.toEqual({ output: 'hi', fatal: false, durationMs: 3 });

  const [url, init] = fetchMock.mock.calls[0];
  expect(url).toBe('http://api.test/api/lessons/7/run');
  expect(init.method).toBe('POST');
  expect(init.headers['Content-Type']).toBe('application/json');
  expect(JSON.parse(init.body)).toEqual({ code: 'echo "hi";' });
});

test('runSql, answer, checkSql and checkPhp send the right bodies', async () => {
  respond(200, {});
  respond(200, {});
  respond(200, {});
  respond(200, {});

  await api.runSql(1, 'SELECT 1');
  await api.answer(5, 2);
  await api.checkSql(9, 'SELECT 2');
  await api.checkPhp(10, '<?php');

  const calls = fetchMock.mock.calls.map(([url, init]) => [url, JSON.parse(init.body)]);
  expect(calls).toEqual([
    ['http://api.test/api/lessons/1/run', { sql: 'SELECT 1' }],
    ['http://api.test/api/quiz-questions/5/answer', { option: 2 }],
    ['http://api.test/api/challenges/9/check', { sql: 'SELECT 2' }],
    ['http://api.test/api/challenges/10/check', { code: '<?php' }],
  ]);
});

test('a network failure becomes an ApiError with status 0', async () => {
  fetchMock.mockRejectedValueOnce(new TypeError('Network request failed'));

  const error = await api.courses().catch((e) => e);

  expect(error).toBeInstanceOf(ApiError);
  expect(error.status).toBe(0);
  expect(error.message).toBe('Could not reach the server. Check your connection and try again.');
});

test('429 gets the rate-limit message', async () => {
  respond(429, { message: 'Too Many Attempts.' });

  await expect(api.runSql(1, 'SELECT 1')).rejects.toThrow(
    'You are running queries very quickly. Wait a few seconds, then try again.',
  );
});

test('other errors use the server message', async () => {
  respond(422, { message: 'Write some PHP before running it.' });

  const error = await api.runPhp(1, '').catch((e) => e);

  expect(error.status).toBe(422);
  expect(error.message).toBe('Write some PHP before running it.');
});

test('errors without a JSON body name the status', async () => {
  respond(500, undefined);

  await expect(api.courses()).rejects.toThrow('The server responded with an error (500).');
});

test('a saved token goes along as a bearer token', async () => {
  await saveToken('1|secret');
  respond(200, { data: { id: 1, name: 'Ada', email: 'ada@example.com' } });

  await expect(api.user()).resolves.toEqual({ id: 1, name: 'Ada', email: 'ada@example.com' });

  expect(fetchMock.mock.calls[0][1].headers.Authorization).toBe('Bearer 1|secret');
  await clearToken();
});

test('validation errors keep their messages by field', async () => {
  respond(422, { message: 'The email field is required.', errors: { email: ['The email field is required.'] } });

  const error = await api.login({ email: '', password: '', device_name: 'phone' }).catch((e) => e);

  expect(error).toBeInstanceOf(ApiError);
  expect(error.errors).toEqual({ email: ['The email field is required.'] });
});
