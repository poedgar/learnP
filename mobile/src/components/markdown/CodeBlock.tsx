import Prism from 'prismjs';
import 'prismjs/components/prism-markup-templating';
import 'prismjs/components/prism-php';
import 'prismjs/components/prism-sql';
import { ScrollView, StyleSheet, Text } from 'react-native';
import { monoFont, useTheme, type SyntaxColor } from '../../theme';

/** Prism token types → theme syntax colours. Anything unlisted inherits its parent's colour. */
const COLORS: Record<string, SyntaxColor> = {
  keyword: 'keyword',
  boolean: 'keyword',
  delimiter: 'keyword',
  important: 'keyword',
  string: 'string',
  char: 'string',
  number: 'number',
  constant: 'number',
  comment: 'comment',
  function: 'function',
  variable: 'variable',
  property: 'variable',
  'class-name': 'type',
};

export function highlightTokens(code: string, language: string): { text: string; color: SyntaxColor }[] {
  const grammar = Prism.languages[language];
  if (!grammar) {
    return [{ text: code, color: 'plain' }];
  }

  const out: { text: string; color: SyntaxColor }[] = [];
  const walk = (tokens: (string | Prism.Token)[], inherited: SyntaxColor) => {
    for (const token of tokens) {
      if (typeof token === 'string') {
        out.push({ text: token, color: inherited });
        continue;
      }
      const color = COLORS[token.type] ?? inherited;
      if (typeof token.content === 'string') {
        out.push({ text: token.content, color });
      } else {
        walk(Array.isArray(token.content) ? token.content : [token.content], color);
      }
    }
  };
  walk(Prism.tokenize(code, grammar), 'plain');

  return out;
}

export function CodeBlock({ code, language }: { code: string; language: string }) {
  const theme = useTheme();

  return (
    <ScrollView
      horizontal
      style={[styles.box, { backgroundColor: theme.codeBackground, borderColor: theme.line }]}
      contentContainerStyle={styles.content}
    >
      <Text style={[styles.code, { color: theme.syntax.plain }]} selectable>
        {highlightTokens(code.replace(/\n$/, ''), language).map((token, i) => (
          <Text key={i} style={{ color: theme.syntax[token.color] }}>
            {token.text}
          </Text>
        ))}
      </Text>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  box: { borderWidth: 1, borderRadius: 8 },
  content: { padding: 12 },
  code: { fontFamily: monoFont, fontSize: 14, lineHeight: 21 },
});
