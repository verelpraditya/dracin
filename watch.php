<?php
require_once 'config.php';

$bookId = $_GET['id'] ?? '';
$episodeNum = $_GET['ep'] ?? 1;
$platform = $_GET['platform'] ?? 'dramabox';

if (!isset($platforms[$platform])) {
    $platform = 'dramabox';
}

if (empty($bookId)) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Watch Drama';
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle . ' - ' . SITE_NAME; ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- CryptoJS for MD5 -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    
    <!-- Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body {
            background: #000;
        }
        
        .video-container {
            position: relative;
            width: 100%;
            height: 100vh;
            background: #000;
        }
        
        video {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .episode-selector {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.95) 0%, rgba(0,0,0,0.9) 100%);
            max-height: 70vh;
            overflow-y: auto;
            transform: translateY(100%);
            transition: transform 0.3s ease;
            z-index: 100;
        }
        
        .episode-selector.open {
            transform: translateY(0);
        }
        
        .episode-btn {
            min-width: 60px;
            min-height: 50px;
            border: 1px solid rgba(34, 211, 238, 0.3);
            background: rgba(17, 24, 39, 0.8);
            transition: all 0.2s;
        }
        
        .episode-btn:hover {
            background: rgba(34, 211, 238, 0.2);
            border-color: rgba(34, 211, 238, 0.6);
        }
        
        .episode-btn.active {
            background: rgba(34, 211, 238, 0.3);
            border-color: #22d3ee;
            color: #22d3ee;
            font-weight: 600;
        }
        
        .tab-selector {
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .tab-btn {
            padding: 12px 24px;
            color: #9ca3af;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
        }
        
        .tab-btn.active {
            color: #22d3ee;
            border-bottom-color: #22d3ee;
        }
        
        .controls-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            padding: 1rem;
            background: linear-gradient(to bottom, rgba(0,0,0,0.8) 0%, transparent 100%);
            z-index: 50;
        }
    </style>
</head>
<body x-data="videoPlayer()" x-init="init()">

    <!-- Video Player -->
    <div class="video-container">
        <!-- Top Controls -->
        <div 
            x-show="!isPlaying" 
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 -translate-y-10"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-10"
            class="controls-overlay"
        >
            <div class="flex items-center justify-between">
                <button @click="goBack()" class="p-2 rounded-full bg-black/50 hover:bg-black/70 transition">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>
                
                <div class="text-white text-sm text-center flex-1 mx-4">
                    <div class="font-semibold line-clamp-1" x-text="dramaTitle"></div>
                    <div class="text-gray-400 text-xs" x-text="'EP. ' + currentEpisode"></div>
                </div>
                
                <button class="p-2 rounded-full bg-black/50 hover:bg-black/70 transition">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Video Element -->
        <div class="relative w-full h-full">
            <video 
                x-ref="videoPlayer"
                controls
                controlsList="nodownload"
                playsinline
                @loadedmetadata="onVideoLoaded()"
                @play="isPlaying = true"
                @pause="isPlaying = false"
                @ended="onVideoEnded()"
                class="w-full h-full"
            >
                Browser Anda tidak mendukung video player.
            </video>
            
            <!-- Play/Pause Overlay Tap Area -->
            <div 
                @click="togglePlayPause()"
                class="absolute inset-0 flex items-center justify-center pointer-events-auto"
                style="top: 0; bottom: 80px;"
                x-data="{showIcon: false}"
                @click.stop="showIcon = true; setTimeout(() => showIcon = false, 500)"
            >
                <!-- Play/Pause Icon Feedback -->
                <div 
                    x-show="showIcon" 
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-75"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-300"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-75"
                    class="bg-black/60 rounded-full p-6 pointer-events-none"
                >
                    <svg x-show="isPlaying" class="w-16 h-16 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z"/>
                    </svg>
                    <svg x-show="!isPlaying" class="w-16 h-16 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z"/>
                    </svg>
                </div>
            </div>
        </div>
        
        <!-- Bottom Action Buttons -->
        <div 
            x-show="!isPlaying" 
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-x-10"
            x-transition:enter-end="opacity-100 translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-x-0"
            x-transition:leave-end="opacity-0 translate-x-10"
            class="fixed bottom-20 right-4 flex flex-col gap-3 z-50"
        >
            <!-- Episode Button -->
            <button 
                @click="toggleEpisodeSelector()"
                class="p-4 rounded-full bg-cyan-500 hover:bg-cyan-600 text-white shadow-lg transition"
            >
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M2 6a2 2 0 012-2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM14.553 7.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z"/>
                </svg>
            </button>
            
            <!-- Share Button -->
            <button 
                @click="shareVideo()"
                class="p-4 rounded-full bg-gray-800 hover:bg-gray-700 text-white shadow-lg transition"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                </svg>
            </button>
            
            <!-- Fullscreen Button -->
            <button 
                @click="toggleFullscreen()"
                class="p-4 rounded-full bg-gray-800 hover:bg-gray-700 text-white shadow-lg transition"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                </svg>
            </button>
        </div>
    </div>

    <!-- Backdrop Overlay -->
    <div 
        x-show="showEpisodeSelector"
        @click="showEpisodeSelector = false"
        class="fixed inset-0 bg-black/50 z-40 transition-opacity"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    ></div>

    <!-- Episode Selector Panel -->
    <div 
        class="episode-selector" 
        :class="{'open': showEpisodeSelector}"
        x-data="{
            touchStartY: 0,
            touchEndY: 0
        }"
        @touchstart="touchStartY = $event.touches[0].clientY"
        @touchmove="touchEndY = $event.touches[0].clientY"
        @touchend="
            if (touchEndY - touchStartY > 100) {
                showEpisodeSelector = false;
            }
        "
    >
        <!-- Handle Bar -->
        <div class="flex justify-center py-2">
            <div class="w-12 h-1 bg-gray-600 rounded-full"></div>
        </div>
        
        <!-- Drama Title -->
        <div class="px-4 pb-3">
            <h2 class="text-white font-bold text-lg line-clamp-2" x-text="dramaTitle"></h2>
        </div>
        
        <!-- Tabs for Episode Ranges -->
        <div class="tab-selector flex overflow-x-auto">
            <template x-for="(range, index) in episodeRanges" :key="index">
                <button 
                    @click="activeRange = index"
                    class="tab-btn whitespace-nowrap"
                    :class="{'active': activeRange === index}"
                    x-text="range.label"
                ></button>
            </template>
        </div>
        
        <!-- Episodes Grid -->
        <div class="p-4">
            <div class="grid grid-cols-6 gap-2">
                <template x-for="episode in filteredEpisodes" :key="episode.episode_number">
                    <button 
                        @click="changeEpisode(episode.episode_number, episode.video_url)"
                        class="episode-btn rounded-lg flex items-center justify-center text-white"
                        :class="{'active': currentEpisode === episode.episode_number}"
                        x-text="episode.episode_number"
                    ></button>
                </template>
            </div>
        </div>
    </div>
    
    <!-- Loading Overlay -->
    <div x-show="loading" class="fixed inset-0 bg-black/80 flex items-center justify-center z-[200]">
        <div class="text-center">
            <div class="spinner mx-auto mb-4"></div>
            <p class="text-white">Loading...</p>
        </div>
    </div>

    <script>
    function videoPlayer() {
        return {
            bookId: '<?php echo htmlspecialchars($bookId); ?>',
            platform: '<?php echo htmlspecialchars($platform); ?>',
            currentEpisode: <?php echo intval($episodeNum); ?>,
            currentVideoUrl: '',
            dramaTitle: '',
            episodes: [],
            showEpisodeSelector: false,
            loading: true,
            activeRange: 0,
            episodeRanges: [],
            isPlaying: false,
            videoInitialized: false,
            
            generateToken(suffix) {
                const date = new Date().toISOString().slice(0, 10).replace(/-/g, '');
                return CryptoJS.MD5(date + suffix).toString();
            },
            
            async init() {
                await this.loadEpisodes();
            },
            
            async loadEpisodes() {
                this.loading = true;
                
                try {
                    // Check localStorage cache first (cache for 1 hour)
                    const cacheKey = `episodes_v2_${this.platform}_${this.bookId}`;
                    const cached = localStorage.getItem(cacheKey);
                    const cacheTime = localStorage.getItem(`${cacheKey}_time`);
                    const now = Date.now();
                    
                    let data;
                    
                    // Use cache if less than 1 hour old
                    if (cached && cacheTime && (now - parseInt(cacheTime)) < 3600000) {
                        data = JSON.parse(cached);
                        console.log('Using cached episodes');
                    } else {
                        // Fetch episodes - use obfuscated API for dramabox
                        if (this.platform === 'dramabox') {
                            const token = this.generateToken('dre2');
                            const params = `i=${this.bookId}&p=${this.platform}`;
                            const encoded = btoa(params);
                            
                            const response = await fetch(`api/e.php?t=${token}&q=${encoded}`);
                            const result = await response.json();
                            
                            if (result.r) {
                                const decoded = JSON.parse(atob(result.r));
                                data = {
                                    success: decoded.s,
                                    data: {
                                        drama: {
                                            title: decoded.d.info.t,
                                            cover: decoded.d.info.c,
                                            description: decoded.d.info.desc
                                        },
                                        episodes: decoded.d.eps.map(ep => ({
                                            episode_number: ep.n,
                                            title: ep.t,
                                            video_url: ep.u
                                        })),
                                        total_episodes: decoded.d.total
                                    }
                                };
                            }
                        } else {
                            const response = await fetch(`api/episodes.php?bookId=${this.bookId}&platform=${this.platform}`);
                            data = await response.json();
                        }
                        
                        // Cache the response
                        if (data && data.success) {
                            localStorage.setItem(cacheKey, JSON.stringify(data));
                            localStorage.setItem(`${cacheKey}_time`, now.toString());
                        }
                    }
                    
                    if (data.success) {
                        this.dramaTitle = data.data.drama.title;
                        this.episodes = data.data.episodes;
                        
                        // Create episode ranges
                        this.createEpisodeRanges();
                        
                        // Load first episode or specified episode
                        const episode = this.episodes.find(ep => ep.episode_number === this.currentEpisode) || this.episodes[0];
                        if (episode) {
                            this.currentEpisode = episode.episode_number;
                            
                            // For new API, fetch video URL on-demand
                            if (this.platform === 'dramabox' && !episode.video_url) {
                                await this.fetchVideoUrl(episode.episode_number);
                            } else {
                                this.currentVideoUrl = episode.video_url;
                                this.loadVideoSource(episode.video_url, true);
                            }
                        }
                    } else {
                        alert('Gagal memuat episode: ' + (data.error || 'Unknown error'));
                    }
                } catch (error) {
                    console.error('Error loading episodes:', error);
                    alert('Gagal memuat episode. Silakan coba lagi.');
                } finally {
                    this.loading = false;
                }
            },
            
            createEpisodeRanges() {
                const totalEpisodes = this.episodes.length;
                const rangeSize = 30;
                this.episodeRanges = [];
                
                for (let i = 0; i < totalEpisodes; i += rangeSize) {
                    const start = i + 1;
                    const end = Math.min(i + rangeSize, totalEpisodes);
                    this.episodeRanges.push({
                        label: `${start} - ${end}`,
                        start: start,
                        end: end
                    });
                }
            },
            
            get filteredEpisodes() {
                if (this.episodeRanges.length === 0) return this.episodes;
                
                const range = this.episodeRanges[this.activeRange];
                return this.episodes.filter(ep => 
                    ep.episode_number >= range.start && ep.episode_number <= range.end
                );
            },
            
            changeEpisode(episodeNum, videoUrl) {
                this.currentEpisode = episodeNum;
                this.showEpisodeSelector = false;
                
                // Update URL without reload
                const url = new URL(window.location);
                url.searchParams.set('ep', episodeNum);
                window.history.pushState({}, '', url);
                
                // For new API, fetch video URL first if empty
                if (this.platform === 'dramabox' && !videoUrl) {
                    this.fetchVideoUrl(episodeNum);
                } else {
                    this.currentVideoUrl = videoUrl;
                    this.loadVideoSource(videoUrl, true);
                }
            },
            
            async fetchVideoUrl(episodeNum) {
                this.loading = true;
                try {
                    const token = this.generateToken('drv2');
                    const params = `i=${this.bookId}&e=${episodeNum}`;
                    const encoded = btoa(params);
                    
                    const response = await fetch(`api/v.php?t=${token}&q=${encoded}`);
                    const result = await response.json();
                    
                    if (result.r) {
                        const decoded = JSON.parse(atob(result.r));
                        if (decoded.s && decoded.d.u) {
                            this.currentVideoUrl = decoded.d.u;
                            
                            // Update episode in array
                            const episode = this.episodes.find(ep => ep.episode_number === episodeNum);
                            if (episode) {
                                episode.video_url = decoded.d.u;
                            }
                            
                            // Load video
                            this.loadVideoSource(decoded.d.u, true);
                        } else {
                            alert('Gagal memuat video');
                        }
                    } else {
                        alert('Gagal memuat video');
                    }
                } catch (error) {
                    console.error('Error fetching video:', error);
                    alert('Terjadi kesalahan saat memuat video. Silakan coba lagi.');
                } finally {
                    this.loading = false;
                }
            },
            
            loadVideoSource(videoUrl, autoplay = false) {
                if (!videoUrl) return;
                
                const video = this.$refs.videoPlayer;
                if (!video) return;
                
                // Remove all existing source elements
                while (video.firstChild) {
                    video.removeChild(video.firstChild);
                }
                
                // Create new source element
                const source = document.createElement('source');
                source.src = videoUrl;
                source.type = 'video/mp4';
                video.appendChild(source);
                
                // Load video
                video.load();
                
                // Auto play if requested
                if (autoplay) {
                    video.addEventListener('loadeddata', () => {
                        video.play().catch(err => {
                            console.log('Autoplay prevented:', err);
                        });
                    }, { once: true });
                }
            },
            
            toggleEpisodeSelector() {
                this.showEpisodeSelector = !this.showEpisodeSelector;
            },
            
            toggleFullscreen() {
                const video = this.$refs.videoPlayer;
                if (!document.fullscreenElement) {
                    if (video.requestFullscreen) {
                        video.requestFullscreen();
                    } else if (video.webkitRequestFullscreen) {
                        video.webkitRequestFullscreen();
                    }
                } else {
                    if (document.exitFullscreen) {
                        document.exitFullscreen();
                    }
                }
            },
            
            shareVideo() {
                const url = window.location.href;
                // Copy to clipboard instead of using navigator.share to avoid permission popup
                navigator.clipboard.writeText(url).then(() => {
                    alert('Link berhasil disalin!');
                }).catch(() => {
                    // Fallback if clipboard access denied
                    prompt('Salin link ini:', url);
                });
            },
            
            goBack() {
                window.location.href = 'index.php';
            },
            
            onVideoLoaded() {
                console.log('Video metadata loaded');
            },
            
            onVideoEnded() {
                // Auto play next episode
                const nextEp = this.episodes.find(ep => ep.episode_number === this.currentEpisode + 1);
                if (nextEp) {
                    this.changeEpisode(nextEp.episode_number, nextEp.video_url);
                }
            },
            
            togglePlayPause() {
                const video = this.$refs.videoPlayer;
                if (video.paused) {
                    video.play();
                } else {
                    video.pause();
                }
            }
        }
    }
    </script>
</body>
</html>
