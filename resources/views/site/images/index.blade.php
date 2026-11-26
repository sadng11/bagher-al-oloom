@extends('site.app')
@section('title', 'تصاویر')
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
                        <h3 class="mb-0">تصاویر</h3>
                        @include('site.partials.breadcrumb', ['crumb_data' => ['تصاویر']])
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="our-services" class="padding bglight">
        <div class="container">
            <div class="gallery-main-box mt-3">
                <div class="gallery-toolbar">
                    <form id="photo-albums-filter"
                          method="GET"
                          action="{{ route('images.index') }}"
                          class="row g-3 align-items-end">
                        <div class="col-lg-6 col-md-5">
                            <label for="album-search" class="form-label gallery-label">جستجو در آلبوم‌ها</label>
                            <div class="input-group">
                                <span class="input-group-text gallery-input-icon"><i class="fa fa-search"></i></span>
                                <input type="text"
                                       name="q"
                                       id="album-search"
                                       class="form-control gallery-input"
                                       value="{{ $search }}"
                                       placeholder="نام آلبوم یا توضیحات را بنویسید...">
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-4">
                            <label for="sort" class="form-label gallery-label">مرتب‌سازی</label>
                            <select name="sort" id="sort" class="form-select gallery-input">
                                <option value="-id" @selected($sort === '-id')>جدیدترین آلبوم‌ها</option>
                                <option value="+id" @selected($sort === '+id')>قدیمی‌ترین آلبوم‌ها</option>
                                <option value="-views" @selected($sort === '-views')>پربازدیدترین</option>
                                <option value="+title" @selected($sort === '+title')>عنوان صعودی</option>
                                <option value="-title" @selected($sort === '-title')>عنوان نزولی</option>
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-3">
                            <button type="submit" class="button gradient-btn w-100 gallery-submit-btn">جستجو</button>
                        </div>
                    </form>
                </div>

                <div class="row g-4 mt-1">
                    @forelse($data as $album)
                        @php
                            $albumPhotoCount = $album->active_photos_count > 0
                                ? (int) $album->active_photos_count
                                : (filled($album->cover_image) ? 1 : 0);
                        @endphp
                        <div class="col-lg-4 col-md-6">
                            <a href="{{ route('images.show', ['id' => $album->id]) }}"
                               class="gallery-album-link"
                               aria-label="مشاهده آلبوم {{ $album->display_title }}">
                                <article class="gallery-album-card">
                                    <div class="gallery-album-media">
                                        @if($album->cover_url)
                                            <img src="{{ $album->cover_url }}"
                                                 alt="{{ $album->display_title }}"
                                                 loading="lazy"
                                                 decoding="async"
                                                 class="gallery-album-img">
                                        @else
                                            <div class="gallery-album-placeholder">
                                                <i class="fa fa-images"></i>
                                                <span>آلبوم تصویر</span>
                                            </div>
                                        @endif

                                        <div class="gallery-album-overlay">
                                            <span class="gallery-album-cta">
                                                <i class="fa fa-folder-open ms-1"></i>
                                                مشاهده آلبوم
                                            </span>
                                        </div>

                                        <div class="gallery-album-badges">
                                            <span class="gallery-pill">
                                                <i class="fa fa-image ms-1"></i>
                                                {{ number_format($albumPhotoCount) }} تصویر
                                            </span>
                                            <span class="gallery-pill">
                                                <i class="fa fa-eye ms-1"></i>
                                                {{ number_format((int) $album->views) }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="gallery-album-info">
                                        <h4 class="gallery-album-title">{{ $album->display_title }}</h4>
                                        @if(filled($album->description))
                                            <p class="gallery-album-desc">{{ \Illuminate\Support\Str::limit($album->description, 95) }}</p>
                                        @endif
                                    </div>
                                </article>
                            </a>
                        </div>
                    @empty
                        @include('site.partials.empty_result')
                    @endforelse
                </div>

                @if($data->hasPages())
                    <div class="row mt-4">
                        <div class="col-12 d-flex justify-content-center">
                            {{ $data->links('pagination::bootstrap-5') }}
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
            .gallery-main-box {
                background: #ffffff;
                border: 1px solid rgba(0, 0, 0, 0.125);
                padding: 1.5rem;
                border-radius: 0;
            }
            .gallery-toolbar {
                padding-bottom: 1.25rem;
                border-bottom: 1px solid #e2e8f0;
                margin-bottom: 0.5rem;
            }
            .gallery-label {
                font-size: 0.875rem;
                font-weight: 600;
                color: #334155;
                margin-bottom: 0.45rem;
            }
            .gallery-input-icon,
            .gallery-input,
            .gallery-submit-btn {
                border-radius: 0 !important;
            }
            .gallery-input-icon {
                background: #f8fafc;
                border-color: #ced4da;
                color: #64748b;
            }
            .gallery-input {
                min-height: 44px;
                border-color: #ced4da;
                font-size: 0.95rem;
            }
            .gallery-input:focus {
                border-color: #24cdd5;
                box-shadow: 0 0 0 0.2rem rgba(36, 205, 213, 0.18);
            }
            .gallery-submit-btn {
                min-height: 44px;
                padding: 0.5rem 1.25rem !important;
            }
            .gallery-album-link {
                display: block;
                height: 100%;
                text-decoration: none;
                color: inherit;
            }
            .gallery-album-link:focus-visible {
                outline: 2px solid #24cdd5;
                outline-offset: 2px;
            }
            .gallery-album-card {
                height: 100%;
                background: #ffffff;
                border-radius: 0;
                overflow: hidden;
                border: 1px solid rgba(0, 0, 0, 0.125);
                box-shadow: 0 4px 14px rgba(15, 23, 42, 0.05);
                transition: transform 0.25s ease, box-shadow 0.25s ease;
                display: flex;
                flex-direction: column;
            }
            .gallery-album-link:hover .gallery-album-card {
                transform: translateY(-3px);
                box-shadow: 0 12px 26px rgba(15, 23, 42, 0.1);
            }
            .gallery-album-media {
                position: relative;
                aspect-ratio: 4 / 3;
                overflow: hidden;
                background: #f1f5f9;
            }
            .gallery-album-img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                display: block;
                transition: transform 0.35s ease;
            }
            .gallery-album-link:hover .gallery-album-img {
                transform: scale(1.05);
            }
            .gallery-album-placeholder {
                width: 100%;
                height: 100%;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                color: #94a3b8;
                gap: 0.5rem;
            }
            .gallery-album-placeholder i {
                font-size: 2.5rem;
            }
            .gallery-album-overlay {
                position: absolute;
                inset: 0;
                background: linear-gradient(135deg, rgba(35, 206, 213, 0.72), rgba(81, 146, 225, 0.78));
                display: flex;
                align-items: center;
                justify-content: center;
                opacity: 0;
                transition: opacity 0.25s ease;
            }
            .gallery-album-link:hover .gallery-album-overlay {
                opacity: 1;
            }
            .gallery-album-cta {
                color: #ffffff;
                background: rgba(15, 23, 42, 0.55);
                padding: 0.45rem 1.1rem;
                border-radius: 0;
                font-size: 0.9rem;
                font-weight: 600;
            }
            .gallery-album-badges {
                position: absolute;
                bottom: 10px;
                right: 10px;
                left: 10px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                pointer-events: none;
            }
            .gallery-pill {
                background: rgba(15, 23, 42, 0.78);
                color: #ffffff;
                font-size: 0.78rem;
                padding: 0.25rem 0.65rem;
                border-radius: 0;
            }
            .gallery-album-info {
                padding: 1rem 1.15rem;
                border-top: 1px solid rgba(0, 0, 0, 0.08);
                flex: 1;
                display: flex;
                flex-direction: column;
                gap: 0.35rem;
            }
            .gallery-album-title {
                font-size: 1.05rem;
                font-weight: 700;
                color: #1e293b;
                margin: 0;
                line-height: 1.55;
            }
            .gallery-album-desc {
                font-size: 0.875rem;
                color: #64748b;
                margin: 0;
                line-height: 1.7;
            }
            @media (prefers-reduced-motion: reduce) {
                .gallery-album-card,
                .gallery-album-img,
                .gallery-album-overlay {
                    transition: none;
                }
            }
        </style>
    @endpush

    @push('script')
        <script>
            $(document).ready(function () {
                $('#sort').on('change', function () {
                    $('#photo-albums-filter').submit();
                });
            });
        </script>
    @endpush
@endsection
