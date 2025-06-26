<?php
/**
 * Bridge MLS Documentation Content
 * 
 * This file contains the documentation content for the admin interface
 * 
 * @package Bridge_MLS
 * @since 3.0.2
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<section id="getting-started">
    <h2><?php _e('Getting Started', 'bridge-mls'); ?></h2>
    <p><?php _e('Welcome to the Bridge MLS WordPress plugin! This plugin provides a complete MLS integration solution for your WordPress website.', 'bridge-mls'); ?></p>
    
    <h3><?php _e('Initial Setup', 'bridge-mls'); ?></h3>
    <ol>
        <li>
            <strong><?php _e('Configure API Credentials:', 'bridge-mls'); ?></strong> 
            <?php _e('Go to Settings → Bridge MLS and enter your API credentials.', 'bridge-mls'); ?>
        </li>
        <li>
            <strong><?php _e('Test Connection:', 'bridge-mls'); ?></strong> 
            <?php _e('Click the "Test API Connection" button to verify your credentials are working.', 'bridge-mls'); ?>
        </li>
        <li>
            <strong><?php _e('Add Agent Information:', 'bridge-mls'); ?></strong> 
            <?php _e('Fill in your contact details to display on property pages.', 'bridge-mls'); ?>
        </li>
        <li>
            <strong><?php _e('Create Pages:', 'bridge-mls'); ?></strong> 
            <?php _e('Create WordPress pages for your property search and details.', 'bridge-mls'); ?>
        </li>
        <li>
            <strong><?php _e('Add Shortcodes:', 'bridge-mls'); ?></strong> 
            <?php _e('Add the appropriate shortcodes to your pages.', 'bridge-mls'); ?>
        </li>
    </ol>
    
    <h3><?php _e('Quick Start Video', 'bridge-mls'); ?></h3>
    <p><?php _e('Watch our 5-minute setup guide:', 'bridge-mls'); ?> 
        <a href="https://bridge-mls.com/setup-video" target="_blank"><?php _e('View Video Tutorial', 'bridge-mls'); ?></a>
    </p>
</section>

<section id="shortcodes">
    <h2><?php _e('Shortcodes', 'bridge-mls'); ?></h2>
    
    <h3>[bridge_property_search]</h3>
    <p><?php _e('Displays a full property search interface with filters and results.', 'bridge-mls'); ?></p>
    
    <h4><?php _e('Parameters:', 'bridge-mls'); ?></h4>
    <ul>
        <li><code>title</code> - <?php _e('The title to display above the search (default: "Property Search")', 'bridge-mls'); ?></li>
        <li><code>show_search</code> - <?php _e('Show/hide the search form (default: "true")', 'bridge-mls'); ?></li>
        <li><code>columns</code> - <?php _e('Number of columns for property grid (default: "3")', 'bridge-mls'); ?></li>
        <li><code>limit</code> - <?php _e('Number of properties to display (default: "12")', 'bridge-mls'); ?></li>
        <li><code>city</code> - <?php _e('Pre-filter by city', 'bridge-mls'); ?></li>
        <li><code>min_price</code> - <?php _e('Minimum price filter', 'bridge-mls'); ?></li>
        <li><code>max_price</code> - <?php _e('Maximum price filter', 'bridge-mls'); ?></li>
        <li><code>bedrooms</code> - <?php _e('Minimum bedrooms', 'bridge-mls'); ?></li>
        <li><code>bathrooms</code> - <?php _e('Minimum bathrooms', 'bridge-mls'); ?></li>
        <li><code>property_type</code> - <?php _e('Filter by property type', 'bridge-mls'); ?></li>
        <li><code>show_title</code> - <?php _e('Show/hide the title (default: "true")', 'bridge-mls'); ?></li>
        <li><code>show_count</code> - <?php _e('Show result count (default: "true")', 'bridge-mls'); ?></li>
    </ul>
    
    <h4><?php _e('Example:', 'bridge-mls'); ?></h4>
    <pre><code>[bridge_property_search title="Find Your Dream Home" columns="4" limit="16" city="Boston"]</code></pre>
    
    <h3>[bridge_featured_properties]</h3>
    <p><?php _e('Displays a grid of featured properties without the search form.', 'bridge-mls'); ?></p>
    
    <h4><?php _e('Parameters:', 'bridge-mls'); ?></h4>
    <ul>
        <li><code>limit</code> - <?php _e('Number of properties to display (default: "6")', 'bridge-mls'); ?></li>
        <li><code>columns</code> - <?php _e('Number of columns (default: "3")', 'bridge-mls'); ?></li>
        <li><code>title</code> - <?php _e('Section title (default: "Featured Properties")', 'bridge-mls'); ?></li>
        <li><code>city</code> - <?php _e('Filter by city', 'bridge-mls'); ?></li>
        <li><code>min_price</code> - <?php _e('Minimum price', 'bridge-mls'); ?></li>
        <li><code>max_price</code> - <?php _e('Maximum price', 'bridge-mls'); ?></li>
        <li><code>show_title</code> - <?php _e('Show/hide title (default: "true")', 'bridge-mls'); ?></li>
    </ul>
    
    <h4><?php _e('Example:', 'bridge-mls'); ?></h4>
    <pre><code>[bridge_featured_properties limit="8" columns="4" title="Latest Listings" city="Cambridge"]</code></pre>
    
    <h3>[bridge_property_details]</h3>
    <p><?php _e('Displays detailed information about a single property.', 'bridge-mls'); ?></p>
    
    <h4><?php _e('Parameters:', 'bridge-mls'); ?></h4>
    <ul>
        <li><code>mls_id</code> - <?php _e('The MLS ID of the property', 'bridge-mls'); ?></li>
        <li><code>listing_key</code> - <?php _e('The listing key of the property', 'bridge-mls'); ?></li>
    </ul>
    
    <p><strong><?php _e('Note:', 'bridge-mls'); ?></strong> <?php _e('Usually used on a dedicated property details page with URL parameters.', 'bridge-mls'); ?></p>
</section>

<section id="property-search">
    <h2><?php _e('Property Search', 'bridge-mls'); ?></h2>
    
    <h3><?php _e('Search Features', 'bridge-mls'); ?></h3>
    <ul>
        <li><strong><?php _e('Multi-City Selection:', 'bridge-mls'); ?></strong> <?php _e('Users can select multiple cities using the Select2 dropdown', 'bridge-mls'); ?></li>
        <li><strong><?php _e('Price Range:', 'bridge-mls'); ?></strong> <?php _e('Min and max price filters', 'bridge-mls'); ?></li>
        <li><strong><?php _e('Bedrooms/Bathrooms:', 'bridge-mls'); ?></strong> <?php _e('Minimum room count filters', 'bridge-mls'); ?></li>
        <li><strong><?php _e('Property Type:', 'bridge-mls'); ?></strong> <?php _e('Filter by property category', 'bridge-mls'); ?></li>
        <li><strong><?php _e('Keyword Search:', 'bridge-mls'); ?></strong> <?php _e('Search within property descriptions', 'bridge-mls'); ?></li>
        <li><strong><?php _e('Real-time Updates:', 'bridge-mls'); ?></strong> <?php _e('Results update automatically as filters change', 'bridge-mls'); ?></li>
    </ul>
    
    <h3><?php _e('URL Parameters', 'bridge-mls'); ?></h3>
    <p><?php _e('Search pages support URL parameters for direct linking:', 'bridge-mls'); ?></p>
    <ul>
        <li><code>?city=Boston,Cambridge</code> - <?php _e('Multiple cities (comma-separated)', 'bridge-mls'); ?></li>
        <li><code>?min_price=300000&max_price=500000</code> - <?php _e('Price range', 'bridge-mls'); ?></li>
        <li><code>?bedrooms=3&bathrooms=2</code> - <?php _e('Room requirements', 'bridge-mls'); ?></li>
        <li><code>?property_type=for_sale</code> - <?php _e('Property type', 'bridge-mls'); ?></li>
        <li><code>?keywords=waterfront</code> - <?php _e('Keyword search', 'bridge-mls'); ?></li>
    </ul>
    
    <h3><?php _e('Creating Search Pages', 'bridge-mls'); ?></h3>
    <ol>
        <li><?php _e('Create a new page in WordPress', 'bridge-mls'); ?></li>
        <li><?php _e('Add the shortcode:', 'bridge-mls'); ?> <code>[bridge_property_search]</code></li>
        <li><?php _e('Customize with parameters as needed', 'bridge-mls'); ?></li>
        <li><?php _e('Publish the page', 'bridge-mls'); ?></li>
    </ol>
</section>

<section id="property-details">
    <h2><?php _e('Property Details', 'bridge-mls'); ?></h2>
    
    <h3><?php _e('Page Setup', 'bridge-mls'); ?></h3>
    <ol>
        <li><?php _e('Create a new page called "Property Details"', 'bridge-mls'); ?></li>
        <li><?php _e('Add the shortcode:', 'bridge-mls'); ?> <code>[bridge_property_details]</code></li>
        <li><?php _e('Properties will be accessible via:', 'bridge-mls'); ?> <code>/property-details/?mls=12345</code></li>
    </ol>
    
    <h3><?php _e('Features', 'bridge-mls'); ?></h3>
    <ul>
        <li><strong><?php _e('Image Gallery:', 'bridge-mls'); ?></strong> <?php _e('Main image with thumbnail grid and lightbox', 'bridge-mls'); ?></li>
        <li><strong><?php _e('Mobile Gallery:', 'bridge-mls'); ?></strong> <?php _e('Touch-enabled swipe gallery on mobile devices', 'bridge-mls'); ?></li>
        <li><strong><?php _e('Property Information:', 'bridge-mls'); ?></strong> <?php _e('All available details displayed in organized sections', 'bridge-mls'); ?></li>
        <li><strong><?php _e('Agent Contact:', 'bridge-mls'); ?></strong> <?php _e('Your contact information with integrated contact form', 'bridge-mls'); ?></li>
        <li><strong><?php _e('Share Functionality:', 'bridge-mls'); ?></strong> <?php _e('Native share API or clipboard copy', 'bridge-mls'); ?></li>
        <li><strong><?php _e('SEO Optimized:', 'bridge-mls'); ?></strong> <?php _e('Schema.org markup and meta tags', 'bridge-mls'); ?></li>
    </ul>
    
    <h3><?php _e('Pretty URLs', 'bridge-mls'); ?></h3>
    <p><?php _e('Enable pretty permalinks to use URLs like:', 'bridge-mls'); ?> <code>/property/123456/</code></p>
    <p><?php _e('The plugin automatically creates rewrite rules for clean URLs.', 'bridge-mls'); ?></p>
</section>

<section id="customization">
    <h2><?php _e('Customization', 'bridge-mls'); ?></h2>
    
    <h3><?php _e('CSS Customization', 'bridge-mls'); ?></h3>
    <p><?php _e('The plugin uses the following main CSS classes that you can override:', 'bridge-mls'); ?></p>
    <ul>
        <li><code>.bridge-mls-container</code> - <?php _e('Main container', 'bridge-mls'); ?></li>
        <li><code>.bridge-property-search</code> - <?php _e('Search form container', 'bridge-mls'); ?></li>
        <li><code>.property-grid</code> - <?php _e('Property grid container', 'bridge-mls'); ?></li>
        <li><code>.property-card</code> - <?php _e('Individual property cards', 'bridge-mls'); ?></li>
        <li><code>.bridge-property-details-modern</code> - <?php _e('Property details page', 'bridge-mls'); ?></li>
    </ul>
    
    <h3><?php _e('Color Scheme', 'bridge-mls'); ?></h3>
    <p><?php _e('To change the color scheme, add custom CSS to your theme:', 'bridge-mls'); ?></p>
    <pre><code>/* Example: Change primary button color */
.button-primary {
    background-color: #your-color;
    border-color: #your-color;
}

/* Change accent color */
.property-status.status-active {
    background-color: #your-color;
}

/* Change hover effects */
.property-card:hover {
    box-shadow: 0 8px 30px rgba(your-color-rgb, 0.3);
}</code></pre>
    
    <h3><?php _e('Layout Customization', 'bridge-mls'); ?></h3>
    <p><?php _e('Adjust grid layouts for different screen sizes:', 'bridge-mls'); ?></p>
    <pre><code>/* Tablet layout */
@media (max-width: 768px) {
    .property-grid.columns-3 {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* Mobile layout */
@media (max-width: 480px) {
    .property-grid {
        grid-template-columns: 1fr;
    }
}</code></pre>
</section>

<section id="hooks-filters">
    <h2><?php _e('Hooks & Filters', 'bridge-mls'); ?></h2>
    
    <h3><?php _e('Available Filters', 'bridge-mls'); ?></h3>
    
    <h4><code>bridge_mls_search_filters</code></h4>
    <p><?php _e('Modify search filters before API request:', 'bridge-mls'); ?></p>
    <pre><code>add_filter('bridge_mls_search_filters', function($filters, $params) {
    // Add custom filter
    $filters[] = "YearBuilt ge 2000";
    return $filters;
}, 10, 2);</code></pre>
    
    <h4><code>bridge_mls_property_type_map</code></h4>
    <p><?php _e('Customize property type display names:', 'bridge-mls'); ?></p>
    <pre><code>add_filter('bridge_mls_property_type_map', function($map) {
    $map['Residential'] = 'Single Family Home';
    return $map;
});</code></pre>
    
    <h4><code>bridge_mls_cities_list</code></h4>
    <p><?php _e('Modify the list of available cities:', 'bridge-mls'); ?></p>
    <pre><code>add_filter('bridge_mls_cities_list', function($cities) {
    // Add custom city
    $cities[] = 'My Custom City';
    // Sort alphabetically
    sort($cities);
    return $cities;
});</code></pre>
    
    <h4><code>bridge_mls_enable_cache</code></h4>
    <p><?php _e('Enable/disable caching:', 'bridge-mls'); ?></p>
    <pre><code>// Disable cache for development
add_filter('bridge_mls_enable_cache', '__return_false');</code></pre>
    
    <h3><?php _e('Available Actions', 'bridge-mls'); ?></h3>
    
    <h4><code>bridge_mls_before_search</code></h4>
    <p><?php _e('Run code before property search:', 'bridge-mls'); ?></p>
    <pre><code>add_action('bridge_mls_before_search', function($params) {
    // Log searches
    error_log('Property search: ' . json_encode($params));
});</code></pre>
</section>

<section id="troubleshooting">
    <h2><?php _e('Troubleshooting', 'bridge-mls'); ?></h2>
    
    <h3><?php _e('Common Issues', 'bridge-mls'); ?></h3>
    
    <h4><?php _e('API Connection Failed', 'bridge-mls'); ?></h4>
    <ul>
        <li><?php _e('Verify your API credentials are correct', 'bridge-mls'); ?></li>
        <li><?php _e('Check if your server can make external HTTPS requests', 'bridge-mls'); ?></li>
        <li><?php _e('Look for firewall or security plugin conflicts', 'bridge-mls'); ?></li>
        <li><?php _e('Enable WP_DEBUG to see detailed error messages', 'bridge-mls'); ?></li>
        <li><?php _e('Check server logs for cURL errors', 'bridge-mls'); ?></li>
    </ul>
    
    <h4><?php _e('No Properties Displaying', 'bridge-mls'); ?></h4>
    <ul>
        <li><?php _e('Ensure API connection is working', 'bridge-mls'); ?></li>
        <li><?php _e('Check if filters are too restrictive', 'bridge-mls'); ?></li>
        <li><?php _e('Verify properties exist in the selected area', 'bridge-mls'); ?></li>
        <li><?php _e('Clear browser cache and WordPress transients', 'bridge-mls'); ?></li>
        <li><?php _e('Check PHP error logs', 'bridge-mls'); ?></li>
    </ul>
    
    <h4><?php _e('Images Not Loading', 'bridge-mls'); ?></h4>
    <ul>
        <li><?php _e('Check browser console for errors', 'bridge-mls'); ?></li>
        <li><?php _e('Verify image URLs are accessible', 'bridge-mls'); ?></li>
        <li><?php _e('Look for HTTPS/HTTP mixed content issues', 'bridge-mls'); ?></li>
        <li><?php _e('Check if a security plugin is blocking external images', 'bridge-mls'); ?></li>
        <li><?php _e('Verify CDN settings if using one', 'bridge-mls'); ?></li>
    </ul>
    
    <h4><?php _e('JavaScript Errors', 'bridge-mls'); ?></h4>
    <ul>
        <li><?php _e('Check for jQuery conflicts with other plugins', 'bridge-mls'); ?></li>
        <li><?php _e('Ensure Select2 library is loading correctly', 'bridge-mls'); ?></li>
        <li><?php _e('Test with default WordPress theme', 'bridge-mls'); ?></li>
        <li><?php _e('Disable other plugins to isolate conflicts', 'bridge-mls'); ?></li>
        <li><?php _e('Check browser console for specific error messages', 'bridge-mls'); ?></li>
    </ul>
    
    <h3><?php _e('Debug Mode', 'bridge-mls'); ?></h3>
    <p><?php _e('Enable WordPress debug mode to see detailed error messages:', 'bridge-mls'); ?></p>
    <pre><code>// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);</code></pre>
    
    <h3><?php _e('Performance Issues', 'bridge-mls'); ?></h3>
    <ul>
        <li><?php _e('Enable caching in plugin settings', 'bridge-mls'); ?></li>
        <li><?php _e('Use a caching plugin like WP Rocket or W3 Total Cache', 'bridge-mls'); ?></li>
        <li><?php _e('Optimize images with a CDN service', 'bridge-mls'); ?></li>
        <li><?php _e('Reduce the number of properties displayed per page', 'bridge-mls'); ?></li>
        <li><?php _e('Use lazy loading for images', 'bridge-mls'); ?></li>
    </ul>
</section>

<section id="api-reference">
    <h2><?php _e('API Reference', 'bridge-mls'); ?></h2>
    
    <h3><?php _e('Available Fields', 'bridge-mls'); ?></h3>
    <p><?php _e('The following fields are available from the Bridge MLS API:', 'bridge-mls'); ?></p>
    
    <h4><?php _e('Basic Fields:', 'bridge-mls'); ?></h4>
    <ul>
        <li><code>ListingKey</code> - <?php _e('Unique identifier', 'bridge-mls'); ?></li>
        <li><code>ListingId</code> - <?php _e('MLS number', 'bridge-mls'); ?></li>
        <li><code>ListPrice</code> - <?php _e('Listing price', 'bridge-mls'); ?></li>
        <li><code>BedroomsTotal</code> - <?php _e('Number of bedrooms', 'bridge-mls'); ?></li>
        <li><code>BathroomsTotalInteger</code> - <?php _e('Number of bathrooms', 'bridge-mls'); ?></li>
        <li><code>LivingArea</code> - <?php _e('Square footage', 'bridge-mls'); ?></li>
        <li><code>City</code> - <?php _e('City name', 'bridge-mls'); ?></li>
        <li><code>StateOrProvince</code> - <?php _e('State', 'bridge-mls'); ?></li>
        <li><code>PostalCode</code> - <?php _e('ZIP code', 'bridge-mls'); ?></li>
        <li><code>UnparsedAddress</code> - <?php _e('Full address', 'bridge-mls'); ?></li>
    </ul>
    
    <h4><?php _e('Extended Fields:', 'bridge-mls'); ?></h4>
    <ul>
        <li><code>PublicRemarks</code> - <?php _e('Property description', 'bridge-mls'); ?></li>
        <li><code>PropertyType</code> - <?php _e('Type of property', 'bridge-mls'); ?></li>
        <li><code>PropertySubType</code> - <?php _e('Property style', 'bridge-mls'); ?></li>
        <li><code>Media</code> - <?php _e('Array of media objects', 'bridge-mls'); ?></li>
        <li><code>PhotosCount</code> - <?php _e('Number of photos', 'bridge-mls'); ?></li>
        <li><code>YearBuilt</code> - <?php _e('Year constructed', 'bridge-mls'); ?></li>
        <li><code>LotSizeArea</code> - <?php _e('Lot size', 'bridge-mls'); ?></li>
        <li><code>StandardStatus</code> - <?php _e('Listing status', 'bridge-mls'); ?></li>
    </ul>
    
    <h3><?php _e('Filter Operators', 'bridge-mls'); ?></h3>
    <p><?php _e('OData filter operators used in API queries:', 'bridge-mls'); ?></p>
    <ul>
        <li><code>eq</code> - <?php _e('Equals', 'bridge-mls'); ?></li>
        <li><code>ne</code> - <?php _e('Not equals', 'bridge-mls'); ?></li>
        <li><code>gt</code> - <?php _e('Greater than', 'bridge-mls'); ?></li>
        <li><code>ge</code> - <?php _e('Greater than or equal', 'bridge-mls'); ?></li>
        <li><code>lt</code> - <?php _e('Less than', 'bridge-mls'); ?></li>
        <li><code>le</code> - <?php _e('Less than or equal', 'bridge-mls'); ?></li>
        <li><code>and</code> - <?php _e('Logical AND', 'bridge-mls'); ?></li>
        <li><code>or</code> - <?php _e('Logical OR', 'bridge-mls'); ?></li>
        <li><code>contains()</code> - <?php _e('String contains', 'bridge-mls'); ?></li>
    </ul>
    
    <h3><?php _e('Example API Queries', 'bridge-mls'); ?></h3>
    <pre><code>// Properties in Boston under $500,000
$filter = "City eq 'Boston' and ListPrice le 500000"

// 3+ bedroom homes with photos
$filter = "BedroomsTotal ge 3 and PhotosCount gt 0"

// Recently listed (last 7 days)
$filter = "ListingContractDate ge 2024-01-20"</code></pre>
</section>