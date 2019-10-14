<?php
/**
 * Simple plugin for registering and maintaining frequently used sort codes.
 *
 * @category Site Short codes.
 * @package  Plugin
 * @author   Chris Obrien <cro1979@gmail.com>
 * @license  GPLv2 or later
 */

require_once __DIR__ . '/vendor/autoload.php';

class Site_Shortcodes {
    public function __construct() {
        /* Add menu item */
        add_action('admin_menu', [ $this, 'settings_menu' ]);

		/* Register shortcodes */
		add_shortcode( 'clone_content', [ $this, 'shortcode_clone_content' ] );
		add_shortcode( 'dynamic_image', [ $this, 'shortcode_dynamic_image' ] );
		add_shortcode( 'inline_svg', [ $this, 'shortcode_inline_svg' ] );
	}




    public function settings_menu() { 
        add_menu_page( 
            'Shortcodes', // page tile
            'Shortcodes', // menu title
            'manage_options', // editability
            'Shortcodes', // page slug
            [ $this, 'settings_render'], // page render
            'dashicons-admin-plugins' //menu icon
           );
      }



      // Add new shortcode examples here:
      public function settings_render(){
		if (!current_user_can('manage_options')) {return;}
		//output settings html
		?>
		<div class="wrap">
			<h1><?php _e('Shortcodes', 'cro_sc'); ?></h1>
            <table class="widefat striped">
                <tbody>
                    <tr>
                        <td>
                            <strong>Clone content</strong>
                        </td>
                        <td>
                            <p>A shortcode to clone the content from another page.</p>
                            <h3>Example:</h3>
                            <code>
                            [clone_content path="about"]
                            </code>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <strong>Dynamic Image</strong>
                        </td>
                        <td>
                            <p>A shortcode to process a dynamic image request based on device.</p>
                            <p>Specify IDs to use for mobile (sm) tablet (md) and desktop (lg). Optionally, specify the `inline` parameter to inline SVGs.</p>
                            <h3>Example:</h3>
                            <code>
                            [dynamic_image sm="106" md="107" lg="108" inline="true"]
                            </code>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <strong>Inline SVG</strong>
                        </td>
                        <td>
                            <p>A shortcode to inline an SVG instead of inserting it as an &lt;img&gt;.</p>
                            <p>Specify ID of the SVG to include inline by referencing the ID in the media manager.</p>
                            <h3>Example:</h3>
                            <code>
                            [inline_svg id="106"]
                            </code>
                        </td>
                    </tr>
                </tbody>
            </table>
		</div>
		<?php
	}



	/**
	 * A shortcode to clone content from another page.
	 *
	 * Specify the path of the page to pull content from that page.
	 *
	 * Example:
	 *
	 * [clone_content path="important-safety-information"]
	 *
	 * @param array $atts Attributes passed to the shortcode.
	 *
	 * @access public
	 * @return string The HTML for the rendered shortcode.
	 */
	public function shortcode_clone_content( $atts ) {

		/* Ensure there is a path provided. */
		if ( empty( $atts['path'] ) ) {
			return '';
		}

		/* Try to get the page by path. */
		$page = get_page_by_path( $atts['path'] );
		if ( empty( $page->post_content ) ) {
			return '';
		}

		return apply_filters( 'the_content', $page->post_content );
	}

	/**
	 * A shortcode to process a dynamic image request.
	 *
	 * Specify IDs to use for mobile (sm) tablet (md) and desktop (lg). Optionally, specify the `inline` parameter to inline SVGs.
	 *
	 * Example:
	 *
	 * [dynamic_image sm="106" md="107" lg="108" inline="true"]
	 *
	 * @param array $atts Attributes passed to the shortcode.
	 *
	 * @access public
	 * @return string The HTML for the rendered shortcode.
	 */
	public function shortcode_dynamic_image( $atts ) {

		/* Negotiate the appropriate ID to use. */
		$id     = 0;
		$detect = new Mobile_Detect;
		if ( $detect->isTablet() && ! empty( $atts['md'] ) ) {
			$id = $atts['md'];
		} elseif ( $detect->isMobile() && ! empty( $atts['sm'] ) ) {
			$id = $atts['sm'];
		} elseif ( ! empty( $atts['lg'] ) ) {
			$id = $atts['lg'];
		}

		/* Ensure we got an ID. */
		if ( empty( $id ) ) {
			return '';
		}

		/* Determine whether we are inlining. */
		if ( ! empty( $atts['inline'] ) && $atts['inline'] === 'true' ) {
			return $this->shortcode_inline_svg( [ 'id' => $id ] );
		}

		return wp_get_attachment_image( $id, 'full' );
	}

	/**
	 * A shortcode to inline an SVG instead of inserting it as an <img>.
	 *
	 * Specify ID of the SVG to include inline by referencing the ID in the media manager.
	 *
	 * Example:
	 *
	 * [inline_svg id="106"]
	 *
	 * @param array $atts Attributes passed to the shortcode.
	 *
	 * @access public
	 * @return string The HTML for the rendered shortcode.
	 */
	public function shortcode_inline_svg( $atts ) {

		/* Ensure that we got an ID to look up. */
		if ( empty( $atts['id'] ) ) {
			return '';
		}

		/* Try to get the absolute filepath of the image based on the ID. */
		$image = get_attached_file( $atts['id'] );
		if ( empty( $image ) ) {
			return '';
		}

		/* Ensure that the file being referenced is an SVG. */
		$pathinfo = pathinfo( $image );
		if ( empty( $pathinfo['extension'] ) || ( strtolower( $pathinfo['extension'] ) !== 'svg' && strtolower( $pathinfo['extension'] ) !== 'svgz' ) ) {
			return '';
		}

		return file_get_contents( $image );
	}


}