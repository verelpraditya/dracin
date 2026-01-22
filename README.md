# Dracin - Drama Streaming Platform

Platform streaming drama, series, dan film pendek dari berbagai platform populer seperti DramaBox, NetShort, MELOLO, dan FlickReels.

## Tech Stack

- **PHP** - Backend language
- **Alpine.js** - Lightweight JavaScript framework (~15KB)
- **Tailwind CSS** - Utility-first CSS framework
- **MySQL** - Database (untuk future features)

## Features

✅ Multi-platform content (DramaBox, NetShort, MELOLO, FlickReels)
✅ Tab switching without page reload
✅ Skeleton loading for smooth UX
✅ Responsive design
✅ Fast and lightweight
✅ Easy to deploy on low-spec VPS

## Installation

### Requirements

- PHP 7.4 or higher
- Web server (Apache/Nginx)
- Laragon/XAMPP/WAMP (for local development)

### Setup

1. Clone or download this project to your web directory
   ```
   d:\laragon-6.0.0\www\dracin
   ```

2. Access the website via browser
   ```
   http://localhost/dracin
   ```

3. Configure your API endpoints in `config.php`
   - Add your actual API URLs
   - Add your API keys

## Project Structure

```
dracin/
├── api/
│   ├── dramabox.php      # DramaBox API endpoint
│   ├── netshort.php      # NetShort API endpoint
│   ├── melolo.php        # MELOLO API endpoint
│   └── flickreels.php    # FlickReels API endpoint
├── assets/
│   ├── css/
│   │   └── style.css     # Custom styles
│   └── js/
│       └── main.js       # Main JavaScript
├── includes/
│   ├── header.php        # Header component
│   ├── footer.php        # Footer component
│   └── skeleton.php      # Skeleton loading component
├── config.php            # Configuration file
├── index.php             # Homepage
├── 404.php               # 404 error page
└── .htaccess             # Apache configuration
```

## How to Integrate Real APIs

### Example: DramaBox API

Open `api/dramabox.php` and replace the mock data section with:

```php
// Replace this mock data section
$data = makeApiRequest(DRAMABOX_API_URL, [
    'Authorization: Bearer ' . DRAMABOX_API_KEY
]);

// Transform the API response to match our format
$mockData = array_map(function($item) {
    return [
        'id' => $item['id'],
        'title' => $item['title'],
        'poster' => $item['image_url'],
        'rating' => $item['rating'],
        'tags' => $item['genres'],
        'episodes' => $item['episode_count'],
        'year' => $item['release_year'],
        'description' => $item['synopsis']
    ];
}, $data['results']);
```

Repeat this process for all 4 platform API files.

## Configuration

Edit `config.php` to set your API credentials:

```php
// API URLs
define('DRAMABOX_API_URL', 'https://your-api.com/endpoint');
define('NETSHORT_API_URL', 'https://your-api.com/endpoint');
define('MELOLO_API_URL', 'https://your-api.com/endpoint');
define('FLICKREELS_API_URL', 'https://your-api.com/endpoint');

// API Keys
define('DRAMABOX_API_KEY', 'your_actual_api_key');
define('NETSHORT_API_KEY', 'your_actual_api_key');
define('MELOLO_API_KEY', 'your_actual_api_key');
define('FLICKREELS_API_KEY', 'your_actual_api_key');
```

## Future Features

- 🔐 User authentication (Google OAuth)
- ⭐ Favorites/Watchlist
- 🔍 Advanced search
- 📱 PWA support
- 🎬 Video player integration
- 💬 Comments and ratings

## Performance

- Lightweight (~100KB total assets)
- Works on VPS with 256MB RAM
- Fast page load (<1s)
- No heavy JavaScript frameworks

## Deployment

### VPS Deployment

1. Upload files to your VPS
2. Configure web server (Apache/Nginx)
3. Set proper file permissions
4. Update `SITE_URL` in config.php
5. Done!

### Shared Hosting

1. Upload via FTP/cPanel
2. Extract files to public_html or subdirectory
3. Update config.php
4. Access via your domain

## Support

For questions or issues, create an issue in the repository.

## License

Free to use and modify.
