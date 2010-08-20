<?php
/*
	Plugin Name: Custom Categories RSS
	Plugin URI: http://www.techforum.sk/
	Description: Grab RSS only from specific categories
	Version: 0.1
	Author: Ján Bočínec
	Author URI: http://johnnypea.wp.sk/
	License: GPL2


 	Copyright 2010  Jan Bocinec  (email : jan.bocinec@wp.sk)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as 
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * Initialize localization.
 * @since 0.1
 */

$plugin_dir = basename(dirname(__FILE__));
load_plugin_textdomain( 'ccrss', 'wp-content/plugins/' . $plugin_dir, $plugin_dir );

/**
 * "ccfeed" content.
 * @since 0.1
 */

function cc_rss_create_feed() { 

	$numposts = -1;

	$categories = get_categories();
	
	$count_cat = count($categories);
	
	$catids = '';
	
	for ($i = 1; $i <= $count_cat; $i++) {
		if( $_GET["cat$i"] ) {
	    $catids .= $_GET["cat$i"] . ',';
		}
	}	

	$count = count(explode(',', $catids)) - 1;
	
//header('Content-Type: ' . feed_content_type('rss-http') . '; charset=' . get_option('blog_charset'), true);
$more = 1;

echo '<?xml version="1.0" encoding="'.get_option('blog_charset').'"?'.'>'; ?>

<rss version="2.0"
	xmlns:content="http://purl.org/rss/1.0/modules/content/"
	xmlns:wfw="http://wellformedweb.org/CommentAPI/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:atom="http://www.w3.org/2005/Atom"
	xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
	xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
	<?php do_action('rss2_ns'); ?>
>
	
<channel>
	  <title><?php echo _n("RSS from a category", "RSS from the categories", $count, 'ccrss');  ?>
	<?php 
	//list categories included in feed
	$get_catnames = get_categories( 'include=' . $catids  );
	$count_catnames = count($get_catnames);
	$i = 1;
	foreach($get_catnames as $catnames) {
		echo $catnames->cat_name;
		if($i < $count_catnames) {
			echo ',';
		}		
		$i++;
	} ?>
		</title>
		
	<atom:link href="<?php self_link(); ?>" rel="self" type="application/rss+xml" />
	<link><?php bloginfo_rss('url') ?></link>
	<description><?php bloginfo_rss("description") ?></description>
	<lastBuildDate><?php echo mysql2date('D, d M Y H:i:s +0000', get_lastpostmodified('GMT'), false); ?></lastBuildDate>
	<?php the_generator( 'rss2' ); ?>
	<language><?php echo get_option('rss_language'); ?></language>
	<sy:updatePeriod><?php echo apply_filters( 'rss_update_period', 'hourly' ); ?></sy:updatePeriod>
	<sy:updateFrequency><?php echo apply_filters( 'rss_update_frequency', '1' ); ?></sy:updateFrequency>
	<?php do_action('rss2_head'); ?>
	<?php query_posts('cat=' . $catids . '&showposts='.$numposts); global $post;?>
	<?php while( have_posts()) : the_post(); ?>
	<item>
		<title><?php the_title_rss() ?></title>
		<link><?php the_permalink_rss() ?></link>
		<comments><?php comments_link(); ?></comments>
		<pubDate><?php echo mysql2date('D, d M Y H:i:s +0000', get_post_time('Y-m-d H:i:s', true), false); ?></pubDate>
		<dc:creator><?php the_author() ?></dc:creator>
		<?php the_category_rss() ?>

		<guid isPermaLink="false"><?php the_guid(); ?></guid>
<?php if (get_option('rss_use_excerpt')) : ?>
		<description><![CDATA[<?php the_excerpt_rss() ?>]]></description>
<?php else : ?>
		<description><![CDATA[<?php the_excerpt_rss() ?>]]></description>
	<?php if ( strlen( $post->post_content ) > 0 ) : ?>
		<content:encoded><![CDATA[<?php the_content_feed('rss2') ?>]]></content:encoded>
	<?php else : ?>
		<content:encoded><![CDATA[<?php the_excerpt_rss() ?>]]></content:encoded>
	<?php endif; ?>
<?php endif; ?>
		<wfw:commentRss><?php echo get_post_comments_feed_link(null, 'rss2'); ?></wfw:commentRss>
		<slash:comments><?php echo get_comments_number(); ?></slash:comments>
<?php rss_enclosure(); ?>
	<?php do_action('rss2_item'); ?>
	</item>
	<?php endwhile; ?>
</channel>
</rss>

<?php	
}

/**
 * Add a new feed type "ccfeed".
 * @since 0.1
 */

add_action('init', 'cc_rss_add_feed');

function  cc_rss_add_feed() {
  add_feed('ccfeed', 'cc_rss_create_feed');
}

/**
 * Front Select Form.
 * @since 0.1
 */

function cc_rss_front_form( $exids = '') { ?>
	
<div id="ccrss">
<form name="ccrssForm" method="get" action="<?php 
if(substr(get_bloginfo('rss2_url'), -1, 1) == '/') {
	bloginfo('rss2_url');
	echo 'ccfeed';	
} else {
	bloginfo('rss2_url');
	echo '/ccfeed';
}
?>">
<div id="ccrss-checkwrap">
 <?php $categories = get_categories( 'exclude=' . $exids ); 

$i=1;
foreach ($categories as $cat) {
	
	echo '<div class="ccrss-checkbox"><input type="checkbox" name="cat' . $i . '" value="' . $cat->cat_ID . '" /> ' . $cat->cat_name . '</div>';
	
	$i++;
} ?>

</div>
<br />
<input id="all-button" type="button" onclick="window.location.href='<?php bloginfo('rss2_url'); ?>'" style="float:left;" value="<?php _e('All RSS from this site', 'ccrss'); ?>"/>
<input id="submit-button" type="submit" value="<?php _e('Submit', 'ccrss'); ?>" style="float:left;"/>
</form>
</div>

<?php }

/**
 * CC RSS WIDGET
 * @since 0.1
 */

	/**
	 * Add function to widgets_init that'll load our widget.
	 * @since 0.1
	 */
	add_action( 'widgets_init', 'cc_rss_load_widget' );

	/**
	 * Register our widget.
	 * 'CC_RSS_Widget' is the widget class used below.
	 *
	 * @since 0.1
	 */
	function cc_rss_load_widget() {
		register_widget( 'CC_RSS_Widget' );
	}

	/**
	 * Example Widget class.
	 * This class handles everything that needs to be handled with the widget:
	 * the settings, form, display, and update.  Nice!
	 *
	 * @since 0.1
	 */
	class CC_RSS_Widget extends WP_Widget {

		/**
		 * Widget setup.
		 */
		function CC_RSS_Widget() {
			/* Widget settings. */
			$widget_ops = array( 'classname' => 'ccrss', 'description' => __('Add Custom Categories RSS Widget to Your Sidebar', 'ccrss') );

			/* Widget control settings. */
			$control_ops = array( 'width' => 300, 'height' => 350, 'id_base' => 'cc-rss-widget' );

			/* Create the widget. */
			$this->WP_Widget( 'cc-rss-widget', __('Custom Categories RSS Widget', 'ccrss'), $widget_ops, $control_ops );
		}

		/**
		 * How to display the widget on the screen.
		 */
		function widget( $args, $instance ) {
			extract( $args );

			/* Our variables from the widget settings. */
			$title = apply_filters('widget_title', $instance['title'] );
			$wcats = $instance['wcats'];

			/* Before widget (defined by themes). */
			echo $before_widget;

			/* Display the widget title if one was input (before and after defined by themes). */
			if ( $title )
				echo $before_title . $title . $after_title;
			
			/* Display form. */
			cc_rss_front_form(  $cats = $wcats ); 
			
			/* After widget (defined by themes). */
			echo $after_widget;
		}

		/**
		 * Update the widget settings.
		 */
		function update( $new_instance, $old_instance ) {
			$instance = $old_instance;

			/* Strip tags for title and name to remove HTML (important for text inputs). */
			$instance['title'] = strip_tags( $new_instance['title'] );
			$instance['wcats'] = strip_tags( $new_instance['wcats'] );

			return $instance;
		}

		/**
		 * Displays the widget settings controls on the widget panel.
		 * Make use of the get_field_id() and get_field_name() function
		 * when creating your form elements. This handles the confusing stuff.
		 */
		function form( $instance ) {

			/* Set up some default widget settings. */
			$defaults = array( 'wcats' => '' );
			$instance = wp_parse_args( (array) $instance, $defaults ); ?>

			<!-- Widget Title: Text Input -->
			<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title:', 'ccrss'); ?></label>
				<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:90%;" />
			</p>
				
			<!-- Category IDs: ID Input -->
			<p>
				<label for="<?php echo $this->get_field_id( 'wcats' ); ?>"><?php _e('Exclude category IDs (a comma-separated list of categories by unique ID; e.g. 3,5,9,16):', 'ccrss'); ?></label>
				<input id="<?php echo $this->get_field_id( 'wcats' ); ?>" name="<?php echo $this->get_field_name( 'wcats' ); ?>" value="<?php echo $instance['wcats']; ?>" style="width:90%;" />
			</p>

		<?php
		}
	}

/**
 * Shortcode form.
 *
 * @since 0.1
 */
function cc_rss_front_form_func( $atts ) {
	extract(shortcode_atts(array(
			'exids' => '',
		), $atts));
	
	ob_start();
	cc_rss_front_form( $exids = $exids );
	$output_string = ob_get_contents();
	ob_end_clean();

	return $output_string;
	
}
add_shortcode('ccrss', 'cc_rss_front_form_func');
?>