import { highlightCode as lezerHighlight, classHighlighter, tags } from '@lezer/highlight';
import { SQLite } from '@codemirror/lang-sql';
import { phpLanguage } from '@codemirror/lang-php';
import { javascriptLanguage, typescriptLanguage } from '@codemirror/lang-javascript';
import { htmlLanguage } from '@codemirror/lang-html';
import { cssLanguage } from '@codemirror/lang-css';
import { StreamLanguage } from '@codemirror/language';
import { shell } from '@codemirror/legacy-modes/mode/shell';
import { dockerFile } from '@codemirror/legacy-modes/mode/dockerfile';
import { yaml } from '@codemirror/legacy-modes/mode/yaml';
import { json } from '@codemirror/legacy-modes/mode/javascript';
import { nginx } from '@codemirror/legacy-modes/mode/nginx';
import { properties } from '@codemirror/legacy-modes/mode/properties';

const parsers = {
    sql: () => SQLite.language.parser,
    // Snippets may leave out the opening <?php tag; parse those as plain PHP.
    php: (code) => (code.trimStart().startsWith('<?') ? phpLanguage.parser : phpLanguage.parser.configure({ top: 'Program' })),
    javascript: () => javascriptLanguage.parser,
    typescript: () => typescriptLanguage.parser,
    html: () => htmlLanguage.parser,
    css: () => cssLanguage.parser,
};

// Real framework code in lessons that can't run in the sandbox: highlighted as PHP, never runnable.
parsers.laravel = parsers.php;

// Read-only blocks in other languages that lessons show (Blade, shell commands, Docker files,
// JSON responses and server or .env configuration).
const streamParser = (mode) => {
    const parser = StreamLanguage.define(mode).parser;
    return () => parser;
};
parsers.blade = parsers.html;
parsers.bash = streamParser(shell);
parsers.shell = parsers.bash;
parsers.git = parsers.bash;
parsers.dockerfile = streamParser(dockerFile);
parsers.yaml = streamParser(yaml);
// JSON's keys come out as `property`, which needs a tag of its own.
parsers.json = streamParser({ ...json, tokenTable: { property: tags.propertyName } });
parsers.nginx = streamParser(nginx);
parsers.ini = streamParser(properties);
parsers.env = parsers.ini;

function escapeHtml(text) {
    return text.replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
}

export function canHighlight(language) {
    return language in parsers;
}

/** Static highlighting for read-only code, using the same parsers as the editor. */
export function highlightCode(code, language) {
    if (!canHighlight(language)) {
        return escapeHtml(code);
    }

    let html = '';
    lezerHighlight(
        code,
        parsers[language](code).parse(code),
        classHighlighter,
        (text, classes) => {
            html += classes ? `<span class="${classes}">${escapeHtml(text)}</span>` : escapeHtml(text);
        },
        () => {
            html += '\n';
        },
    );
    return html;
}
