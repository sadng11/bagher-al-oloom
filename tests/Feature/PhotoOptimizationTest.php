<?php

namespace Tests\Feature;

use App\Models\Photo;
use App\Models\PhotoAlbum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhotoOptimizationTest extends TestCase
{
    use RefreshDatabase;

    private function createJpegBytes(int $width, int $height): string
    {
        $gd = imagecreatetruecolor($width, $height);
        $bg = imagecolorallocate($gd, 40, 90, 140);
        imagefilledrectangle($gd, 0, 0, $width, $height, $bg);
        ob_start();
        imagejpeg($gd, null, 95);
        $bytes = (string) ob_get_clean();
        unset($gd);

        return $bytes;
    }

    private function createTransparentPngBytes(int $width, int $height): string
    {
        $gd = imagecreatetruecolor($width, $height);
        imagealphablending($gd, false);
        imagesavealpha($gd, true);
        $transparent = imagecolorallocatealpha($gd, 0, 0, 0, 127);
        imagefilledrectangle($gd, 0, 0, $width, $height, $transparent);
        $solid = imagecolorallocatealpha($gd, 200, 60, 60, 0);
        imagefilledrectangle($gd, 10, 10, $width - 10, $height - 10, $solid);
        ob_start();
        imagepng($gd);
        $bytes = (string) ob_get_clean();
        unset($gd);

        return $bytes;
    }

    public function test_oversized_jpeg_is_resized_optimized_and_thumbnail_generated(): void
    {
        Storage::fake('gallery');
        Storage::disk('gallery')->put('raw-large.jpg', $this->createJpegBytes(2600, 1300));

        $album = PhotoAlbum::create(['title' => null]);
        $photo = Photo::create([
            'photo_album_id' => $album->id,
            'image' => 'raw-large.jpg',
            'caption' => 'توضیح تصویر بزرگ',
        ]);

        $this->assertNotSame('raw-large.jpg', $photo->image);
        Storage::disk('gallery')->assertMissing('raw-large.jpg');
        Storage::disk('gallery')->assertExists($photo->image);
        Storage::disk('gallery')->assertExists($photo->thumbnail);

        $this->assertSame(1920, $photo->width);
        $this->assertSame(960, $photo->height);
        $this->assertSame('jpg', $photo->original_extension);
        $this->assertGreaterThan(0, $photo->file_size);

        $thumbInfo = getimagesizefromstring(Storage::disk('gallery')->get($photo->thumbnail));
        $this->assertIsArray($thumbInfo);
        $this->assertSame(600, $thumbInfo[0]);
        $this->assertSame(300, $thumbInfo[1]);
    }

    public function test_transparent_png_preserves_alpha_when_webp_unavailable_or_uses_webp(): void
    {
        Storage::fake('gallery');
        Storage::disk('gallery')->put('logo.png', $this->createTransparentPngBytes(400, 400));

        $album = PhotoAlbum::create(['title' => 'آلبوم لوگو']);
        $photo = Photo::create([
            'photo_album_id' => $album->id,
            'image' => 'logo.png',
        ]);

        Storage::disk('gallery')->assertExists($photo->image);
        Storage::disk('gallery')->assertExists($photo->thumbnail);
        $expectedExt = function_exists('imagewebp') ? '.webp' : '.png';
        $this->assertStringEndsWith($expectedExt, $photo->image);
        $this->assertStringEndsWith($expectedExt, $photo->thumbnail);
    }

    public function test_unreadable_codec_falls_back_without_crashing(): void
    {
        Storage::fake('gallery');
        Storage::disk('gallery')->put('photo.avif', 'fake-avif-binary-payload');

        $album = PhotoAlbum::create(['title' => null]);
        $photo = Photo::create([
            'photo_album_id' => $album->id,
            'image' => 'photo.avif',
            'caption' => 'فایل avif',
        ]);

        $this->assertSame('photo.avif', $photo->image);
        $this->assertSame('photo.avif', $photo->thumbnail);
        $this->assertSame('avif', $photo->original_extension);
        Storage::disk('gallery')->assertExists('photo.avif');
    }

    public function test_updating_caption_keeps_existing_files_and_deleting_album_cleans_up_all_files(): void
    {
        Storage::fake('gallery');
        Storage::disk('gallery')->put('cover.jpg', $this->createJpegBytes(800, 600));
        Storage::disk('gallery')->put('first.jpg', $this->createJpegBytes(1000, 800));
        Storage::disk('gallery')->put('second.jpg', $this->createJpegBytes(900, 700));

        $album = PhotoAlbum::create([
            'title' => null,
            'cover_image' => 'cover.jpg',
        ]);

        $photo1 = Photo::create([
            'photo_album_id' => $album->id,
            'image' => 'first.jpg',
            'caption' => 'توضیح اولیه',
        ]);
        $photo2 = Photo::create([
            'photo_album_id' => $album->id,
            'image' => 'second.jpg',
            'caption' => 'تصویر دوم',
        ]);

        $originalImage = $photo1->image;
        $originalThumb = $photo1->thumbnail;

        $photo1->update(['caption' => 'توضیح ویرایش‌شده']);
        $photo1->refresh();
        $this->assertSame($originalImage, $photo1->image);
        $this->assertSame($originalThumb, $photo1->thumbnail);
        Storage::disk('gallery')->assertExists($originalImage);
        Storage::disk('gallery')->assertExists($originalThumb);

        $photo2Image = $photo2->image;
        $photo2Thumb = $photo2->thumbnail;
        $photo2->delete();
        Storage::disk('gallery')->assertMissing($photo2Image);
        Storage::disk('gallery')->assertMissing($photo2Thumb);

        $coverImage = $album->cover_image;
        $coverThumb = $album->cover_thumbnail;
        $album->delete();

        Storage::disk('gallery')->assertMissing($coverImage);
        Storage::disk('gallery')->assertMissing($coverThumb);
        Storage::disk('gallery')->assertMissing($originalImage);
        Storage::disk('gallery')->assertMissing($originalThumb);
    }
}
