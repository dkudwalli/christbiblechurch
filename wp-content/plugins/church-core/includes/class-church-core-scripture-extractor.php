<?php
if (! defined('ABSPATH')) {
    exit;
}

final class Church_Core_Scripture_Extractor
{
    private const SCRIPTURE_BOOK_ALIASES = [
        'Genesis' => ['Genesis', 'Gen', 'Ge', 'Gn'],
        'Exodus' => ['Exodus', 'Exod', 'Exo', 'Ex'],
        'Leviticus' => ['Leviticus', 'Lev', 'Le', 'Lv'],
        'Numbers' => ['Numbers', 'Num', 'Nu', 'Nm', 'Nb'],
        'Deuteronomy' => ['Deuteronomy', 'Deut', 'Deu', 'Dt'],
        'Joshua' => ['Joshua', 'Josh', 'Jos', 'Jsh'],
        'Judges' => ['Judges', 'Judg', 'Jdg', 'Jdgs'],
        'Ruth' => ['Ruth', 'Rth', 'Ru'],
        '1 Samuel' => ['1 Samuel', '1 Sam', '1Sa', '1 Sa'],
        '2 Samuel' => ['2 Samuel', '2 Sam', '2Sa', '2 Sa'],
        '1 Kings' => ['1 Kings', '1 Kgs', '1 Ki', '1Ki', '1 Kngs'],
        '2 Kings' => ['2 Kings', '2 Kgs', '2 Ki', '2Ki', '2 Kngs'],
        '1 Chronicles' => ['1 Chronicles', '1 Chron', '1 Chr', '1Ch', '1 Chrn'],
        '2 Chronicles' => ['2 Chronicles', '2 Chron', '2 Chr', '2Ch', '2 Chrn'],
        'Ezra' => ['Ezra', 'Ezr'],
        'Nehemiah' => ['Nehemiah', 'Neh', 'Ne'],
        'Esther' => ['Esther', 'Esth', 'Est'],
        'Job' => ['Job'],
        'Psalms' => ['Psalms', 'Psalm', 'Psalm', 'Psalms', 'Ps'],
        'Proverbs' => ['Proverbs', 'Prov', 'Pro', 'Prv', 'Pr'],
        'Ecclesiastes' => ['Ecclesiastes', 'Eccles', 'Eccl', 'Ecc', 'Qoh'],
        'Song of Solomon' => ['Song of Solomon', 'Song of Songs', 'Song', 'SOS', 'Canticles'],
        'Isaiah' => ['Isaiah', 'Isa'],
        'Jeremiah' => ['Jeremiah', 'Jer'],
        'Lamentations' => ['Lamentations', 'Lam'],
        'Ezekiel' => ['Ezekiel', 'Ezek', 'Eze', 'Ezk'],
        'Daniel' => ['Daniel', 'Dan', 'Da', 'Dn'],
        'Hosea' => ['Hosea', 'Hos', 'Ho'],
        'Joel' => ['Joel', 'Joe', 'Jl'],
        'Amos' => ['Amos', 'Am'],
        'Obadiah' => ['Obadiah', 'Obad', 'Ob'],
        'Jonah' => ['Jonah', 'Jon'],
        'Micah' => ['Micah', 'Mic'],
        'Nahum' => ['Nahum', 'Nah'],
        'Habakkuk' => ['Habakkuk', 'Hab'],
        'Zephaniah' => ['Zephaniah', 'Zeph', 'Zep'],
        'Haggai' => ['Haggai', 'Hag', 'Hg'],
        'Zechariah' => ['Zechariah', 'Zech', 'Zec'],
        'Malachi' => ['Malachi', 'Mal'],
        'Matthew' => ['Matthew', 'Matt', 'Mt'],
        'Mark' => ['Mark', 'Mrk', 'Mk', 'Mr'],
        'Luke' => ['Luke', 'Luk', 'Lk'],
        'John' => ['John', 'Jn', 'Jhn'],
        'Acts' => ['Acts', 'Act', 'Ac'],
        'Romans' => ['Romans', 'Rom', 'Ro', 'Rm'],
        '1 Corinthians' => ['1 Corinthians', '1 Cor', '1Co', '1 Co'],
        '2 Corinthians' => ['2 Corinthians', '2 Cor', '2Co', '2 Co'],
        'Galatians' => ['Galatians', 'Gal', 'Ga'],
        'Ephesians' => ['Ephesians', 'Eph'],
        'Philippians' => ['Philippians', 'Phil', 'Php', 'Pp'],
        'Colossians' => ['Colossians', 'Col', 'Colos', 'Coloss'],
        '1 Thessalonians' => ['1 Thessalonians', '1 Thess', '1 Thes', '1Th', '1 Th'],
        '2 Thessalonians' => ['2 Thessalonians', '2 Thess', '2 Thes', '2Th', '2 Th'],
        '1 Timothy' => ['1 Timothy', '1 Tim', '1Ti', '1 Ti'],
        '2 Timothy' => ['2 Timothy', '2 Tim', '2Ti', '2 Ti'],
        'Titus' => ['Titus', 'Tit'],
        'Philemon' => ['Philemon', 'Phlm', 'Phm', 'Pm'],
        'Hebrews' => ['Hebrews', 'Heb'],
        'James' => ['James', 'Jas', 'Jm'],
        '1 Peter' => ['1 Peter', '1 Pet', '1Pe', '1 Pt'],
        '2 Peter' => ['2 Peter', '2 Pet', '2Pe', '2 Pt'],
        '1 John' => ['1 John', '1 Jn', '1Jn', '1 Jhn'],
        '2 John' => ['2 John', '2 Jn', '2Jn', '2 Jhn'],
        '3 John' => ['3 John', '3 Jn', '3Jn', '3 Jhn'],
        'Jude' => ['Jude', 'Jud'],
        'Revelation' => ['Revelation', 'Rev', 'Re', 'Revelations'],
    ];

    public static function from_title(string $title): string
    {
        $title = html_entity_decode(trim($title), ENT_QUOTES, get_bloginfo('charset') ?: 'UTF-8');

        if ($title === '') {
            return '';
        }

        $pattern = sprintf(
            '/(?<![A-Za-z])(?P<book>%1$s)\.?\s*(?P<chapter>\d{1,3})\s*:\s*(?P<verse>\d{1,3})(?:(?P<separator>\s*[-–—]\s*)(?:(?P<end_chapter>\d{1,3})\s*:\s*)?(?P<end_verse>\d{1,3}))?/iu',
            self::get_book_pattern()
        );

        if (preg_match($pattern, $title, $matches, PREG_OFFSET_CAPTURE) !== 1) {
            return '';
        }

        // Reject clock times mistaken for chapter:verse, e.g. "Evening Service 6:30pm"
        // preceded by a book-like word: if the matched number is immediately followed
        // by an am/pm marker, it is a time, not a scripture reference.
        $match_end = $matches[0][1] + strlen($matches[0][0]);
        if (preg_match('/^\s*[ap]\.?m\.?\b/i', substr($title, $match_end)) === 1) {
            return '';
        }

        // Collapse the offset-capture shape back to plain string groups.
        $matches = array_map(static fn ($m) => is_array($m) ? $m[0] : $m, $matches);

        $book_name = self::expand_book_name((string) $matches['book']);

        if ($book_name === '') {
            return '';
        }

        $separator = isset($matches['separator']) && $matches['separator'] !== ''
            ? preg_replace('/\s+/u', '', (string) $matches['separator'])
            : '';

        $reference = (string) $matches['chapter'] . ':' . (string) $matches['verse'];

        if (isset($matches['end_verse']) && $matches['end_verse'] !== '') {
            $separator = $separator !== '' ? $separator : '-';
            $reference .= $separator;

            if (isset($matches['end_chapter']) && $matches['end_chapter'] !== '') {
                $reference .= (string) $matches['end_chapter'] . ':';
            }

            $reference .= (string) $matches['end_verse'];
        }

        $scripture_reference = $book_name . ' ' . $reference;
        $scripture_reference = sanitize_text_field($scripture_reference);
        $scripture_reference = (string) apply_filters(
            'church_core_sermon_sync_extract_scripture_reference',
            $scripture_reference,
            $title,
            $matches
        );

        return sanitize_text_field(trim($scripture_reference));
    }

    private static function expand_book_name(string $book_name): string
    {
        $normalized = self::normalize_book_alias($book_name);
        $alias_map = self::get_book_alias_map();

        return $alias_map[$normalized] ?? '';
    }

    private static function get_book_alias_map(): array
    {
        static $alias_map = null;

        if (is_array($alias_map)) {
            return $alias_map;
        }

        $alias_map = [];

        foreach (self::SCRIPTURE_BOOK_ALIASES as $canonical_name => $aliases) {
            $alias_map[self::normalize_book_alias($canonical_name)] = $canonical_name;

            foreach ($aliases as $alias) {
                $alias_map[self::normalize_book_alias($alias)] = $canonical_name;
            }
        }

        return $alias_map;
    }

    private static function get_book_pattern(): string
    {
        static $pattern = null;

        if (is_string($pattern) && $pattern !== '') {
            return $pattern;
        }

        $aliases = [];

        foreach (self::SCRIPTURE_BOOK_ALIASES as $canonical_name => $book_aliases) {
            $aliases[$canonical_name] = $canonical_name;

            foreach ($book_aliases as $alias) {
                $aliases[$alias] = $alias;
            }
        }

        $aliases = array_values($aliases);

        usort(
            $aliases,
            static function (string $left, string $right): int {
                return strlen($right) <=> strlen($left);
            }
        );

        $pattern_parts = [];

        foreach ($aliases as $alias) {
            $pattern_parts[] = str_replace('\ ', '\s*', preg_quote($alias, '/'));
        }

        $pattern = '(?:' . implode('|', $pattern_parts) . ')';

        return $pattern;
    }

    private static function normalize_book_alias(string $book_name): string
    {
        $book_name = strtolower($book_name);
        $book_name = preg_replace('/\.+/u', '', $book_name) ?: $book_name;
        $book_name = preg_replace('/\s+/u', '', $book_name) ?: $book_name;

        return preg_replace('/[^a-z0-9]/u', '', $book_name) ?: '';
    }
}
