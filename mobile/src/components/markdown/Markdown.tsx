import { Lexer, type Token, type Tokens } from 'marked';
import { useMemo, type ReactNode } from 'react';
import { Linking, ScrollView, StyleSheet, Text, View } from 'react-native';
import type { Language } from '../../api/types';
import { monoFont, useTheme, type Theme } from '../../theme';
import { Button } from '../Button';
import { CodeBlock } from './CodeBlock';

/**
 * Lesson Markdown drawn as native views. marked only tokenises here; the
 * subset the lessons use (headings, paragraphs, lists, tables, quotes, code) is
 * rendered below. Code blocks in the lesson's language get a "Try it" button.
 */
type Props = {
  source: string;
  inline?: boolean;
  runnable?: Language | null;
  onTry?: (code: string) => void;
};

const ENTITIES: Record<string, string> = { '&amp;': '&', '&lt;': '<', '&gt;': '>', '&quot;': '"', '&#39;': "'" };

export function decodeEntities(text: string): string {
  return text.replace(/&(amp|lt|gt|quot|#39);/g, (entity) => ENTITIES[entity]);
}

export function Markdown({ source, inline = false, runnable = null, onTry }: Props) {
  const theme = useTheme();
  const tokens = useMemo(() => (inline ? Lexer.lexInline(source) : Lexer.lex(source)), [source, inline]);

  if (inline) {
    return <Text style={[styles.body, { color: theme.ink }]}>{renderInline(tokens, theme)}</Text>;
  }

  return (
    <View style={styles.blocks}>
      {tokens.map((token, i) => (
        <Block key={i} token={token} theme={theme} runnable={runnable} onTry={onTry} />
      ))}
    </View>
  );
}

type BlockProps = { token: Token; theme: Theme; runnable: Language | null; onTry?: (code: string) => void };

function Block({ token, theme, runnable, onTry }: BlockProps): ReactNode {
  switch (token.type) {
    case 'space':
      return null;
    case 'heading': {
      const heading = token as Tokens.Heading;
      return (
        <Text accessibilityRole="header" style={[heading.depth <= 2 ? styles.h2 : styles.h3, { color: theme.ink }]}>
          {renderInline(heading.tokens, theme)}
        </Text>
      );
    }
    case 'paragraph':
      return (
        <Text style={[styles.body, { color: theme.ink }]}>{renderInline((token as Tokens.Paragraph).tokens, theme)}</Text>
      );
    case 'text': {
      const text = token as Tokens.Text;
      return (
        <Text style={[styles.body, { color: theme.ink }]}>
          {text.tokens ? renderInline(text.tokens, theme) : decodeEntities(text.text)}
        </Text>
      );
    }
    case 'code': {
      const code = token as Tokens.Code;
      const lang = (code.lang ?? '').trim();
      return (
        <View style={styles.codeGroup}>
          <CodeBlock code={code.text} language={lang} />
          {runnable && lang === runnable && onTry ? (
            <Button title="Try it in the sandbox" onPress={() => onTry(code.text)} />
          ) : null}
        </View>
      );
    }
    case 'blockquote':
      return (
        <View style={[styles.quote, { borderColor: theme.highlight, backgroundColor: theme.highlightSoft }]}>
          {(token as Tokens.Blockquote).tokens.map((child, i) => (
            <Block key={i} token={child} theme={theme} runnable={runnable} onTry={onTry} />
          ))}
        </View>
      );
    case 'list': {
      const list = token as Tokens.List;
      const start = typeof list.start === 'number' ? list.start : 1;
      return (
        <View style={styles.list}>
          {list.items.map((item, i) => (
            <View key={i} style={styles.listItem}>
              <Text style={[styles.body, styles.bullet, { color: theme.muted }]}>
                {list.ordered ? `${start + i}.` : '•'}
              </Text>
              <View style={styles.listBody}>
                {item.tokens.map((child, j) => (
                  <Block key={j} token={child} theme={theme} runnable={runnable} onTry={onTry} />
                ))}
              </View>
            </View>
          ))}
        </View>
      );
    }
    case 'table': {
      const table = token as Tokens.Table;
      return (
        <ScrollView horizontal style={[styles.table, { borderColor: theme.line }]}>
          <View>
            <View style={[styles.tableRow, { backgroundColor: theme.codeBackground }]}>
              {table.header.map((cell, i) => (
                <Text key={i} style={[styles.cell, styles.headerCell, { color: theme.ink, borderColor: theme.line }]}>
                  {renderInline(cell.tokens, theme)}
                </Text>
              ))}
            </View>
            {table.rows.map((row, r) => (
              <View key={r} style={styles.tableRow}>
                {row.map((cell, c) => (
                  <Text key={c} style={[styles.cell, { color: theme.ink, borderColor: theme.line }]}>
                    {renderInline(cell.tokens, theme)}
                  </Text>
                ))}
              </View>
            ))}
          </View>
        </ScrollView>
      );
    }
    case 'hr':
      return <View style={[styles.hr, { backgroundColor: theme.line }]} />;
    default:
      return 'raw' in token && token.raw.trim() ? (
        <Text style={[styles.body, { color: theme.ink }]}>{token.raw}</Text>
      ) : null;
  }
}

function renderInline(tokens: Token[] | undefined, theme: Theme): ReactNode[] {
  return (tokens ?? []).map((token, i) => {
    switch (token.type) {
      case 'strong':
        return (
          <Text key={i} style={styles.strong}>
            {renderInline((token as Tokens.Strong).tokens, theme)}
          </Text>
        );
      case 'em':
        return (
          <Text key={i} style={styles.em}>
            {renderInline((token as Tokens.Em).tokens, theme)}
          </Text>
        );
      case 'del':
        return (
          <Text key={i} style={styles.del}>
            {renderInline((token as Tokens.Del).tokens, theme)}
          </Text>
        );
      case 'codespan':
        return (
          <Text key={i} style={[styles.codespan, { backgroundColor: theme.codeBackground }]}>
            {decodeEntities((token as Tokens.Codespan).text)}
          </Text>
        );
      case 'link': {
        const link = token as Tokens.Link;
        return (
          <Text
            key={i}
            accessibilityRole="link"
            style={[styles.link, { color: theme.ink }]}
            onPress={() => Linking.openURL(link.href)}
          >
            {renderInline(link.tokens, theme)}
          </Text>
        );
      }
      case 'br':
        return '\n';
      case 'text': {
        const text = token as Tokens.Text;
        return text.tokens ? (
          <Text key={i}>{renderInline(text.tokens, theme)}</Text>
        ) : (
          decodeEntities(text.text)
        );
      }
      case 'escape':
        return decodeEntities((token as Tokens.Escape).text);
      default:
        return 'raw' in token ? token.raw : null;
    }
  });
}

const styles = StyleSheet.create({
  blocks: { gap: 14 },
  body: { fontSize: 16, lineHeight: 24 },
  h2: { fontSize: 24, fontWeight: '800', lineHeight: 30, marginTop: 6 },
  h3: { fontSize: 19, fontWeight: '700', lineHeight: 26, marginTop: 4 },
  strong: { fontWeight: '700' },
  em: { fontStyle: 'italic' },
  del: { textDecorationLine: 'line-through' },
  codespan: { fontFamily: monoFont, fontSize: 15 },
  link: { textDecorationLine: 'underline' },
  codeGroup: { gap: 8 },
  quote: { borderLeftWidth: 4, paddingVertical: 8, paddingHorizontal: 12, borderRadius: 6, gap: 8 },
  list: { gap: 6 },
  listItem: { flexDirection: 'row', gap: 8 },
  bullet: { minWidth: 18 },
  listBody: { flex: 1, gap: 6 },
  table: { borderWidth: 1, borderRadius: 6 },
  tableRow: { flexDirection: 'row' },
  cell: { minWidth: 96, paddingVertical: 6, paddingHorizontal: 10, borderRightWidth: 1, borderBottomWidth: 1, fontSize: 15 },
  headerCell: { fontWeight: '700' },
  hr: { height: 1 },
});
