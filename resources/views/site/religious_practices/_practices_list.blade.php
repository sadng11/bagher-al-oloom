@if($practices->isNotEmpty())
    @php $showNumbers = $practices->count() > 1; @endphp
    <div>
        @foreach($practices as $practice)
            <article class="rp-practice-card">
                <div class="rp-practice-header">
                    @if($showNumbers)
                        <span class="rp-practice-index">{{ $loop->iteration }}</span>
                    @endif
                    <h4 class="rp-practice-title">{{ $practice->title }}</h4>
                </div>
                <div class="rp-practice-body">
                    {!! $practice->content !!}
                </div>
            </article>
        @endforeach
    </div>
@else
    <div class="rp-empty-state">
        <p>{{ $emptyMessage ?? 'در حال حاضر عملی برای انجام در این بخش ثبت نشده است.' }}</p>
    </div>
@endif
