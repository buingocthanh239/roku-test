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
updates the same Draft post instead of creating a duplicate. A folder that
imports successfully (no error) is moved into `content-drops/_imported/`
right after — archived, not deleted, so the "ready to import" list doesn't
keep piling up with already-done folders, but the original `.docx`/images
are still there if ever needed again. Re-importing the same slug later
replaces its old archived copy.

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
- **`seo_info.txt`** — the per-post config (title, meta description, tags,
  categories, keywords). See below.
- **A featured image** — see below.
- Any other image files referenced from the `.docx` as inline images (see
  the `[image: ...]` marker below).

## The `.docx`'s own structure

The importer understands the content pipeline's real doc format directly —
no manual reformatting needed. A fixed metadata block at the very top of
the document — **Title / Meta Description / Tag / a focus-keyword field
(either "Main Keyword" or "Primary Keyword" — both recognized) /
Secondary Keywords / Outline** — written either way, both work and can be
mixed within the same doc:

```
Title
<the article's title>
```
or on one line:
```
Title: <the article's title>
```

A short unrecognized heading right before the block (e.g. "SEO Publishing
Snapshot") is fine — it's skipped rather than treated as content or as a
sign this doc doesn't use the template at all.

...followed by the article body (the body's own repeat of the title, right
after the metadata block, is recognized by matching text — or, failing
that, a clearly oversized heading — and dropped automatically; WordPress
already renders the post title as the page's real heading). From **Title /
Meta Description / Main-or-Primary Keyword**, the importer auto-fills Rank
Math's SEO Title / Meta Description / Focus Keyword fields on the Draft —
reviewable and editable in the Rank Math box same as always, just not
blank to start. (Rank Math itself needs a one-time setup per environment
before this works — if it's missing, wp-admin shows a notice with a
one-click "Activate & Configure Rank Math" button, no server access
needed.) `Tag` is merged into the post's WordPress tags (together with
`seo_info.txt`'s own `Tag` field, if both are present; splits on comma or
semicolon — seen both ways in practice). `Secondary Keywords` is parsed
but not currently applied anywhere. Note this block is the *older* way of
carrying a post's metadata — folders in the current structure put it in
`seo_info.txt` instead (see below), and that file is also the only place
**categories** can be set.

Body headings work either way — docs that use no paragraph styles at all
are detected by size (a large bold line = H2, a smaller bold line = H3),
and docs that use real Word `Heading 1`/`Heading 2`/`Heading 3` styles are
mapped by style. In the styled case the level depends on where the title
came from:

| Title comes from | `Heading 1` | `Heading 2` | `Heading 3` |
| --- | --- | --- | --- |
| a `Title`-styled paragraph, or `seo_info.txt` | `<h2>` | `<h3>` | `<h4>` |
| `Heading 1` itself (older docs, no `Title` style) | *the post title* | `<h2>` | `<h3>` |

Lists, tables, and **bold**/*italic* text are carried through as-is —
including bullets written with the `ListBullet`/`ListNumber` paragraph
styles, which Word exports without any list-numbering markup of their own.

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
- **Unmarked images** (present in the folder, not the featured image, not
  referenced by an `[image: ...]` marker) aren't left out — real content
  folders often don't mark every image explicitly, so up to one per H2
  section gets placed automatically, right after that section's heading
  (centered, full width), in filename order. The admin preview table
  always shows exactly which images got auto-placed this way before you
  run the import. Only if there are *more* unmarked images than H2
  sections does anything actually get left out — flagged as a warning, not
  silently dropped.

## `seo_info.txt`

The folder's per-post config — a label on its own line, its value on the
line(s) below it:

```
Title
How to Pair Universal Remote to TV: Complete Setup Guide for Any Smart TV

Meta description
Learn how to pair universal remote to tv with or without codes.

Tag
Universal Remote, TV Remote Setup, Smart TV, Remote Pairing

Categories
Remote Setup Guides

Từ khóa chính
how to pair universal remote to tv

Từ khóa phụ
how to pair universal remote with tv
how to sync universal remote to tv

Outline
1. What You Need Before Pairing a Universal Remote
2. ...
```

`Label: value` on one line works too, and the two shapes can be mixed in
the same file.

| Label (either spelling) | Goes to |
| --- | --- |
| `Title` / `Tiêu đề` | The post title + Rank Math's SEO Title |
| `Meta description` / `Mô tả` | Rank Math's Meta Description |
| `Tag` / `Tags` / `Thẻ` | The post's WordPress **tags** |
| `Categories` / `Category` / `Chuyên mục` / `Danh mục` | The post's WordPress **categories** |
| `Từ khóa chính` / `Main Keyword` / `Primary Keyword` / `Focus Keyword` | Rank Math's Focus Keyword |
| `Từ khóa phụ` / `Secondary Keywords` | Parsed, not currently applied anywhere |
| `Outline` / `Dàn ý` | Ignored (it's the writer's own working outline) |

`Tag` and `Categories` both accept a comma- or semicolon-separated list on
one line, or one entry per line. A category or tag that doesn't exist yet
is created automatically, and the assigned categories replace whatever the
draft had before — including WordPress's default "Uncategorized".

Tags are the **union** of this file's `Tag` line and the `.docx`'s own
`Tag` metadata field (for older docs that carry one), not one or the
other. For every other field, this file wins where it has a value.

The older **`meta.txt`** (`category: ...` / `tags: ...` lines) is still
read, for folders that already have one — `seo_info.txt` takes precedence
where a folder somehow has both.

## What the importer does NOT do

It never publishes anything — every post it creates or updates is left in
`draft` status for the normal QA-and-preview review step. The Rank Math
fields it auto-fills are a starting point, not final — always reviewed by
a human before the post goes out.
