<!-- Skeleton Loading Component -->
<div class="skeleton-container grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3">
    <?php for ($i = 0; $i < 12; $i++): ?>
    <div class="skeleton-card bg-gray-800/50 rounded-lg overflow-hidden animate-pulse">
        <!-- Image placeholder -->
        <div class="aspect-[2/3] bg-gray-700"></div>
        
        <!-- Content placeholder -->
        <div class="p-2 space-y-2">
            <!-- Title -->
            <div class="h-3 bg-gray-700 rounded w-3/4"></div>
            <div class="h-3 bg-gray-700 rounded w-1/2"></div>
            
            <!-- Tags -->
            <div class="flex gap-1">
                <div class="h-4 bg-gray-700 rounded w-12"></div>
                <div class="h-4 bg-gray-700 rounded w-10"></div>
            </div>
        </div>
    </div>
    <?php endfor; ?>
</div>

<style>
@keyframes shimmer {
    0% {
        background-position: -1000px 0;
    }
    100% {
        background-position: 1000px 0;
    }
}

.skeleton-card {
    background: linear-gradient(
        90deg,
        rgba(31, 41, 55, 0.5) 0%,
        rgba(55, 65, 81, 0.5) 50%,
        rgba(31, 41, 55, 0.5) 100%
    );
    background-size: 1000px 100%;
    animation: shimmer 2s infinite;
}
</style>
