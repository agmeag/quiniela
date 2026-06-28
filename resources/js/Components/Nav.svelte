<script lang="ts">
  import { usePage } from '@inertiajs/svelte';

  const page = usePage();
  let menuOpen = $state(false);

  const links = [
    { label: 'Inicio', href: '/' },
    { label: 'Partidos', href: '/matches' },
    { label: 'Podio', href: '/podium' },
    { label: 'Clasificación', href: '/leaderboard' },
  ];

  function isActive(href: string): boolean {
    if (href === '/') return page.url === '/';
    return page.url.startsWith(href);
  }

  const auth = $derived((page.props as any).auth as { user: { name: string; role: string; participant_slug: string | null } | null });
</script>

<nav class="sticky top-0 z-50 bg-white border-b border-[#E0E0E0]">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between h-16">
      <a href="/" class="flex items-center gap-3">
        <div class="w-8 h-8 bg-[#081B6A] flex items-center justify-center">
          <span class="text-white text-xs font-black tracking-tighter">Q26</span>
        </div>
        <span class="font-black text-[#081B6A] text-sm tracking-widest uppercase hidden sm:block">
          Quiniela 2026
        </span>
      </a>

      <!-- Desktop nav -->
      <div class="hidden md:flex items-center gap-1">
        {#each links as link}
          <a
            href={link.href}
            class="px-4 py-2 text-sm font-semibold tracking-wide transition-colors
              {isActive(link.href)
                ? 'text-[#3554FF] border-b-2 border-[#3554FF]'
                : 'text-[#081B6A] hover:text-[#3554FF]'}"
          >
            {link.label}
          </a>
        {/each}

        {#if auth.user}
          <span class="w-px h-5 bg-[#E0E0E0] mx-2"></span>
          {#if auth.user.role === 'super_admin' || auth.user.role === 'admin'}
            <a
              href="/admin"
              class="px-4 py-2 text-sm font-semibold tracking-wide transition-colors
                {page.url.startsWith('/admin') ? 'text-[#3554FF] border-b-2 border-[#3554FF]' : 'text-[#081B6A] hover:text-[#3554FF]'}"
            >
              Admin
            </a>
          {:else if auth.user.role === 'participant'}
            <a
              href="/mis-predicciones"
              class="px-4 py-2 text-sm font-semibold tracking-wide transition-colors
                {isActive('/mis-predicciones') ? 'text-[#3554FF] border-b-2 border-[#3554FF]' : 'text-[#081B6A] hover:text-[#3554FF]'}"
            >
              Mis predicciones
            </a>
          {/if}
          <form method="post" action="/logout" class="inline">
            <input type="hidden" name="_token" value={(page.props as any).csrf_token} />
            <button
              type="submit"
              class="px-4 py-2 text-sm font-semibold text-[#9CA3AF] hover:text-red-500 transition-colors"
            >
              Salir
            </button>
          </form>
        {/if}
      </div>

      <!-- Mobile hamburger -->
      <button
        class="md:hidden p-2"
        onclick={() => menuOpen = !menuOpen}
        aria-label="Toggle menu"
      >
        <div class="w-5 h-0.5 bg-[#081B6A] mb-1.5 transition-all {menuOpen ? 'rotate-45 translate-y-2' : ''}"></div>
        <div class="w-5 h-0.5 bg-[#081B6A] mb-1.5 transition-all {menuOpen ? 'opacity-0' : ''}"></div>
        <div class="w-5 h-0.5 bg-[#081B6A] transition-all {menuOpen ? '-rotate-45 -translate-y-2' : ''}"></div>
      </button>
    </div>
  </div>

  {#if menuOpen}
    <div class="md:hidden border-t border-[#E0E0E0] bg-white">
      {#each links as link}
        <a
          href={link.href}
          onclick={() => menuOpen = false}
          class="block px-6 py-4 text-sm font-semibold text-[#081B6A] hover:bg-[#F6F6F6] hover:text-[#3554FF]
            {isActive(link.href) ? 'text-[#3554FF] bg-[#F6F6F6]' : ''}"
        >
          {link.label}
        </a>
      {/each}

      {#if auth.user}
        <div class="border-t border-[#F0F0F0]">
          {#if auth.user.role === 'super_admin' || auth.user.role === 'admin'}
            <a
              href="/admin"
              onclick={() => menuOpen = false}
              class="block px-6 py-4 text-sm font-semibold text-[#081B6A] hover:bg-[#F6F6F6] hover:text-[#3554FF]"
            >
              Admin
            </a>
          {:else if auth.user.role === 'participant'}
            <a
              href="/mis-predicciones"
              onclick={() => menuOpen = false}
              class="block px-6 py-4 text-sm font-semibold text-[#081B6A] hover:bg-[#F6F6F6] hover:text-[#3554FF]"
            >
              Mis predicciones
            </a>
          {/if}
          <form method="post" action="/logout">
            <input type="hidden" name="_token" value={(page.props as any).csrf_token} />
            <button type="submit" class="block w-full text-left px-6 py-4 text-sm font-semibold text-red-500 hover:bg-[#FFF5F5]">
              Salir
            </button>
          </form>
        </div>
      {/if}
    </div>
  {/if}
</nav>
