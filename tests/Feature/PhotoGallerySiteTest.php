<?php

namespace Tests\Feature;

use App\Models\Photo;
use App\Models\PhotoAlbum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhotoGallerySiteTest extends TestCase
{
    use RefreshDatabase;

    private function putSampleImage(string $filename, int $width = 800, int $height = 600): string
    {
        $gd = imagecreatetruecolor($width, $height);
        $color = imagecolorallocate($gd, 60, 120, 180);
        imagefilledrectangle($gd, 0, 0, $width, $height, $color);
        ob_start();
        imagejpeg($gd, null, 85);
        $bytes = (string) ob_get_clean();
        unset($gd);

        Storage::disk('gallery')->put($filename, $bytes);

        return $filename;
    }

    public function test_site_header_renders_images_navigation_link(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('تصاویر')
            ->assertSee(route('images.index'));

        $imagesHref = route('images.index');
        $anchorCount = preg_match_all(
            '/<a\b[^>]*\bhref="' . preg_quote($imagesHref, '/') . '"[^>]*>/',
            $response->getContent()
        );

        $this->assertGreaterThanOrEqual(2, $anchorCount);
        $this->assertSame('/storage/gallery/sample.jpg', Storage::disk('gallery')->url('sample.jpg'));
    }

    public function test_images_index_lists_published_albums_including_untitled_with_views_and_filtering(): void
    {
        Storage::fake('gallery');

        $emptyResponse = $this->get('/images');
        $emptyResponse->assertOk()
            ->assertViewIs('site.images.index')
            ->assertSee('هیچ داده ای در این قسمت وجود ندارد.');

        $unnamedAlbum = PhotoAlbum::create([
            'title' => null,
            'views' => 42,
            'status' => true,
        ]);
        Photo::create([
            'photo_album_id' => $unnamedAlbum->id,
            'image' => $this->putSampleImage('u1.jpg'),
            'caption' => 'عکس اول',
            'status' => true,
        ]);

        $namedAlbum = PhotoAlbum::create([
            'title' => 'همایش علمی',
            'description' => 'گزارش تصویری همایش',
            'views' => 150,
            'status' => true,
        ]);

        $hiddenAlbum = PhotoAlbum::create([
            'title' => 'آلبوم مخفی',
            'status' => false,
        ]);

        $indexResponse = $this->get('/images');
        $indexResponse->assertOk()
            ->assertSee(PhotoAlbum::UNTITLED_LABEL)
            ->assertSee('همایش علمی')
            ->assertSee('150')
            ->assertSee('42')
            ->assertSee('1 تصویر')
            ->assertSee(route('images.show', ['id' => $unnamedAlbum->id]))
            ->assertDontSee($hiddenAlbum->title);

        $this->get('/images?q=' . urlencode('همایش'))
            ->assertOk()
            ->assertSee('همایش علمی')
            ->assertDontSee(PhotoAlbum::UNTITLED_LABEL);

        $this->get('/images?sort=-views')
            ->assertOk()
            ->assertSeeInOrder(['همایش علمی', PhotoAlbum::UNTITLED_LABEL]);

        $this->get('/images?sort=invalid-field')
            ->assertOk()
            ->assertViewHas('sort', '-id');
    }

    public function test_album_show_displays_active_photos_with_captions_lazy_loading_no_download_and_tracks_views(): void
    {
        Storage::fake('gallery');

        $hiddenAlbum = PhotoAlbum::create([
            'title' => 'مخفی',
            'status' => false,
        ]);
        $this->get(route('images.show', ['id' => $hiddenAlbum->id]))->assertNotFound();

        $album = PhotoAlbum::create([
            'title' => null,
            'description' => 'توضیح آلبوم آزمایشی',
            'views' => 10,
            'status' => true,
        ]);

        $activePhoto = Photo::create([
            'photo_album_id' => $album->id,
            'image' => $this->putSampleImage('active.jpg'),
            'caption' => 'توضیح عکس قابل مشاهده',
            'status' => true,
        ]);

        $inactivePhoto = Photo::create([
            'photo_album_id' => $album->id,
            'image' => $this->putSampleImage('inactive.jpg'),
            'caption' => 'توضیح عکس مخفی',
            'status' => false,
        ]);

        $response = $this->get(route('images.show', ['id' => $album->id]));
        $response->assertOk()
            ->assertViewIs('site.images.show')
            ->assertSee(PhotoAlbum::UNTITLED_LABEL)
            ->assertSee('توضیح آلبوم آزمایشی')
            ->assertSee($activePhoto->thumbnail_url)
            ->assertSee($activePhoto->image_url)
            ->assertSee('توضیح عکس قابل مشاهده')
            ->assertSee('loading="lazy"', false)
            ->assertSee('data-fancybox="album-' . $album->id . '"', false)
            ->assertSee('11')
            ->assertDontSee('توضیح عکس مخفی')
            ->assertDontSee($inactivePhoto->image_url);

        $this->assertStringNotContainsString("'download'", $response->getContent());
        $this->assertSame(11, $album->fresh()->views);

        // Reload in same session does not double-increment views
        $this->get(route('images.show', ['id' => $album->id]))->assertOk();
        $this->assertSame(11, $album->fresh()->views);
    }

    public function test_album_with_only_cover_image_displays_cover_in_gallery_instead_of_empty_state(): void
    {
        Storage::fake('gallery');

        $coverOnlyAlbum = PhotoAlbum::create([
            'title' => 'آلبوم دارای جلد',
            'description' => 'توضیح جلد آلبوم',
            'cover_image' => $this->putSampleImage('album-cover.jpg'),
            'status' => true,
        ]);

        $this->get('/images')
            ->assertOk()
            ->assertSee('1 تصویر');

        $this->get(route('images.show', ['id' => $coverOnlyAlbum->id]))
            ->assertOk()
            ->assertSee($coverOnlyAlbum->cover_url)
            ->assertSee('توضیح جلد آلبوم')
            ->assertSee('بازگشت به همه آلبوم‌ها')
            ->assertDontSee('هیچ داده ای در این قسمت وجود ندارد.');
    }
}
