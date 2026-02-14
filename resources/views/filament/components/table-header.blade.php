<style>
    .header-container { padding: 0.75rem 1rem; }
    .header-title {
        font-size: 1.125rem;
        font-weight: 700;
        color: #030712; /* Light mode color */
    }
    .header-desc {
        font-size: 0.875rem;
        color: #6b7280; /* Light mode color */
    }

    /* Deteksi Dark Mode */
    .dark .header-title { color: #ffffff; }
    .dark .header-desc { color: #9ca3af; }
</style>

<div class="header-container">
    <h2 class="header-title">{{ $title }}</h2>
    @if($description)
        <p class="header-desc">{{ $description }}</p>
    @endif
</div>