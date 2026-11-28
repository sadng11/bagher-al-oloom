@push('style')
    <style>
        .title-wrap .breadcrumb {
            margin-bottom: 0;
        }

        .rp-main-box {
            background: #ffffff;
            border: 1px solid rgba(0, 0, 0, 0.125);
            padding: 1.5rem;
            border-radius: 0;
        }

        .rp-intro {
            font-size: 0.95rem;
            color: #475569;
            line-height: 1.8;
            margin-bottom: 1.25rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .rp-group {
            margin-bottom: 1.75rem;
        }

        .rp-group:last-child {
            margin-bottom: 0;
        }

        .rp-group-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.9rem;
            padding-right: 0.75rem;
            border-right: 3px solid #24cdd5;
        }

        .rp-group-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
        }

        .rp-group-hint {
            font-size: 0.82rem;
            color: #64748b;
        }

        .rp-section-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 52px;
            padding: 0.75rem 1rem;
            background: #ffffff;
            border: 1px solid #dee2e6;
            color: #1e293b;
            text-decoration: none;
            transition: border-color 0.2s ease, background-color 0.2s ease, color 0.2s ease, box-shadow 0.2s ease;
        }

        .rp-section-link:hover,
        .rp-section-link:focus-visible {
            border-color: #24cdd5;
            background: #f8fafc;
            color: #0f172a;
            box-shadow: 0 4px 12px rgba(36, 205, 213, 0.12);
        }

        .rp-section-link:focus-visible,
        .rp-day-btn:focus-visible,
        .rp-nav-btn:focus-visible,
        .rp-pill-link:focus-visible {
            outline: 2px solid #24cdd5;
            outline-offset: 2px;
        }

        .rp-section-name {
            font-size: 0.96rem;
            font-weight: 600;
        }

        .rp-section-meta {
            font-size: 0.8rem;
            color: #64748b;
            margin-top: 0.15rem;
        }

        .rp-section-arrow {
            color: #24cdd5;
            font-size: 0.85rem;
            flex-shrink: 0;
            margin-right: 0.5rem;
        }

        .rp-special-link {
            background: #f8fafc;
            border-color: #cbd5e1;
        }

        .rp-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding-bottom: 1rem;
            margin-bottom: 1.25rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .rp-page-heading {
            font-size: 1.15rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
        }

        .rp-nav-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            min-height: 40px;
            padding: 0.4rem 0.95rem;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            color: #334155;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
        }

        .rp-nav-btn:hover {
            background: #24cdd5;
            border-color: #24cdd5;
            color: #ffffff;
        }

        .rp-days-grid {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 0.65rem;
        }

        @media (max-width: 991.98px) {
            .rp-days-grid {
                grid-template-columns: repeat(5, minmax(0, 1fr));
            }
        }

        @media (max-width: 575.98px) {
            .rp-days-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        .rp-day-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 54px;
            padding: 0.55rem 0.4rem;
            background: #ffffff;
            border: 1px solid #dee2e6;
            color: #1e293b;
            text-decoration: none;
            transition: border-color 0.2s ease, background-color 0.2s ease, color 0.2s ease;
        }

        .rp-day-btn:hover {
            background: #24cdd5;
            border-color: #24cdd5;
            color: #ffffff;
        }

        .rp-day-number {
            font-size: 1rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .rp-day-label {
            font-size: 0.78rem;
            opacity: 0.85;
            margin-top: 0.15rem;
        }

        .rp-weekday-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1.25rem;
        }

        .rp-pill-link {
            display: inline-flex;
            align-items: center;
            min-height: 38px;
            padding: 0.35rem 0.85rem;
            background: #f8fafc;
            border: 1px solid #dee2e6;
            color: #334155;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
        }

        .rp-pill-link:hover,
        .rp-pill-link.is-active {
            background: #24cdd5;
            border-color: #24cdd5;
            color: #ffffff;
        }

        .rp-practice-card {
            background: #ffffff;
            border: 1px solid #dee2e6;
            margin-bottom: 1rem;
        }

        .rp-practice-card:last-child {
            margin-bottom: 0;
        }

        .rp-practice-header {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.85rem 1.15rem;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }

        .rp-practice-index {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 26px;
            height: 26px;
            padding: 0 0.4rem;
            background: #24cdd5;
            color: #ffffff;
            font-size: 0.8rem;
            font-weight: 700;
            flex-shrink: 0;
        }

        .rp-practice-title {
            font-size: 1rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
            line-height: 1.5;
        }

        .rp-practice-body {
            padding: 1.15rem;
            font-size: 0.97rem;
            line-height: 1.95;
            color: #1e293b;
            text-align: justify;
        }

        .rp-practice-body p:last-child,
        .rp-practice-body ul:last-child,
        .rp-practice-body ol:last-child {
            margin-bottom: 0;
        }

        .rp-empty-state {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 1.75rem 1.25rem;
            text-align: center;
            color: #475569;
        }

        .rp-empty-state p {
            font-size: 0.98rem;
            margin: 0;
        }

        .rp-day-pager {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 0.75rem;
            margin-top: 1.25rem;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
        }

        @media (prefers-reduced-motion: reduce) {
            .rp-section-link,
            .rp-day-btn,
            .rp-nav-btn,
            .rp-pill-link {
                transition: none;
            }
        }
    </style>
@endpush
