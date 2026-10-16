/**
 * The TypeScript compiler the sandbox uses. vite.config.js publishes it, with its lib files, at
 * /vendor/typescript/<version>/ (typescript.js and libs.js), where the web app and the mobile
 * app load it from. It must match the `typescript` version in package.json.
 */
export const TS_VERSION = '6.0.3';
