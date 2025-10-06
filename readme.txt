=== Ratingr ===
Contributors: butialabs
Donate link: https://butialabs.com
Tags: rating, stars, reviews, votes, post rating
Requires at least: 6.7
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A simple and elegant 5-star rating system for WordPress posts.

== Description ==

Ratingr is a lightweight rating plugin that allows your users to rate posts with a 5-star system. Everything runs directly from your WordPress database - no external services needed.

**Key Features:**

* **5-star rating system**: Intuitive and visual ratings for your posts
* **Prevent duplicate ratings**: Users can only rate once per post (tracked by IP, cookie, or user account)
* **View statistics**: See average ratings and total vote counts for all rated posts
* **Customizable styling**: Option to disable default CSS and use your own custom styles
* **Performance optimized**: Statistics are cached for fast loading
* **Spam protection**: Built-in protection against rating manipulation
* **Fully responsive**: Works perfectly on desktop, tablet, and mobile devices
* **Multisite compatible**: Works seamlessly with WordPress multisite networks
* **Easy integration**: Simple template function or class method to display ratings

**Perfect For:**

* Blog posts with reader ratings
* Product reviews
* Article quality feedback
* User-generated content rating
* Review sites
* Community engagement

**Privacy & Security:**

Your data stays in your own WordPress database. Nothing gets sent to external services. The plugin tracks ratings by IP address, browser cookie, or user login to prevent duplicate votes while respecting privacy.

**Developer Friendly:**

Includes hooks and filters for customization, clean code structure, and comprehensive documentation for developers who want to extend functionality.

== Installation ==

**Easy Installation:**

1. Log into your WordPress dashboard
2. Go to **Plugins > Add New**
3. Search for "Ratingr"
4. Click **Install Now** then **Activate**

**Manual Installation:**

1. Download the plugin ZIP file
2. Log into your WordPress dashboard
3. Go to **Plugins > Add New > Upload Plugin**
4. Select the ZIP file and click **Install Now**
5. After installation completes, click **Activate Plugin**

**After Activation:**

1. Add the rating display to your theme template (see Usage section)
2. Configure settings at **Ratingr > Settings** (optional)
3. View rating statistics at **Ratingr**

== Usage ==

**Display Ratings in Your Theme:**

Add this code to your theme's `single.php`, `content.php`, or template file where you want ratings to appear:

`<?php
if (function_exists('ratingr')) {
    ratingr();
}
?>`

**Alternative Method:**

`<?php
if (class_exists('ratingr_Rating')) {
    echo ratingr_Rating::get_instance()->display_rating(get_the_ID());
}
?>`

**Configure Settings:**

1. Go to **Ratingr > Settings**
2. Enable/disable default CSS styling
3. Save your preferences

**View Statistics:**

1. Go to **Ratingr**
2. Browse all posts with ratings
3. Sort by average rating or total votes
4. Click any post to view or edit

== Frequently Asked Questions ==

= Does this work with any WordPress theme? =

Yes! Ratingr works with any properly coded WordPress theme. You just need to add the template function where you want the rating stars to appear.

= Can users rate posts multiple times? =

No. The plugin prevents duplicate ratings by tracking IP address, browser cookie, and user login (if logged in). Users can only rate once per post.

= Does this plugin send data to external services? =

No. All data is stored in your WordPress database. No external services are used.

= Is this compatible with multisite? =

Yes! Ratingr works perfectly with WordPress multisite installations.

= Can I customize the appearance of the rating stars? =

Yes! You can disable the default CSS in settings and add your own custom styles. All elements use specific CSS classes for easy styling.

= Will ratings affect my site's performance? =

No. The plugin is optimized with statistics caching to ensure fast loading times.

= Can I export the rating data? =

The ratings are stored in your WordPress database and can be accessed via standard database tools or custom queries.

= What happens if I deactivate the plugin? =

Your rating data remains in the database but ratings will not be displayed. If you reactivate the plugin, everything will work again.

= What happens if I uninstall the plugin? =

If you delete the plugin through WordPress, all rating data and database tables will be permanently removed.

= Can I use this for custom post types? =

Yes! The plugin works with any post type as long as you add the display function to the appropriate template.

= Does this support half-star ratings? =

Yes! The system supports ratings in 0.5 increments (0, 0.5, 1, 1.5, 2, etc.).

= Is there an option to moderate ratings before they appear? =

Currently, ratings appear immediately. Moderation features may be added in future versions.

== Screenshots ==

1. Rating display on the frontend showing 5-star system
2. Admin statistics page showing all rated posts
3. Settings page to configure plugin options
4. Rating component with vote count and average

== Upgrade Notice ==

= 1.0.0 =
Initial release of Ratingr - a simple 5-star rating system for WordPress.

== Developer Documentation ==

**Available Functions:**

`// Display rating component
ratingr();

// Get rating data
$data = ratingr_Rating::get_instance()->get_rating_data($post_id);

// Get average rating
$average = ratingr_Rating::get_instance()->get_average_rating($post_id);

// Get total votes
$votes = ratingr_Rating::get_instance()->get_total_votes($post_id);`

**Available Filters:**

`// Modify JavaScript parameters
add_filter('ratingr_params', 'my_custom_params');

// Control if user can rate
add_filter('ratingr_user_can_rate', 'my_can_rate_logic', 10, 2);

// Modify rating HTML output
add_filter('ratingr_rating_html', 'my_custom_html', 10, 3);`

**CSS Classes:**

* `.ratingr-rating` - Main container
* `.ratingr-stars-container` - Stars container
* `.ratingr-stars-foreground` - Filled stars
* `.ratingr-stars-background` - Empty stars
* `.ratingr-rating-info` - Rating information text
* `.ratingr-already-rated` - Applied when user has already rated

**Database Tables:**

* `wp_ratingr_ratings` - Individual rating records
* `wp_ratingr_stats` - Cached statistics per post

== Credits ==

Made by Butiá Labs and Manual do Usuário