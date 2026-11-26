<?php

namespace App\Http\Controllers;

use App\Models\Photo;
use App\Models\PhotoAlbum;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PhotoGalleryController extends Controller
{
    /**
     * Display the paginated public listing of published photo albums.
     */
    public function index(Request $request): View
    {
        $rawSearch = $request->query('q', '');
        $search = is_string($rawSearch) ? trim($rawSearch) : '';

        $allowedSortFields = ['id', 'title', 'views', 'sort_order'];
        $rawSort = $request->query('sort', '-id');
        $trimmedSort = is_string($rawSort) ? trim($rawSort) : '';

        $hasSign = str_starts_with($trimmedSort, '-') || str_starts_with($trimmedSort, '+');
        $candidatePrefix = str_starts_with($trimmedSort, '-') ? '-' : '+';
        $candidateField = $hasSign ? substr($trimmedSort, 1) : $trimmedSort;

        if (! in_array($candidateField, $allowedSortFields, true)) {
            $sort = '-id';
            $sortDirection = 'desc';
            $sortField = 'id';
        } else {
            $sort = $candidatePrefix . $candidateField;
            $sortDirection = $candidatePrefix === '-' ? 'desc' : 'asc';
            $sortField = $candidateField;
        }

        $albums = PhotoAlbum::getPublicAlbums(
            $search !== '' ? $search : null,
            $sortField,
            $sortDirection
        )->withQueryString();

        return view('site.images.index', [
            'data' => $albums,
            'search' => $search,
            'sort' => $sort,
        ]);
    }

    /**
     * Display a published photo album and its active photos.
     */
    public function show(Request $request, int $id): View
    {
        $album = PhotoAlbum::published()
            ->withCount('activePhotos')
            ->findOrFail($id);

        $sessionKey = 'viewed_photo_album_' . $album->id;
        if (! $request->session()->has($sessionKey)) {
            $album->increment('views');
            $request->session()->put($sessionKey, true);
        }

        $photos = $album->activePhotos()->paginate(24);

        // If an album only has a cover image and no child photos yet, expose it as a single gallery item.
        $fallbackCoverPhoto = null;
        if ($photos->isEmpty() && filled($album->cover_image)) {
            $fallbackCoverPhoto = new Photo([
                'photo_album_id' => $album->id,
                'image' => $album->cover_image,
                'thumbnail' => $album->cover_thumbnail ?: $album->cover_image,
                'caption' => $album->description,
            ]);
        }

        $displayPhotoCount = $album->active_photos_count > 0
            ? (int) $album->active_photos_count
            : ($fallbackCoverPhoto ? 1 : 0);

        return view('site.images.show', [
            'album' => $album,
            'photos' => $photos,
            'fallbackCoverPhoto' => $fallbackCoverPhoto,
            'displayPhotoCount' => $displayPhotoCount,
        ]);
    }
}
