# Adult API Crawler For Eroz Theme

WordPress plugin to crawl videos from adult API providers (AVDBAPI, XVIDAPI, etc.) with special compatibility for **Eroz Theme**.

## Features

- Crawl videos from multiple API providers
- Automatically create posts with meta fields suitable for Eroz Theme
- Automatic cronjob support
- Download and save images
- Manage categories, actors, tags
- User-friendly admin interface

## Meta Fields Mapping for Eroz Theme

This plugin has been customized to map data fields from API to Eroz Theme meta fields:

### Main Mapping:
- `poster_url` → `eroz_meta_src` (Poster image)
- `embed` → `embed` (Video embed - keep as is)
- `original_name` → `video_optional_1` + `original_name_1`
- `movie_code` → `movie_code_1`, `movie_code_2`, `movie_code_3`
- `description` → `eroz_post_desc`
- `duration` → `duration`
- `trailer_url` → `trailer_url`

### Additional Mapping:
- `year` → `year`
- `director` → `director`
- `quality` → `quality`
- `release_date` → `release_date`
- `country` → `country`
- `language` → `language`
- `studio` → `studio`
- `series` → `series`
- `episode_number` → `episode_number`
- `total_episodes` → `total_episodes`
- `rating` → `rating`
- `views` → `views`
- `likes` → `likes`
- `dislikes` → `dislikes`
- `download_links` → `eroz_ads_link`
- `subtitle_links` → `subtitle_links`
- `video_sources` → `video_optional_2`, `video_optional_3`, `video_optional_4`
- `movie_codes` → `movie_code_1`, `movie_code_2`, `movie_code_3`

## Installation

1. Upload the plugin to `/wp-content/plugins/` directory
2. Activate the plugin in WordPress Admin
3. Access the "Adult API Crawler For Eroz Theme" menu to configure

## Configuration

### API Settings
- **API URL**: URL of the API provider (default: https://avdbapi.com/api.php/provide/vod/?ac=detail)
- **Crawling Method**: 
  - Recent: Crawl the latest 5 pages
  - Selected: Choose specific page ranges
  - All: Crawl all pages

### Cronjob Settings
- **Enable Cronjob**: Enable/disable automatic cronjob
- **Schedule**: Running frequency (30 minutes, 1 hour, 2 hours, 6 hours, twice daily, once daily)
- **Download Images**: Download and save images
- **Force Update**: Update existing posts

## Usage

### Manual Crawling
1. Go to plugin menu
2. Enter API URL
3. Select pages to crawl
4. Click "Get Movies" to view the list
5. Select videos and click "Crawl" to import

### Automatic Crawling
1. Configure cronjob settings
2. Enable "Enable Cronjob"
3. Choose appropriate schedule
4. Plugin will automatically crawl according to schedule

### Import Multiple Videos
1. Use "Multi-Link Import" feature
2. Enter list of video IDs (separated by commas)
3. Click "Import" to crawl all

## Notes

- Plugin is optimized for Eroz Theme
- All meta fields are accurately mapped according to Eroz structure
- Full support for Eroz Theme features
- Compatible with WordPress 5.0+

## Support

If you encounter issues, please check:
1. PHP version (requires 7.4+)
2. WordPress permissions
3. API provider status
4. Eroz Theme is activated

## Changelog

### Version 2.0.0
- Full compatibility with Eroz Theme
- Accurate meta fields mapping
- Improved crawling performance
- Added many additional meta fields 