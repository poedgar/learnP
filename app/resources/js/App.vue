<template>
    <a href="#main" class="skip-link">Skip to content</a>
    <header class="site-header">
        <div class="container bar">
            <RouterLink to="/" class="wordmark">learn<span>P</span></RouterLink>
            <nav aria-label="Main">
                <RouterLink to="/">Courses</RouterLink>
            </nav>
        </div>
    </header>

    <main id="main" class="container">
        <RouterView />
    </main>
</template>

<style>
.container {
    width: 100%;
    max-width: 76rem;
    margin: 0 auto;
    padding: 0 1.5rem;
}

@media (max-width: 640px) {
    .container {
        padding: 0 1rem;
    }
}

.skip-link {
    position: absolute;
    left: 1rem;
    top: -3rem;
    padding: 0.5rem 1rem;
    background: var(--highlight);
    color: var(--ink);
    font-weight: 700;
    border-radius: var(--radius);
    z-index: 10;
}

.skip-link:focus {
    top: 0.75rem;
}

.site-header {
    border-bottom: 1px solid var(--line);
    background: var(--paper);
}

.bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 4rem;
}

.wordmark {
    font-family: var(--font-display);
    font-size: 1.5rem;
    font-weight: 800;
    letter-spacing: -0.02em;
    color: var(--ink);
}

.wordmark span {
    display: inline-block;
    margin-left: 1px;
    padding: 0 0.2rem;
    background: var(--highlight);
    border-radius: var(--radius-sm);
}

.site-header nav a {
    font-weight: 700;
    color: var(--ink-soft);
}

.site-header nav a:hover {
    color: var(--ink);
}
</style>
