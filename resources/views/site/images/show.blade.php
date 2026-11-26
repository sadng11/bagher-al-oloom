@extends('site.app')
@section('title', $album->display_title)
@section('content')
    <section id="main-banner-page"
             class="position-relative page-header service-detail-header section-nav-smooth parallax">
        <div class="overlay overlay-dark opacity-7 z-index-1"></div>
        <div class="container">
            <div class="row shift-tole">
                <div class="col-lg-8 offset-lg-2">
                    <div class="page-titles whitecolor text-center padding_top padding_bottom">
                    </div>
                </div>
            </div>
            <div class="gradient-bg title-wrap">
                <div class="row">
                    <div class="col-lg-12 col-md-12 whitecolor d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <h3 class="mb-0">{{ $album->display_title }}</h3>
                        @include('site.partials.breadcrumb', ['crumb_data' => [route('images.index') => 'تصاویر', $album->display_title]])
                    </div>
                </div>
            </div>
        </div>
    </section>

    @php
        $galleryItems = $photos->isNotEmpty()
            ? $photos
            : ($fallbackCoverPhoto ? collect([$fallbackCoverPhoto]) : collect());
    @endphp

    <section id="our-services" class="padding bglight">
        <div class="container">
            <div class="album-main-box mt-3">
                <div class="album-header-section">
                    <div class="album-header-top">
                        <a href="{{ route('images.index') }}" class="album-back-link">
                            <i class="fa fa-arrow-right ms-1"></i>
                            بازگشت به همه آلبوم‌ها
                        </a>
                        <div class="album-stats">
                            <span class="album-stat-badge">
                                <i class="fa fa-image ms-1"></i>
                                {{ number_format($displayPhotoCount) }} تصویر
                            </span>
                            <span class="album-stat-badge">
                                <i class="fa fa-eye ms-1"></i>
                                بازدید: {{ number_format((int) $album->views) }}
                            </span>
                        </div>
                    </div>

                    <h2 class="album-header-title">{{ $album->display_title }}</h2>
                    @if(filled($album->description))
                        <p class="album-header-desc">{{ $album->description }}</p>
                    @endif
                </div>

                <div class="row g-4">
                    @forelse($galleryItems as $photo)
                        <div class="col-lg-4 col-md-6">
                            <figure class="photo-gallery-tile">
                                <a href="{{ $photo->image_url }}"
                                   data-fancybox="album-{{ $album->id }}"
                                   data-caption="{{ e($photo->caption ?? '') }}"
                                   class="photo-tile-link"
                                   aria-label="{{ filled($photo->caption) ? e(\Illuminate\Support\Str::limit($photo->caption, 80)) : 'نمایش بزرگ تصویر' }}">
                                    <img src="{{ $photo->thumbnail_url }}"
                                         alt="{{ e($photo->caption ?? $album->display_title) }}"
                                         loading="lazy"
                                         decoding="async"
                                         class="photo-tile-img">
                                    <span class="photo-tile-overlay" aria-hidden="true">
                                        <span class="photo-tile-zoom-btn">
                                            <i class="fa fa-search-plus"></i>
                                        </span>
                                    </span>
                                </a>
                                @if(filled($photo->caption))
                                    <figcaption class="photo-tile-caption">
                                        {{ $photo->caption }}
                                    </figcaption>
                                @endif
                            </figure>
                        </div>
                    @empty
                        @include('site.partials.empty_result')
                    @endforelse
                </div>

                @if($photos->hasPages())
                    <div class="row mt-4">
                        <div class="col-12 d-flex justify-content-center">
                            {{ $photos->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>

    @push('style')
        <style>
            .title-wrap .breadcrumb {
                margin-bottom: 0;
            }
            .album-main-box {
                background: #ffffff;
                border: 1px solid rgba(0, 0, 0, 0.125);
                padding: 1.75rem;
                border-radius: 0;
            }
            .album-header-section {
                padding-bottom: 1.25rem;
                margin-bottom: 1.5rem;
                border-bottom: 1px solid #e2e8f0;
            }
            .album-header-top {
                display: flex;
                flex-wrap: wrap;
                justify-content: space-between;
                align-items: center;
                gap: 0.75rem;
                margin-bottom: 1rem;
            }
            .album-back-link {
                display: inline-flex;
                align-items: center;
                min-height: 36px;
                padding: 0.35rem 0.95rem;
                border-radius: 0;
                background: #f1f5f9;
                border: 1px solid #cbd5e1;
                color: #334155;
                font-size: 0.875rem;
                font-weight: 600;
                text-decoration: none;
                transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease;
            }
            .album-back-link:hover {
                background: #24cdd5;
                border-color: #24cdd5;
                color: #ffffff;
            }
            .album-stats {
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            .album-stat-badge {
                display: inline-flex;
                align-items: center;
                padding: 0.35rem 0.85rem;
                border-radius: 0;
                background: #f8fafc;
                border: 1px solid #cbd5e1;
                color: #475569;
                font-size: 0.84rem;
                font-weight: 500;
            }
            .album-header-title {
                font-size: 1.35rem;
                font-weight: 700;
                color: #0f172a;
                margin: 0 0 0.45rem;
                line-height: 1.55;
            }
            .album-header-desc {
                font-size: 0.95rem;
                color: #475569;
                line-height: 1.75;
                margin: 0;
            }
            .photo-gallery-tile {
                margin: 0;
                height: 100%;
                background: #ffffff;
                border-radius: 0;
                overflow: hidden;
                border: 1px solid rgba(0, 0, 0, 0.125);
                box-shadow: 0 4px 14px rgba(15, 23, 42, 0.05);
                display: flex;
                flex-direction: column;
                transition: transform 0.25s ease, box-shadow 0.25s ease;
            }
            .photo-gallery-tile:hover {
                transform: translateY(-3px);
                box-shadow: 0 12px 26px rgba(15, 23, 42, 0.1);
            }
            .photo-tile-link {
                position: relative;
                display: block;
                aspect-ratio: 4 / 3;
                overflow: hidden;
                background: #f1f5f9;
                cursor: pointer;
            }
            .photo-tile-link:focus-visible {
                outline: 2px solid #24cdd5;
                outline-offset: -2px;
            }
            .photo-tile-img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                display: block;
                transition: transform 0.35s ease;
            }
            .photo-gallery-tile:hover .photo-tile-img {
                transform: scale(1.05);
            }
            .photo-tile-overlay {
                position: absolute;
                inset: 0;
                background: radial-gradient(rgba(35, 206, 213, 0.72), rgba(81, 146, 225, 0.8));
                display: flex;
                align-items: center;
                justify-content: center;
                opacity: 0;
                transition: opacity 0.25s ease;
            }
            .photo-tile-link:hover .photo-tile-overlay,
            .photo-tile-link:focus-visible .photo-tile-overlay {
                opacity: 1;
            }
            .photo-tile-zoom-btn {
                width: 48px;
                height: 48px;
                border-radius: 50%;
                background: #ffffff;
                color: #1e293b;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 1.15rem;
                box-shadow: 0 6px 16px rgba(0, 0, 0, 0.18);
            }
            .photo-tile-caption {
                padding: 0.9rem 1.1rem;
                border-top: 1px solid rgba(0, 0, 0, 0.08);
                background: #ffffff;
                font-size: 0.92rem;
                line-height: 1.75;
                color: #334155;
                flex: 1;
            }
            @media (prefers-reduced-motion: reduce) {
                .photo-gallery-tile,
                .photo-tile-img,
                .photo-tile-overlay {
                    transition: none;
                }
            }
        </style>
    @endpush

    @push('script')
        <script>
            $(document).ready(function () {
                $('[data-fancybox="album-{{ $album->id }}"]').fancybox({
                    buttons: ['zoom', 'slideShow', 'fullScreen', 'thumbs', 'close'],
                    loop: true,
                    protect: true
                });
            });
        </script>
    @endpush
@endsection
