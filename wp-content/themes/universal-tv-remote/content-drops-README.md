# Blog folder-drop convention

Drop one folder per blog post into **`wp-content/uploads/content-drops/`**
(not inside the theme — see note below) — either by copying it onto the
server directly, or by uploading it from wp-admin: **Posts → Import from
Folder** (or the "Add Folder Post" button next to "Add Post" on Posts →
All Posts) has an upload control that accepts a whole folder at once
(including a parent folder containing several post folders, for bulk
upload), and lists every folder's validation status before you commit to
running the import. `scripts/import-blog-posts.php` does the same thing
from the command line, for anyone who prefers that. Either way, each
import is idempotent — re-running after fixing a mistake in a folder
updates the same Draft post instead of creating a duplicate.

**Why `wp-content/uploads/` and not a folder inside the theme:** most WP
hosting (this project's production included) makes `wp-content/themes/`
read-only to the PHP process — only `wp-content/uploads/` is guaranteed
writable, since WordPress itself requires that for any media upload to
work at all. `tvr_blog_content_drops_dir()` in `inc/blog-import.php`
builds the actual path via `wp_upload_dir()`, so this note is the only
place the location is hardcoded — nothing else needs to know it moved if
it ever does again.

Folder name = the post's URL slug, e.g. `content-drops/how-to-fix-tv-wont-turn-on/`.

Each folder must contain:

- **Exactly one `.docx` file** — any filename (e.g. named after the article
  title, however it naturally comes out of the content pipeline).
- **A featured image** and **`meta.txt`** — see below.
- Any other image files referenced from the `.docx` as inline images (see
  the `[image: ...]` marker below).

## The `.docx`'s own structure

The importer understands the content pipeline's real doc format directly —
no manual reformatting needed. A fixed metadata block at the very top of
the document, each on its own paragraph:

```
Title
<the article's title>

Meta Description
<the meta description text>

Tag
<comma-separated tags>

Main Keyword
<the focus keyword>

Secondary Keywords
<bulleted list of secondary keyword phrases>

Outline
<bulleted list matching the H2 sections below>
```

...followed by the article body (the body's own repeat of the title, right
after Outline, is recognized and dropped automatically — WordPress already
renders the post title as the page's real heading). From **Title / Meta
Description / Main Keyword**, the importer auto-fills Rank Math's SEO
Title / Meta Description / Focus Keyword fields on the Draft — reviewable
and editable in the Rank Math box same as always, just not blank to start.
`Tag` and `Secondary Keywords` are parsed but not currently applied
anywhere automatically.

Body headings don't need any particular Word style — they're detected by
size (a large bold line = H2, a smaller bold line = H3), matching what the
pipeline's docs actually use. Lists, tables, and **bold**/*italic* text are
carried through as-is.

(A plain doc using real Word `Heading 1`/`Heading 2`/`Title` paragraph
styles instead — no metadata block — still works too, as a fallback.)

## Images

- **Featured image**, in order of preference:
  1. a line inside the `.docx`, anywhere, exactly like this:
     ```
     [feature: photo3.jpg]
     ```
     (optionally `[feature: photo3.jpg | Alt text]` to set its alt text too)
  2. a file in the folder literally named `feature.<ext>` (e.g. `feature.jpg`)
  3. if neither is present, the importer auto-picks the first image file in
     the folder that isn't already used as an inline image (see below) —
     the admin preview table always shows which image got auto-picked, so
     a wrong guess is easy to spot; add a `[feature: ...]` line to override it.
- **Inline images**: referenced from inside the `.docx`, on its own line,
  exactly like this:

  ```
  [image: wifi-setup.png | Alt text mô tả ảnh]
  ```

  The image is inserted at that exact position in the post, using the text
  after `|` as its alt text (required — used for image SEO/accessibility).
  Image files present in the folder but never referenced this way are left
  out of the post and flagged as a warning (not an error) so nothing goes
  missing silently.

## `meta.txt`

Plain `key: value` lines, just:

```
category: Troubleshooting
tags: wifi, samsung, setup
```

A category/tag that doesn't exist yet is created automatically. (Separate
from the `.docx`'s own `Tag` metadata field above, which isn't wired into
WordPress categories/tags yet.)

## What the importer does NOT do

It never publishes anything — every post it creates or updates is left in
`draft` status for the normal QA-and-preview review step. The Rank Math
fields it auto-fills are a starting point, not final — always reviewed by
a human before the post goes out.
