@if($capped)
    <div class="warn-box">
        ⚠ Se muestran {{ $cap }} de {{ $totalMatching }} registros que cumplen el filtro.
    </div>
@endif
