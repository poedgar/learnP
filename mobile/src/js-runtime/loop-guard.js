/*
 * Adds a call to __learnpGuard() at the start of every loop body, so a loop that runs too long
 * can be stopped (see frame-runtime.js). Used by prepare.js, and injected as source text into
 * sandbox frames (with acorn) so runVariant() can guard the code it runs too. So guardLoops()
 * must stay self-contained: no imports, and nothing from outside the function.
 *
 * `parse(code)` must return an acorn AST of `code` with start and end offsets. Insertions add no
 * line breaks, so line numbers stay the same.
 */
export function guardLoops(parse, code) {
    const loops = ['ForStatement', 'ForInStatement', 'ForOfStatement', 'WhileStatement', 'DoWhileStatement'];
    const inserts = [];

    (function walk(node) {
        if (!node || typeof node.type !== 'string') return;
        if (loops.includes(node.type)) {
            const body = node.body;
            if (body.type === 'BlockStatement') {
                inserts.push([body.start + 1, '__learnpGuard();']);
            } else {
                inserts.push([body.start, '{__learnpGuard();']);
                inserts.push([body.end, '}']);
            }
        }
        for (const key of Object.keys(node)) {
            const value = node[key];
            if (Array.isArray(value)) value.forEach(walk);
            else if (value && typeof value === 'object' && typeof value.type === 'string') walk(value);
        }
    })(parse(code));

    inserts.sort((a, b) => b[0] - a[0]);
    let guarded = code;
    for (const [at, text] of inserts) {
        guarded = guarded.slice(0, at) + text + guarded.slice(at);
    }
    return guarded;
}
