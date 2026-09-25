// Stand-in for the PHP versions the sandbox doesn't use, so their ~20 MB
// WebAssembly binaries stay out of the build (see vite.config.js).
export function getPHPLoaderModule() {
    throw new Error('This PHP version is not bundled with learnP.');
}
