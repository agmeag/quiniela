<script lang="ts">
  let { count = 1 }: { count?: number } = $props();
</script>

{#each Array(count) as _}
  <div class="bg-white border border-[#E0E0E0] p-4 animate-pulse">
    <div class="h-3 bg-[#F0F0F0] rounded w-1/3 mb-4"></div>
    <div class="flex items-center justify-between gap-4">
      <div class="flex-1 space-y-2">
        <div class="h-4 bg-[#F0F0F0] rounded w-3/4 ml-auto"></div>
        <div class="h-3 bg-[#F0F0F0] rounded w-1/2 ml-auto"></div>
      </div>
      <div class="h-8 w-16 bg-[#F0F0F0] rounded"></div>
      <div class="flex-1 space-y-2">
        <div class="h-4 bg-[#F0F0F0] rounded w-3/4"></div>
        <div class="h-3 bg-[#F0F0F0] rounded w-1/2"></div>
      </div>
    </div>
  </div>
{/each}
