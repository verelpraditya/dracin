<?php
require_once 'config.php';
$pageTitle = 'Home';
require_once 'includes/header.php';
?>

<!-- Main Content -->
<main class="container mx-auto px-4 py-8" x-data="dramaApp()">
    
    <!-- Platform Tabs -->
    <div class="mb-8">
        <div class="flex flex-wrap gap-4 justify-center lg:justify-start">
            <?php foreach ($platforms as $key => $platform): ?>
            <button 
                @click="switchPlatform('<?php echo $key; ?>')"
                :class="activePlatform === '<?php echo $key; ?>' ? 'bg-gradient-to-r <?php echo $platform['color']; ?> text-white' : 'bg-gray-800 text-gray-300 hover:bg-gray-700'"
                class="group relative flex items-center gap-3 px-6 py-3 rounded-xl transition-all duration-300 transform hover:scale-105"
            >
                <!-- Icon -->
                <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-white/10">
                    <span class="text-2xl"><?php echo $platform['icon']; ?></span>
                </div>
                
                <!-- Platform Name -->
                <div class="flex flex-col items-start">
                    <span class="font-bold text-lg"><?php echo $platform['name']; ?></span>
                    <span class="text-xs opacity-75" x-show="activePlatform === '<?php echo $key; ?>'">Eksklusif</span>
                </div>
                
                <!-- Active Indicator -->
                <div 
                    x-show="activePlatform === '<?php echo $key; ?>'"
                    class="absolute -top-1 -right-1 w-3 h-3 bg-green-400 rounded-full border-2 border-gray-950"
                ></div>
            </button>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Content Area -->
    <div class="relative min-h-screen">
        
        <!-- Search Info Banner -->
        <div x-show="isSearchMode" x-transition class="mb-6 bg-gray-800 rounded-lg p-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <div>
                    <p class="text-white font-medium">Hasil pencarian untuk "<span x-text="searchQuery"></span>"</p>
                    <p class="text-gray-400 text-sm">di platform <span x-text="getPlatformName(activePlatform)"></span></p>
                </div>
            </div>
            <button 
                @click="isSearchMode = false; searchQuery = ''; loadPlatformData(activePlatform)"
                class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg text-sm transition"
            >
                Tutup
            </button>
        </div>
        
        <!-- Loading Skeleton -->
        <div x-show="loading" x-transition>
            <?php include 'includes/skeleton.php'; ?>
        </div>
        
        <!-- Drama Grid -->
        <div x-show="!loading" x-transition class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3">
            
            <template x-for="drama in dramas" :key="drama.id">
                <a :href="'watch.php?id=' + drama.bookId + '&ep=1&platform=' + activePlatform" class="group relative bg-gray-800 rounded-lg overflow-hidden hover:ring-2 hover:ring-cyan-500 transition-all duration-300 cursor-pointer block">
                    
                    <!-- Drama Poster -->
                    <div class="relative aspect-[2/3] overflow-hidden">
                        <img 
                            :src="drama.poster" 
                            :alt="drama.title"
                            class="w-full h-full object-cover"
                            loading="lazy"
                        >
                        
                        <!-- Badge Platform -->
                        <div class="absolute top-2 left-2 px-2 py-0.5 rounded-full text-[10px] font-bold bg-gradient-to-r text-white backdrop-blur-sm"
                             :class="getPlatformColor(activePlatform)">
                            <span x-text="getPlatformIcon(activePlatform)"></span>
                            <span x-text="getPlatformName(activePlatform)" class="ml-0.5"></span>
                        </div>
                        
                        <!-- Play Button Overlay -->
                        <div class="absolute inset-0 bg-black/60 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                            <div class="w-16 h-16 bg-cyan-500 rounded-full flex items-center justify-center">
                                <svg class="w-8 h-8 text-white ml-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Drama Info -->
                    <div class="p-2">
                        <!-- Title -->
                        <h3 class="font-semibold text-sm mb-1.5 line-clamp-2" x-text="drama.title"></h3>
                        
                        <!-- Tags -->
                        <div class="flex flex-wrap gap-1 mb-2">
                            <template x-for="(tag, index) in drama.tags.slice(0, 2)" :key="tag">
                                <span class="px-1.5 py-0.5 bg-gray-700 text-[10px] rounded" x-text="tag"></span>
                            </template>
                        </div>
                        
                        <!-- Additional Info -->
                        <div x-show="activePlatform !== 'netshort' && drama.episodes > 0" class="flex items-center justify-between text-[10px] text-gray-400">
                            <span x-text="drama.episodes + ' Ep'"></span>
                        </div>
                    </div>
                </a>
            </template>
            
        </div>
        
        <!-- Empty State -->
        <div x-show="!loading && dramas.length === 0" class="text-center py-20">
            <svg class="w-24 h-24 mx-auto text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" x-show="isSearchMode"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" x-show="!isSearchMode"/>
            </svg>
            <h3 class="text-2xl font-bold mb-2" x-text="isSearchMode ? 'Tidak Ditemukan' : 'Tidak Ada Drama'"></h3>
            <p class="text-gray-400" x-text="isSearchMode ? 'Tidak ada hasil untuk pencarian Anda. Coba kata kunci lain.' : 'Belum ada konten yang tersedia untuk platform ini.'"></p>
        </div>
        
    </div>
    
</main>

<script>
function dramaApp() {
    return {
        activePlatform: 'dramabox',
        dramas: [],
        loading: false,
        isSearchMode: false,
        searchQuery: '',
        
        init() {
            // Load initial platform data
            this.loadPlatformData('dramabox');
            
            // Listen for search events
            window.addEventListener('search', (e) => {
                this.handleSearch(e.detail.query);
            });
        },
        
        switchPlatform(platform) {
            if (this.activePlatform !== platform) {
                this.activePlatform = platform;
                this.isSearchMode = false;
                this.searchQuery = '';
                this.loadPlatformData(platform);
            }
        },
        
        async handleSearch(query) {
            if (!query || query.trim() === '') {
                // If empty, load normal platform data
                this.isSearchMode = false;
                this.searchQuery = '';
                this.loadPlatformData(this.activePlatform);
                return;
            }
            
            this.searchQuery = query.trim();
            this.isSearchMode = true;
            this.loading = true;
            this.dramas = [];
            
            try {
                const response = await fetch(`api/search.php?platform=${this.activePlatform}&query=${encodeURIComponent(this.searchQuery)}`);
                const data = await response.json();
                
                if (data.success) {
                    this.dramas = data.data;
                } else {
                    alert('Pencarian gagal: ' + (data.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error searching:', error);
                alert('Terjadi kesalahan saat mencari. Silakan coba lagi.');
            } finally {
                setTimeout(() => {
                    this.loading = false;
                }, 300);
            }
        },
        
        async loadPlatformData(platform) {
            this.loading = true;
            this.dramas = [];
            
            try {
                const response = await fetch(`api/${platform}.php`);
                const data = await response.json();
                
                if (data.success) {
                    this.dramas = data.data;
                }
            } catch (error) {
                console.error('Error loading data:', error);
            } finally {
                // Simulate loading delay for smooth UX
                setTimeout(() => {
                    this.loading = false;
                }, 500);
            }
        },
        
        getPlatformColor(platform) {
            const colors = {
                'dramabox': 'from-pink-500 to-red-500',
                'netshort': 'from-orange-500 to-pink-500',
                'melolo': 'from-yellow-500 to-orange-500',
                'flickreels': 'from-yellow-400 to-yellow-600'
            };
            return colors[platform] || 'from-gray-500 to-gray-600';
        },
        
        getPlatformIcon(platform) {
            const icons = {
                'dramabox': '🎬',
                'netshort': '🎥',
                'melolo': '🎭',
                'flickreels': '🎞️'
            };
            return icons[platform] || '📺';
        },
        
        getPlatformName(platform) {
            const names = {
                'dramabox': 'DramaBox',
                'netshort': 'NetShort',
                'melolo': 'MELOLO',
                'flickreels': 'FlickReels'
            };
            return names[platform] || 'Unknown';
        }
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
