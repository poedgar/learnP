import { highlightCode as lezerHighlight, classHighlighter } from '@lezer/highlight';
import { SQLite } from '@codemirror/lang-sql';
import { phpLanguage } from '@codemirror/lang-php';
import { javascriptLanguage, typescriptLanguage } from '@codemirror/lang-javascript';
import { htmlLanguage } from '@codemirror/lang-html';
import { cssLanguage } from '@codemirror/lang-css';

const parsers = {
    sql: () => SQLite.language.parser,
    // Snippets may leave out the opening <?php tag; parse those as plain PHP.
    php: (code) => (code.trimStart().startsWith('<?') ? phpLanguage.parser : phpLanguage.parser.configure({ top: 'Program' })),
    javascript: () => javascriptLanguage.parser,
    typescript: () => typescriptLanguage.parser,
    html: () => htmlLanguage.parser,
    css: () => cssLanguage.parser,
};

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
