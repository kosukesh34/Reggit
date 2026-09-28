@foreach(\App\Models\SystemNotice::visible()->get() as $systemNotice)
<section class="alert alert-info" aria-label="店舗向けのお知らせ">
<strong>{{ $systemNotice->title }}</strong>
<div class="notice-markdown">{!! app(\App\Support\NoticeMarkdown::class)->toHtml($systemNotice->body) !!}</div>
</section>
@endforeach
