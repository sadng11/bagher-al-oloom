# Photo Gallery («تصاویر») Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the «تصاویر» photo albums & images section in Laravel 9, Laravel Nova 4, and the Blade/Fancybox frontend with unnamed editable albums, per-photo captions, 2 MB upload limit, automatic GD compression + thumbnail generation, and album view counters without image download buttons.

**Architecture:** Two normalized tables (`photo_albums` and `photos`) backed by a dedicated `gallery` filesystem disk (`storage/app/public/gallery`), `App\Services\ImageOptimizationService` invoked through Eloquent observers for resize/compression/thumbnailing and file cleanup, Nova resources (`PhotoAlbum` and `Photo`), and `PhotoGalleryController` + Blade/Fancybox views.

**Tech Stack:** Laravel 9, PHP 8.2 (GD extension), Laravel Nova 4, Blade + Bootstrap + jQuery Fancybox 3, PHPUnit (SQLite in-memory).

**Spec:** `docs/superpowers/specs/2026-11-26-photo-gallery-design.md`

## Global Constraints
- Album `title` is nullable (`string(500)->nullable()`) and editable at any time; displays `آلبوم بدون نام` on the site when empty.
- Upload mode in Nova is single-photo upload per record (`Photo` resource) with per-photo `caption`.
- Accepted formats: `jpg,jpeg,png,webp,gif,bmp,avif` (no SVG) with max upload size `2048` KB (2 MB) before automatic optimization.
- Image download buttons must NOT appear in Fancybox or photo cards; album `views` counter must be displayed and incremented once per session.

## Review Focus
- Uploading an image > 2048 KB or disallowed MIME (e.g. `.svg`, `.txt`) must fail validation cleanly.
- Uploading an image when GD lacks WebP/AVIF codecs must not crash; it must gracefully store the file and fall back for thumbnail.
- Updating only `caption` or album `title` must NOT delete or re-encode existing image files.
- Deleting a `Photo` or `PhotoAlbum` must clean up image/thumbnail files from the `gallery` disk.
- Unpublished albums/photos (`status = false`) must return 404 / be hidden on public `/images` routes.

---

### Task 1: Filesystem disk, PHP upload ini, migrations, and models
- [ ] Add `gallery` disk to `config/filesystems.php` and upload limits to `custom-opcache.ini`.
- [ ] Create migrations for `photo_albums` and `photos`.
- [ ] Create `App\Models\PhotoAlbum` and `App\Models\Photo` with relationships, scopes, `display_title`, cover/URL accessors, and sorting/search helper.
- [ ] Write & run unit/feature tests for models and migrations.

### Task 2: Image optimization service and model observers
- [ ] Create `App\Services\ImageOptimizationService` (resize max 1920px, thumbnail max 600px, EXIF orientation, transparency preservation, animated GIF preservation, codec fallback, cleanup).
- [ ] Create `App\Observers\PhotoObserver` and `App\Observers\PhotoAlbumObserver` and register them in `AppServiceProvider` (so they work in both Nova and tests/tinker).
- [ ] Write & run tests verifying compression, thumbnail generation, caption-only updates, and file cleanup on replace/delete.

### Task 3: Laravel Nova resources & Persian translations
- [ ] Create `App\Nova\PhotoAlbum` and `App\Nova\Photo` with nullable/editable album title, single upload rules (`mimes:jpg,jpeg,png,webp,gif,bmp,avif`, `max:2048`), caption field, views, status, sort order.
- [ ] Register resources in `App\Providers\NovaServiceProvider` main menu and add Persian translations in `lang/vendor/nova/fa.json`.
- [ ] Write & run `tests/Feature/NovaPhotoGalleryTest.php` covering creation of unnamed album, editing title, uploading photo with caption, >2MB validation error, invalid MIME rejection, and menu registration.

### Task 4: Public routes, controller, views, and header navigation
- [ ] Add `images.index` and `images.show` routes to `routes/web.php` and implement `App\Http\Controllers\PhotoGalleryController`.
- [ ] Create `resources/views/site/images/index.blade.php` and `resources/views/site/images/show.blade.php` with lazy loading, pagination, view counts, and Fancybox without download button.
- [ ] Add `تصاویر` link to desktop and mobile navigation in `resources/views/site/partials/header.blade.php`.
- [ ] Write & run `tests/Feature/PhotoGallerySiteTest.php` and verify full test suite passes.
