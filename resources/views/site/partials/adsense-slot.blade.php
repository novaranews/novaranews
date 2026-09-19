@php
    $adsenseEnabled  = (bool)   \App\Models\Setting::site('adsense_enabled', false);
    $adsenseClientId = (string) \App\Models\Setting::site('adsense_client_id', '');
    $slotType        = $slotType ?? 'display'; // in-article | display
    $slotId          = (string) \App\Models\Setting::site(
        $slotType === 'in-article' ? 'adsense_slot_in_article' : 'adsense_slot_display',
        ''
    );
@endphp
@if($adsenseEnabled && filled($adsenseClientId) && filled($slotId))
    <div class="nv-ad-slot my-6 flex justify-center overflow-hidden" aria-hidden="true">
        <ins class="adsbygoogle"
             style="display:block"
             data-ad-client="{{ $adsenseClientId }}"
             data-ad-slot="{{ $slotId }}"
             @if($slotType === 'in-article')
                 data-ad-format="fluid"
                 data-ad-layout="in-article"
             @else
                 data-ad-format="auto"
                 data-full-width-responsive="true"
             @endif
        ></ins>
        <script>(window.adsbygoogle = window.adsbygoogle || []).push({});</script>
    </div>
@endif
