# API Migration - DramaBox

## Overview
Migrasi dari API lama (sansekai.my.id) ke API baru (dramabos.asia) untuk platform DramaBox.

## Files Created/Modified

### New Files:
1. **api/dramabox2.php** - List drama dari API baru
2. **api/episodes2.php** - Episodes/player dari API baru

### Modified Files:
1. **config.php** - Added `USE_NEW_DRAMABOX_API` constant
2. **index.php** - Auto switch to dramabox2.php for home list
3. **watch.php** - Auto switch to episodes2.php for video player
4. **api/search.php** - Support new API (still uses old API for search)

## API Endpoints

### New API (dramabos.asia):
- **List**: `https://dramabos.asia/api/dramabox/api/foryou/1?lang=in`
- **Player**: `https://dramabos.asia/api/dramabox/api/watch/player?bookId={bookId}&index={episode}&lang=in`

### Old API (sansekai.my.id):
- Still available as fallback
- Used for search functionality

## Configuration

Edit `config.php`:
```php
define('USE_NEW_DRAMABOX_API', true); // true = new API, false = old API
```

## Features

✅ Automatic API switching based on config
✅ Frontend remains exactly the same
✅ Cache support for better performance
✅ Quality selection (1080p, 720p, 540p)
✅ Backward compatibility with old API

## Testing

1. Open home page → Should load from new API
2. Click any drama → Should play from new API
3. Search still uses old API (no search endpoint in new API yet)

## Notes

- New API doesn't have dedicated search endpoint yet
- Episodes are fetched on-demand (max 50 episodes)
- Video URLs from new API are direct and high quality
- Frontend code is unchanged - only backend API files modified
