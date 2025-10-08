# Ratingr

[![WordPress Plugin Version](https://img.shields.io/badge/version-1.0.0-blue.svg)](https://github.com/butialabs/ratingr/)
[![WordPress](https://img.shields.io/badge/wordpress-6.7+-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/php-7.4+-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-GPL%20v2-red.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Multisite](https://img.shields.io/badge/multisite-compatible-green.svg)](https://wordpress.org/support/article/create-a-network/)

A simple and elegant 5-star rating system for WordPress.

## 📋 What is this?

Ratingr allows your users to rate posts with a 5-star system. Everything is stored in your own WordPress database - no external services, no fees, no complications.

## ✨ Features

- **⭐ 5-star system**: Intuitive and visual ratings
- **👥 Rating control**: Prevents multiple ratings from the same user (by IP, cookie, or login)
- **📊 Statistics**: View average rating and total votes
- **🎨 Customizable**: Option to disable default CSS and use your own styles
- **⚡ Performance**: Optimized system with statistics caching
- **🔒 Security**: Protection against spam and multiple ratings
- **📱 Responsive**: Works perfectly on desktop and mobile
- **🌐 Multisite**: Compatible with WordPress networks
- **🔧 Easy integration**: Use via template function or shortcode

## 📦 How to Install

### From WordPress Dashboard

1. Log into your WordPress dashboard
2. Go to **Plugins > Add New**
3. Click **Upload Plugin**
4. Select the plugin ZIP file
5. Click **Install Now** then **Activate**

### Manual Installation

1. Download the plugin
2. Upload the `ratingr` folder to `/wp-content/plugins/`
3. Activate the plugin from your WordPress dashboard

## 🚀 How to Use

### Display Ratings on Posts

**Method 1: Template Function**

Add to your theme (usually in `single.php` or `content.php`):

```php
<?php
if (function_exists('ratingr')) {
    ratingr();
}
?>
```

**Method 2: Via Code**

```php
<?php
if (class_exists('ratingr_Rating')) {
    echo ratingr_Rating::get_instance()->display_rating(get_the_ID());
}
?>
```

### Settings

1. Go to **Ratingr > Settings**
2. Configure available options:
   - **Disable Default CSS**: Use your own custom styles

### View Statistics

1. Go to **Ratingr**
2. See all posts with ratings
3. Sort by average rating or total votes
4. Click any post to view or edit

## ⚙️ Requirements

- WordPress 6.7 or newer
- PHP 7.4 or newer
- MySQL 5.6 or MariaDB 10.1 (or newer)

## 🎨 Customization

### Custom CSS

If you want to use your own styles:

1. Go to **Ratingr > Settings**
2. Check "Disable Default CSS"
3. Add your own CSS to your theme

Available CSS classes:
- `.ratingr-rating` - Main container
- `.ratingr-stars-container` - Stars container
- `.ratingr-stars-foreground` - Filled stars
- `.ratingr-stars-background` - Empty stars
- `.ratingr-rating-info` - Rating information
- `.ratingr-already-rated` - When user has already rated

### Hooks and Filters

**Available filters:**

```php
// Modify JavaScript parameters
add_filter('ratingr_params', function($params) {
    // Modify $params as needed
    return $params;
});

// Control if user can rate
add_filter('ratingr_user_can_rate', function($can_rate, $post_id) {
    // Your custom logic
    return $can_rate;
}, 10, 2);

// Modify rating HTML
add_filter('ratingr_rating_html', function($html, $post_id, $stats) {
    // Modify the HTML
    return $html;
}, 10, 3);
```

## 🗄️ Database Structure

The plugin creates two tables:

**`wp_ratingr_ratings`** - Stores individual ratings
- `id` - Unique rating ID
- `post_id` - Post ID
- `rating` - Rating value (0-5)
- `user_id` - User ID (if logged in)
- `ip_address` - User IP
- `cookie_id` - Unique visitor cookie
- `created_at` - Creation date
- `updated_at` - Update date

**`wp_ratingr_stats`** - Statistics cache
- `post_id` - Post ID
- `average_rating` - Average rating
- `total_votes` - Total votes
- `last_updated` - Last update

## 🔧 Available Functions

```php
// Display rating component
ratingr();

// Get rating data
$data = ratingr_Rating::get_instance()->get_rating_data($post_id);
// Returns: array('average_rating' => float, 'total_votes' => int)

// Get average rating
$average = ratingr_Rating::get_instance()->get_average_rating($post_id);

// Get total votes
$votes = ratingr_Rating::get_instance()->get_total_votes($post_id);
```

## 🌟 Like it? Help Out!

If you find this plugin useful, consider:

- ⭐ Starring the repository
- 🐛 Reporting bugs you find
- 💡 Suggesting improvements
- 🤝 Contributing code
- 📢 Sharing with others

---

**Made with ❤️ by Butiá Labs and Manual do Usuário**