@props([
    'title' => 'Mi Perfil',
    'subtitle' => '',
    'editRoute' => null,
    'showEditButton' => true,
])

<div style="display:flex;flex-wrap:wrap;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:1.25rem;">
    <div>
        <h1 class="pp-page-title" style="margin-bottom:0.25rem;">{{ $title }}</h1>
        @if($subtitle !== '')
            <p class="pp-page-sub" style="margin:0;">{{ $subtitle }}</p>
        @endif
    </div>
    @if($showEditButton && $editRoute)
        <a href="{{ $editRoute }}" class="btn btn-primary">Editar cuenta</a>
    @endif
</div>
