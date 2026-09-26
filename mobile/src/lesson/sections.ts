export interface Section {
  source: string;
  title: string;
}

/**
 * One page per "## " section, as in the web app's TheoryStep. Text before the
 * first heading joins the first page.
 */
export function splitSections(content: string, fallbackTitle: string): Section[] {
  const chunks = content.split(/^(?=## )/m).filter((chunk) => chunk.trim());

  if (chunks.length === 0) {
    return [{ source: content, title: fallbackTitle }];
  }

  if (chunks.length > 1 && !chunks[0].startsWith('## ')) {
    chunks.splice(0, 2, `${chunks[1]}\n\n${chunks[0]}`);
  }

  return chunks.map((source) => ({
    source,
    // Titles appear as plain text in navigation, so drop inline-code backticks.
    title: source.match(/^## (.+)$/m)?.[1].replace(/`/g, '').trim() ?? fallbackTitle,
  }));
}
