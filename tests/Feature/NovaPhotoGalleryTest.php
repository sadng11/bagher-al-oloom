<?php

namespace Tests\Feature;

use App\Models\Photo;
use App\Models\PhotoAlbum;
use App\Models\User;
use App\Nova\Photo as NovaPhoto;
use App\Nova\PhotoAlbum as NovaPhotoAlbum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Laravel\Nova\Events\ServingNova;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Menu\MenuItem;
use Laravel\Nova\Menu\MenuSection;
use Laravel\Nova\Nova;
use Tests\TestCase;

class NovaPhotoGalleryTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsNovaUser(): User
    {
        $user = User::factory()->create();
        Gate::define('viewNova', fn () => true);
        $this->actingAs($user);

        return $user;
    }

    private function bootNova(): NovaRequest
    {
        Nova::flushState();
        ServingNova::dispatch(Request::create('/manage'));

        return NovaRequest::create('/manage');
    }

    public function test_nova_registers_gallery_resources_in_menu_and_resolves_models(): void
    {
        $this->actingAsNovaUser();
        $request = $this->bootNova();

        $this->assertSame(NovaPhotoAlbum::class, Nova::resourceForModel(PhotoAlbum::class));
        $this->assertSame(NovaPhoto::class, Nova::resourceForModel(Photo::class));

        $menu = collect(Nova::resolveMainMenu($request));
        $imagesSection = $menu->first(
            fn ($item) => $item instanceof MenuSection && $item->name === __('Images')
        );
        $this->assertInstanceOf(MenuSection::class, $imagesSection);

        $paths = collect($imagesSection->items)
            ->filter(fn ($item) => $item instanceof MenuItem)
            ->pluck('path')
            ->all();

        $this->assertContains('/resources/photo-albums', $paths);
        $this->assertContains('/resources/photos', $paths);
    }

    public function test_nova_creates_unnamed_album_and_allows_naming_later(): void
    {
        $this->actingAsNovaUser();
        $this->bootNova();

        $createResponse = $this->postJson('/nova-api/photo-albums', [
            'title' => '',
            'description' => 'آلبوم بدون نام اولیه',
            'sort_order' => 0,
            'status' => true,
        ])->assertCreated();

        $albumId = $createResponse->json('id');
        $album = PhotoAlbum::findOrFail($albumId);

        $this->assertNull($album->title);
        $this->assertSame(PhotoAlbum::UNTITLED_LABEL, $album->display_title);
        $this->assertSame(__('Untitled Album') . ' #' . $album->id, (new NovaPhotoAlbum($album))->title());

        $this->putJson('/nova-api/photo-albums/' . $albumId, [
            'title' => 'مراسم محرم ۱۴۰۵',
            'description' => 'آلبوم نام‌گذاری شده',
            'sort_order' => 1,
            'status' => true,
        ])->assertOk();

        $album->refresh();
        $this->assertSame('مراسم محرم ۱۴۰۵', $album->title);
        $this->assertSame('مراسم محرم ۱۴۰۵', $album->display_title);
    }

    public function test_nova_uploads_single_photo_with_caption_and_validates_size_and_mime(): void
    {
        Storage::fake('gallery');
        $this->actingAsNovaUser();
        $this->bootNova();

        $album = PhotoAlbum::create(['title' => null]);

        // Reject file larger than 2048 KB
        $tooBigFile = UploadedFile::fake()->create('huge.jpg', 2500, 'image/jpeg');
        $this->post('/nova-api/photos', [
            'album' => $album->id,
            'image' => $tooBigFile,
            'caption' => 'فایل حجیم',
            'sort_order' => 0,
            'status' => true,
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('image');

        // Reject disallowed MIME (e.g. svg / text)
        $disallowedSvg = UploadedFile::fake()->create('vector.svg', 10, 'image/svg+xml');
        $this->post('/nova-api/photos', [
            'album' => $album->id,
            'image' => $disallowedSvg,
            'caption' => 'فایل غیرمجاز',
            'sort_order' => 0,
            'status' => true,
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('image');

        // Accept valid image <= 2048 KB
        $validImage = UploadedFile::fake()->image('event.png', 1200, 900);
        $createdResponse = $this->post('/nova-api/photos', [
            'album' => $album->id,
            'image' => $validImage,
            'caption' => 'توضیح تصویر مراسم',
            'sort_order' => 2,
            'status' => true,
        ], ['Accept' => 'application/json'])->assertCreated();

        $photo = Photo::findOrFail($createdResponse->json('id'));
        $this->assertSame($album->id, $photo->photo_album_id);
        $this->assertSame('توضیح تصویر مراسم', $photo->caption);
        $this->assertNotNull($photo->thumbnail);
        Storage::disk('gallery')->assertExists($photo->image);
        Storage::disk('gallery')->assertExists($photo->thumbnail);

        $savedImagePath = $photo->image;

        // Updating caption only keeps existing image intact
        $this->putJson('/nova-api/photos/' . $photo->id, [
            'album' => $album->id,
            'caption' => 'توضیح ویرایش‌شده تصویر',
            'sort_order' => 3,
            'status' => true,
        ])->assertOk();

        $photo->refresh();
        $this->assertSame('توضیح ویرایش‌شده تصویر', $photo->caption);
        $this->assertSame($savedImagePath, $photo->image);
        Storage::disk('gallery')->assertExists($savedImagePath);
    }

    public function test_nova_persian_translations_json_contains_gallery_keys(): void
    {
        $raw = file_get_contents(lang_path('vendor/nova/fa.json'));
        $this->assertNotFalse($raw);

        $translations = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

        foreach (['Images', 'Photo Albums', 'Photo Album', 'Photos', 'Photo', 'Untitled Album', 'Photo Caption', 'Album Photos'] as $key) {
            $this->assertArrayHasKey($key, $translations);
        }
    }

    public function test_nova_album_inline_photos_field_supports_multiple_photos_captions_updates_and_deletions(): void
    {
        Storage::fake('gallery');
        $this->actingAsNovaUser();
        $this->bootNova();

        // Reject >2MB photo inside album_photos
        $tooBig = UploadedFile::fake()->create('too-big.jpg', 2500, 'image/jpeg');
        $this->post('/nova-api/photo-albums', [
            'title' => '',
            'status' => true,
            'sort_order' => 0,
            'album_photos' => [
                0 => ['caption' => 'عکس حجیم', 'image' => $tooBig, 'sort_order' => 0],
            ],
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('album_photos.0.image');

        // Reject disallowed MIME inside album_photos
        $svg = UploadedFile::fake()->create('bad.svg', 10, 'image/svg+xml');
        $this->post('/nova-api/photo-albums', [
            'title' => '',
            'status' => true,
            'sort_order' => 0,
            'album_photos' => [
                0 => ['caption' => 'svg', 'image' => $svg, 'sort_order' => 0],
            ],
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('album_photos.0.image');

        // Reject caption without an image for a new photo row
        $this->post('/nova-api/photo-albums', [
            'title' => '',
            'status' => true,
            'sort_order' => 0,
            'album_photos' => [
                0 => ['caption' => 'فقط متن بدون عکس', 'sort_order' => 0],
            ],
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('album_photos.0.image');

        // Create unnamed album with two photos and captions at once
        $firstFile = UploadedFile::fake()->image('one.jpg', 900, 600);
        $secondFile = UploadedFile::fake()->image('two.png', 800, 600);

        $created = $this->post('/nova-api/photo-albums', [
            'title' => '',
            'description' => 'توضیح آلبوم',
            'status' => true,
            'sort_order' => 0,
            'album_photos' => [
                0 => ['caption' => 'توضیح عکس اول', 'image' => $firstFile, 'sort_order' => 0],
                1 => ['caption' => 'توضیح عکس دوم', 'image' => $secondFile, 'sort_order' => 1],
            ],
        ], ['Accept' => 'application/json'])->assertCreated();

        $album = PhotoAlbum::with('photos')->findOrFail($created->json('id'));
        $this->assertNull($album->title);
        $this->assertCount(2, $album->photos);

        $photoOne = $album->photos->firstWhere('caption', 'توضیح عکس اول');
        $photoTwo = $album->photos->firstWhere('caption', 'توضیح عکس دوم');
        $this->assertNotNull($photoOne);
        $this->assertNotNull($photoTwo);
        Storage::disk('gallery')->assertExists($photoOne->image);
        Storage::disk('gallery')->assertExists($photoOne->thumbnail);
        Storage::disk('gallery')->assertExists($photoTwo->image);

        // Update album: name it, update photo 1 caption, delete photo 2, add photo 3
        $thirdFile = UploadedFile::fake()->image('three.jpg', 1000, 750);
        $this->post('/nova-api/photo-albums/' . $album->id, [
            '_method' => 'PUT',
            'title' => 'آلبوم نام‌گذاری‌شده',
            'description' => 'توضیح آلبوم',
            'status' => true,
            'sort_order' => 0,
            'album_photos' => [
                0 => ['id' => $photoOne->id, 'caption' => 'توضیح ویرایش‌شده عکس اول', 'sort_order' => 0],
                1 => ['caption' => 'توضیح عکس سوم', 'image' => $thirdFile, 'sort_order' => 1],
            ],
            'deleted_photo_ids' => [$photoTwo->id],
        ], ['Accept' => 'application/json'])->assertOk();

        $album->refresh();
        $this->assertSame('آلبوم نام‌گذاری‌شده', $album->title);
        $photos = $album->photos()->get();
        $this->assertCount(2, $photos);
        $this->assertSame('توضیح ویرایش‌شده عکس اول', $photoOne->fresh()->caption);
        $this->assertNull(Photo::find($photoTwo->id));
        Storage::disk('gallery')->assertMissing($photoTwo->image);
        Storage::disk('gallery')->assertMissing($photoTwo->thumbnail);
        $this->assertNotNull($photos->firstWhere('caption', 'توضیح عکس سوم'));
    }
}
