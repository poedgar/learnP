# learnP mobile

The learnP courses on iOS and Android, built with Expo (SDK 57) and Expo Router. It uses the same
API as the web app (`../app`). SQL and PHP both run on the server; PHP runs on the separate runner
(`../runner`).

## Running it on your phone

1. Install **Expo Go** on your phone. Your phone and computer must be on the same Wi-Fi.
2. Start the API so the phone can reach it:
   ```sh
   cd ../app && php artisan serve --host=0.0.0.0 --port=8000
   ```
   For PHP lessons, also start the runner (see `../runner/README.md`) and set `RUNNER_URL` and
   `RUNNER_SECRET` in `../app/.env`.
3. Point the app at your computer's LAN address (find it with `ipconfig getifaddr en0` on macOS):
   ```sh
   cp .env.example .env   # then edit EXPO_PUBLIC_API_URL, e.g. http://192.168.1.20:8000
   ```
4. Start Expo and scan the QR code with the camera (iOS) or Expo Go (Android):
   ```sh
   export PATH="$HOME/.nvm/versions/node/v24.13.0/bin:$PATH"
   npm install
   npx expo start
   ```

Changing `.env` needs a restart of `npx expo start`.

## How it's built

- `app/`: routes (`/`, `/courses/[course]`, `/courses/[course]/lessons/[lesson]?step=…`). Deep links use the scheme `learnp://`.
- `src/api`: typed client. Its error messages match the web app's `api.js`.
- `src/progress`: progress stored on the device (AsyncStorage key `learnp:progress:v2`). There are no accounts.
- `src/components/markdown`: lesson Markdown tokenised with `marked` and drawn as native views, with code highlighted by Prism.
- `src/editor`: CodeMirror 6 in an Expo DOM component (`'use dom'`), with a native key bar above the keyboard.
  - The editor only reports text; the app replaces it by bumping `resetKey`, so typing is never overwritten.

## Tests

```sh
npm test
npm run typecheck
```

Jest replaces the WebView editor with a `TextInput` (`src/editor/__mocks__`).

## Manual checklist (Expo Go, on a real phone)

Run through this on one iPhone and one Android phone before a release.

- [ ] The course list loads. With the API stopped, you see "Could not reach the server…", and **Try again** works once the API is back.
- [ ] Opening a course shows its lessons; opening a lesson lands on **Learn**.
- [ ] Learn:
  - [ ] Next and Previous change sections.
  - [ ] Tables scroll sideways.
  - [ ] **Try it in the sandbox** opens the Sandbox with that code and runs it.
- [ ] Sandbox (SQL):
  - [ ] **Show tables** and **Preview** work.
  - [ ] Running shows rows.
  - [ ] An error shows the statement number.
  - [ ] **Restore example** works.
- [ ] Sandbox (PHP): echo output shows. With the runner stopped you see "The PHP sandbox is unavailable. Try again shortly." and your code stays.
- [ ] Editor:
  - [ ] Typing fast (hold a key, paste a long snippet) loses no characters.
  - [ ] The key bar appears above the keyboard, inserts `$`, `->` and `{}`, and paired keys put the cursor inside.
  - [ ] The keyboard stays open while you use the key bar.
  - [ ] The editor stays visible while typing.
- [ ] Switching Learn → Sandbox → Quiz → Sandbox keeps the Sandbox code.
- [ ] Quiz:
  - [ ] Answers show Correct/Not quite and the explanation.
  - [ ] The numbers turn green and red.
  - [ ] **Retake the quiz** clears answers.
- [ ] Challenges:
  - [ ] **Check answer** shows the verdict and details.
  - [ ] **Next challenge** works.
  - [ ] Solved challenges show ✓.
  - [ ] **Start over** works.
- [ ] Progress survives closing and reopening the app. The course screen shows "Completed." for a finished lesson.
- [ ] Dark mode (switch the phone's appearance) keeps everything readable, including the editor.
- [ ] `npx uri-scheme open "learnp://courses/sql/lessons/your-first-queries?step=quiz" --ios` (or tapping such a link) opens the quiz directly.
