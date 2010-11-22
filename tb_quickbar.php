<?php
/*
Plugin Name: TwoBeers Quickbar
Plugin URI: http://www.twobeers.net/annunci/quickbar-plugins
Description: Quick access to blog contents and fast navigation for WordPress
Version: 0.2
Author: TB Crew
Author URI: http://www.twobeers.net/
License: GNU General Public License, version 2 (http://www.gnu.org/licenses/gpl-2.0.html)

Copyright 2010  TwoBeers.net Crew  (email : light@twobeers.net)

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

if( !class_exists( 'TBQuickBarPlugin' ) ) {
	class TBQuickBarPlugin {

		// define class variable
		var $tbqb_options_name = 'TBQuickBarPluginOptions';
		var $version = '0.1beta4';

		function TBQuickBarPlugin() {

			// define plugin name
			$this->plugin_name = plugin_basename(__FILE__);
			// define plugin URLPATH
			define('TBQBPLGN_URLPATH', trailingslashit( WP_PLUGIN_URL . '/' . plugin_basename( dirname(__FILE__) ) ) );

			// action and filters
			// Init options during activation & deregister init option
			register_activation_hook( $this->plugin_name, array( &$this, 'tbqb_activate' ) );
			register_deactivation_hook( $this->plugin_name, array(&$this, 'tbqb_deactivate') );
			//hooks
			add_action( 'init', array( &$this, 'tb_quickbar_init' ) );
			add_action( 'template_redirect', array( &$this, 'tbqb_scripts' ) );
			add_action( 'wp_print_styles', array( &$this, 'tbqb_style' ) );
			add_action( 'wp_footer', array( &$this, 'tbqb_add_quickbar' ) );
			//filters
			add_filter( 'the_content', array( &$this, 'tbqb_wmode_in_embed' ), 9 ) ;
			// create custom plugin settings page
			add_action( 'admin_menu', array( &$this, 'tbqb_create_settings_page' ) );
			add_action( 'admin_init', array( &$this, 'register_tbqb_settings' ) );
		}

		//register the settings
		function register_tbqb_settings() {
			register_setting( $this->tbqb_options_name . 'Group', $this->tbqb_options_name, array( &$this, 'tbqb_sanitaze_options' ) );
		}

		// register plugin options
		function tbqb_activate() {
			if ( current_user_can( 'manage_options' ) ) {
				$tbqb_options_array = array(
					'fontcolor' => '#000000',
					'backcolor' => '#FFFFFF',
					'bordercolor' => '#000000',
					'highcolor' => '#5F5F5F',
					'quickbar' => 1,
					'easynavi' => 1,
					'meta' => 1,
					'username' => 1,
					'welcome' => 1,
					'welcometxt' => 'Welcome',
					'gravatar' => 1,
					'gravatartype' => 'current-user',
					'gravatardatas' => '',
					'search' => 1,
					'date' => 1,
					'jsani' => 1
					);

				// store default options
				update_option( $this->tbqb_options_name, $tbqb_options_array );
			}
		}

		// deactivate plugin
		function tbqb_deactivate() {
			delete_option( $this->tbqb_options_name );
		}

		// sanitaze and validate input
		function tbqb_sanitaze_options($input) {

			$tbqb_options_array = array(
					'fontcolor',
					'backcolor',
					'bordercolor',
					'highcolor',
					'quickbar',
					'easynavi',
					'meta',
					'username',
					'welcome',
					'welcometxt',
					'gravatar',
					'gravatartype',
					'gravatardatas',
					'search',
					'date',
					'jsani'
					);

			foreach( $tbqb_options_array as $key ) {
				if( !isset( $input[$key] ) ) $input[$key] = '';
			}

			$input['fontcolor'] = esc_url( substr( $input['fontcolor'], 0 ,7 ) );
			$input['backcolor'] = esc_url( substr( $input['backcolor'], 0 ,7 ) );
			$input['bordercolor'] = esc_url( substr( $input['bordercolor'], 0 ,7 ) );
			$input['highcolor'] = esc_url( substr( $input['highcolor'], 0 ,7 ) );

			$input['quickbar'] = ( $input['quickbar'] == 1 ? 1 : 0 );
			$input['easynavi'] = ( $input['easynavi'] == 1 ? 1 : 0 );
			$input['meta'] = ( $input['meta'] == 1 ? 1 : 0 );
			$input['username'] = ( $input['username'] == 1 ? 1 : 0 );
			$input['welcome'] = ( $input['welcome'] == 1 ? 1 : 0 );
			$input['gravatar'] = ( $input['gravatar'] == 1 ? 1 : 0 );
			$input['search'] = ( $input['search'] == 1 ? 1 : 0 );
			$input['date'] = ( $input['date'] == 1 ? 1 : 0 );
			$input['jsani'] = ( $input['jsani'] == 1 ? 1 : 0 );

			if ( empty( $input['welcometxt'] ) ) {
				$input['welcometxt'] ='Welcome';
			} else {
				$input['welcometxt'] = esc_attr( substr( $input['welcometxt'], 0, 20 ) );
			}

			if ( empty( $input['gravatartype'] ) ) {
				$input['gravatartype'] = 'current-user';
			} else {
				$input['gravatartype'] = ( $input['gravatartype'] == 'current-user' ? 'current-user' : 'fixed' );
			}

			if ( $input['gravatartype'] == 'current-user' ) {
				$input['gravatardatas'] = '' ;
			}

			if ( $input['gravatartype'] == 'fixed' && empty( $input['gravatardatas'] ) ) {
				$input['gravatardatas'] = esc_url_raw( TBQBPLGN_URLPATH . 'images/user.png' );
			} else if ( $input['gravatartype'] == 'fixed' && is_email( $input['gravatardatas'] ) ) {
				$input['gravatardatas'] = sanitize_email( $input['gravatardatas'] );
			} else {
				$input['gravatardatas'] = esc_url_raw( $input['gravatardatas'] );
			}

			return $input;
		}

		// Output the quickbar
		function tbqb_add_quickbar() {
			global $current_user, $post;
			$tbqb_options = get_option( $this->tbqb_options_name );

			?>
			<!-- begin easynavi -->
			<?php if ( $tbqb_options['easynavi'] == 1 ) { ?>
				<div id="tbqb-bottom_ref" style="clear: both; height: 1px;"> </div>
				<div id="tbqb-navi_div">

					<div id="tbqb-navi_cont">
						<?php if ( is_singular() ) { ?>

							<div class="tbqb-minibutton">
								<a href="javascript:window.print()" id="print_button" title="<?php _e('Print'); ?>" style="display: none;">
									<div class="tbqb-navi_buttons" style="background-position: 0 top">
										<span class="nb_tooltip"><?php _e( 'Print' ); ?></span>
									</div>
								</a>
							</div>
							<script type="text/javascript" defer="defer">
								document.getElementById('print_button').style.display = '';
							</script>
							<?php if ( comments_open( $post->ID ) && !post_password_required() ) { ?>
								<div class="tbqb-minibutton">
									<a href="#respond" title="<?php _e( 'Leave a comment' ); ?>">
										<div class="tbqb-navi_buttons" style="background-position: -16px top">
											<span class="nb_tooltip"><?php _e( 'Leave a comment' ); ?></span>
										</div>
									</a>
								</div>
								<div class="tbqb-minibutton">
									<a href="<?php echo get_post_comments_feed_link( $post->ID, 'rss2' ); ?> " title="<?php _e( 'Feed for comments on this post', 'tbqb' ); ?>">
										<div class="tbqb-navi_buttons" style="background-position: -32px top">
											<span class="nb_tooltip"><?php _e( 'Feed for comments on this post', 'tbqb' ); ?></span>
										</div>
									</a>
								</div>
								<?php if ( pings_open() ) { ?>
									<div class="tbqb-minibutton">
										<a href="<?php global $tmptrackback; echo $tmptrackback; ?>" rel="trackback" title="Trackback URL">
											<div class="tbqb-navi_buttons" style="background-position: -48px top">
												<span class="nb_tooltip"><?php _e( 'Trackback URL', 'tbqb' ); ?></span>
											</div>
										</a>
									</div>
								<?php } ?>
							<?php } ?>
							<div class="tbqb-minibutton">
								<a href="<?php echo home_url(); ?>" title="<?php _e( 'Home' ); ?>">
									<div class="tbqb-navi_buttons" style="background-position: -64px top">
										<span class="nb_tooltip"><?php _e( 'Home' ); ?></span>
									</div>
								</a>
							</div>


							<?php if ( is_page() ) {
								$page_nav_links = $this->tbqb_page_navi( $post->ID ); // get the menu-ordered prev/next pages links
								if ( isset ( $page_nav_links['prev'] ) ) { // prev page link ?>
									<div class="tbqb-minibutton">
										<a href="<?php echo $page_nav_links['prev']['link']; ?>" title="<?php echo $page_nav_links['prev']['title']; ?>">
											<div class="tbqb-navi_buttons" style="background-position: -80px top">
												<span class="nb_tooltip"><?php echo __( 'Previous page' ) . ': ' . $page_nav_links['prev']['title']; ?></span>
											</div>
										</a>
									</div>
								<?php }
								if ( isset ( $page_nav_links['next'] ) ) { // next page link ?>
									<div class="tbqb-minibutton">
										<a href="<?php echo $page_nav_links['next']['link']; ?>" title="<?php echo $page_nav_links['next']['title']; ?>">
											<div class="tbqb-navi_buttons" style="background-position: -96px top">
												<span class="nb_tooltip"><?php echo __( 'Next page' ) . ': ' . $page_nav_links['next']['title']; ?></span>
											</div>
										</a>
									</div>
								<?php } ?>
							<?php } elseif ( !is_attachment() ) { ?>
								<div class="tbqb-minibutton">
									<?php next_post_link( '%link', '<div class="tbqb-navi_buttons" style="background-position: -80px top"><span class="nb_tooltip">' . __( 'Next Post' ) . ': %title</span></div>' ); ?>
								</div>

								<div class="tbqb-minibutton">
									<?php previous_post_link( '%link', '<div class="tbqb-navi_buttons" style="background-position: -96px top"><span class="nb_tooltip">' . __( 'Previous Post' ) . ': %title</span></div>' ); ?>
								</div>
							<?php } else { ?>
								<?php if ( !empty( $post->post_parent ) ) { ?>
									<div class="tbqb-minibutton">
										<a href="<?php echo get_permalink( $post->post_parent ); ?>" title="<?php esc_attr( printf( __( 'Return to %s', 'tbqb' ), get_the_title( $post->post_parent ) ) ); ?>" rel="gallery">
											<div class="tbqb-navi_buttons" style="background-position: -80px top">
												<span class="nb_tooltip"><?php esc_attr( printf( __( 'Return to %s', 'tbqb' ), get_the_title( $post->post_parent ) ) ); ?></span>
											</div>
										</a>
									</div>
								<?php } ?>
							<?php } ?>

						<?php } else { // index navigation ?>
							<div class="tbqb-minibutton">
								<a href="<?php home_url(); ?>" title="<?php _e( 'Home' ); ?>">
									<div class="tbqb-navi_buttons" style="background-position: -64px top">
										<span class="nb_tooltip"><?php _e( 'Home' ); ?></span>
									</div>
								</a>
							</div>
							<div class="tbqb-minibutton">
								<?php  previous_posts_link( '<div title="' . __( 'Newer Posts','tbqb' ) . '" class="tbqb-navi_buttons" style="background-position: -80px top"><span class="nb_tooltip">' . __( 'Newer Posts','tbqb' ) . '</span></div>' ); ?>
							</div>
							<div class="tbqb-minibutton">
								<?php  next_posts_link( '<div title="' . __( 'Older Posts','tbqb' ) . '" class="tbqb-navi_buttons" style="background-position: -96px top"><span class="nb_tooltip">' . __( 'Older Posts','tbqb' ) . '</span></div>' ); ?>
							</div>
						<?php } ?>

						<div class="tbqb-minibutton">
							<a href="#" title="<?php _e( 'Top of page', 'tbqb' ); ?>">
								<div class="tbqb-navi_buttons" style="background-position: -112px top">
									<span class="nb_tooltip"><?php _e( 'Top of page', 'tbqb' ); ?></span>
								</div>
							</a>
						</div>
						<div class="tbqb-minibutton">
							<a href="#tbqb-bottom_ref" title="<?php _e( 'Bottom of page', 'tbqb' ); ?>">
								<div class="tbqb-navi_buttons" style="background-position: -128px top">
									<span class="nb_tooltip"><?php _e( 'Bottom of page', 'tbqb' ); ?></span>
								</div>
							</a>
						</div>
					</div>

				</div>
			<?php } ?>
			<!-- end easynavi -->

			<!-- begin quickbar -->
			<?php if ( $tbqb_options['quickbar'] == 1 ) { ?>
				<div id="tbqb_quickbar">
					<div id="tbqb_right">
						<?php if ( $tbqb_options['search'] == 1 ) { ?>
							<div id="tbqb_search">
								<form id="tbqb_searchform" method="get" action="<?php bloginfo( 'url' ); ?>">
									<input type="text" onfocus="if (this.value == '<?php esc_attr_e( 'Search' ) ?>...') {this.value = '';}" onblur="if (this.value == '') {this.value = '<?php esc_attr_e( 'Search' ) ?>...';}" id="tbqb_s" name="s" value="<?php esc_attr_e( 'Search' ) ?>..." size="30" />
									<input type="hidden" id="searchsubmit">
								</form>
							</div>
						<?php } ?>
						<?php if ( $tbqb_options['date'] == 1 ) { ?>
							<small><?php esc_attr_e( 'Today is ','tbqb' ); echo date_i18n( 'l' ); ?><br /><?php echo date_i18n( __( 'F j, Y' ) ); ?></small>
						<?php } ?>
					</div>
					<ul id="tbqb_main_ul">
						<?php if ( $tbqb_options['gravatar'] == 1 ) { ?>
							<li id="tbqb_avatar_cont"><?php $this->tbqb_gravatar(); ?></li>
						<?php } ?>
						<?php if ( $tbqb_options['meta'] == 1 ) { ?>
							<li class="tbqb_widget">
								<h4 class="tbqb_w_title">
									<?php
									if ( $tbqb_options['welcome'] == 1 ) {
										esc_attr_e( $tbqb_options['welcometxt'] );
										echo '&nbsp;&nbsp;';
										if ( is_user_logged_in() && $tbqb_options['username'] == 1 ) {
											echo $current_user->display_name;
										}
									} else {
										echo 'Meta';
									}
									?>
									 &raquo;
								</h4>
								<div class="fw_pul_cont">
									<div class="fw_pul">
										<ul class="">
											<?php wp_register(); ?>
											<?php if ( is_user_logged_in() ) {?>
												<li><a href="<?php echo esc_url( admin_url( 'profile.php' ) ); ?>" title="<?php esc_attr_e( 'Your Profile' ); ?>"><?php esc_attr_e( 'Your Profile' ); ?></a></li>
												<li><a title="<?php esc_attr_e( 'Add New Post' ); ?>" href="<?php echo esc_url( admin_url( 'post-new.php' ) ); ?>" title="<?php esc_attr_e( 'New Post' ); ?>"><?php esc_attr_e( 'New Post' ); ?></a></li>
												<?php if ( current_user_can( 'moderate_comments' ) ) { ?>
													<li><a title="<?php _e( 'Comments' ); ?>" href="<?php echo esc_url( admin_url( 'edit-comments.php' ) ); ?>" title="<?php _e( 'Comments' ); ?>"><?php _e( 'Comments' ); ?></a></li>
												<?php } ?>
											<?php } ?>
											<li><?php wp_loginout(); ?></li>
										</ul>
									</div>
								</div>
							</li>
						<?php } ?>

						<?php
						//load the tb_quickbar sidebar and display quickbar widgets
						dynamic_sidebar( 'tb_quickbar' );
						?>
					</ul>
				</div>
			<?php } ?>
			<!-- end quickbar -->
			<?php
		}

		// pages navigation links
		function tbqb_page_navi($this_page_id) {
			$pages = get_pages( array( 'sort_column' => 'menu_order' ) ); // get the menu-ordered list of the pages
			$page_links = array();
			foreach ( $pages as $k => $pagg ) {
				if ( $pagg->ID == $this_page_id ) { // we are in this $pagg
					if ( $k == 0 ) { // is first page
						$page_links['next']['link'] = get_page_link($pages[1]->ID);
						$page_links['next']['title'] = $pages[1]->post_title;
						if ( $page_links['next']['title'] == '' ) $page_links['next']['title'] = __( '(no title)' );
					} elseif ( $k == ( count( $pages ) -1 ) ) { // is last page
						$page_links['prev']['link'] = get_page_link($pages[$k - 1]->ID);
						$page_links['prev']['title'] = $pages[$k - 1]->post_title;
						if ( $page_links['prev']['title'] == '' ) $page_links['prev']['title'] = __( '(no title)' );
					} else {
						$page_links['next']['link'] = get_page_link($pages[$k + 1]->ID);
						$page_links['next']['title'] = $pages[$k + 1]->post_title;
						if ( $page_links['next']['title'] == '' ) $page_links['next']['title'] = __( '(no title)' );
						$page_links['prev']['link'] = get_page_link($pages[$k - 1]->ID);
						$page_links['prev']['title'] = $pages[$k - 1]->post_title;
						if ( $page_links['prev']['title'] == '' ) $page_links['prev']['title'] = __( '(no title)' );
					}
				}
			}
			return $page_links;
		}

		// define gravatar
		function tbqb_gravatar(){
			global $current_user;
			$tbqb_options = get_option( $this->tbqb_options_name );

			if ( $tbqb_options['gravatartype'] == 'current-user' ) {
				if ( is_user_logged_in() ) { //fix for notice when user not log-in
					get_currentuserinfo();
					$email = $current_user->user_email;
					echo get_avatar( sanitize_email( $email ), 50, $default = esc_url( TBQBPLGN_URLPATH . 'images/user.png' ),'user-avatar' );
				} else {
					echo get_avatar( 'dummyemail', 50, $default = esc_url( TBQBPLGN_URLPATH . 'images/user.png' ),'user-avatar' );
				}
			}else{
				if ( $tbqb_options['gravatartype'] == 'fixed' && is_email( $tbqb_options['gravatardatas'] ) ) {
					echo get_avatar( sanitize_email( $tbqb_options['gravatardatas'] ) , 50, $default = esc_url( TBQBPLGN_URLPATH . 'images/user.png' ),'user-avatar' );
				} else {
					echo get_avatar( 'dummyemail', 50, $default = esc_url( $tbqb_options['gravatardatas'] ) ,'user-avatar' );
				}
			}

		}

		// add scripts
		function tbqb_scripts(){
			$tbqb_options = get_option( $this->tbqb_options_name );

			if ( isset( $tbqb_options['jsani'] ) && $tbqb_options['jsani']==1 ) {
				// enqueue the script
				wp_enqueue_script( 'tbqb_script_animations', TBQBPLGN_URLPATH . 'js/tbqb-script.js' , array('jquery'), $this->version, true );
			}
		}

		// add style
		function tbqb_style() {
			$tbqb_options = get_option( $this->tbqb_options_name );
			wp_enqueue_style( 'tbqb_output', TBQBPLGN_URLPATH . 'css/tbqb_style.css', false, $this->version, 'screen' );

			echo '
			<style type="text/css" media="screen">
				#tbqb_quickbar {
					color: ' . $tbqb_options['fontcolor'] . ';
					background: ' . $tbqb_options['backcolor'] . ' url(\'' . TBQBPLGN_URLPATH . 'images/overlay.png\') left top repeat-x;
					border:1px solid ' . $tbqb_options['bordercolor'] . ';
				}
				#tbqb_quickbar h4 {
					color: ' . $tbqb_options['fontcolor'] . ';
				}
				#tbqb_quickbar #tbqb_s {
					background: transparent url(\'' . TBQBPLGN_URLPATH . 'images/lens.png\') 1px center no-repeat;
					color: ' . $tbqb_options['fontcolor'] . ';
					border:1px solid ' . $tbqb_options['bordercolor'] . ';
				}
				#tbqb_quickbar a {
					color: ' . $tbqb_options['fontcolor'] . ';
				}
				#tbqb_quickbar a:hover {
					color: ' . $tbqb_options['highcolor'] . ';
				}
				#tbqb_quickbar .tbqb_widget {
					border-right: 1px solid ' . $tbqb_options['bordercolor'] . ';
				}
				#tbqb_quickbar .tbqb_widget:hover h4{
					color: ' . $tbqb_options['highcolor'] . ';
				}
				#tbqb_quickbar .fw_pul{
					color: ' . $tbqb_options['fontcolor'] . ';
					border-color: ' . $tbqb_options['bordercolor'] . ';
					background: ' . $tbqb_options['backcolor'] . ' url(\'' . TBQBPLGN_URLPATH . 'images/overlay.png\') left top repeat-x;
				}
				#tbqb-navi_div {
					border-color: ' . $tbqb_options['bordercolor'] . ';
					background: ' . $tbqb_options['backcolor'] . ' url(\'' . TBQBPLGN_URLPATH . 'images/overlay.png\') left top repeat-x;
				}
				.tbqb-navi_buttons:hover {
					background-color: ' . $tbqb_options['highcolor'] . ';
				}
				.tbqb-navi_buttons {
					background-image: url(\'' . TBQBPLGN_URLPATH . 'images/buttons.png\');
				}
				.tbqb-navi_buttons .nb_tooltip {
					background: ' . $tbqb_options['backcolor'] . ' url(\'' . TBQBPLGN_URLPATH . 'images/litem.png\') no-repeat scroll right center;
					border: 1px solid ' . $tbqb_options['bordercolor'] . ';
				}
			</style>
			<style media="print" type="text/css">
				#tbqb_quickbar {
					display: none;
				}
			</style>';
		}

		// fix the wmode issue for embed videos
		function tbqb_wmode_in_embed($content) {
			$content = str_replace( '<param name="allowscriptaccess" value="always">', '<param name="allowscriptaccess" value="always"><param name="wmode" value="transparent">', $content );
			$content = str_replace( '<embed ', '<embed wmode="transparent" ', $content );
			return $content;
		}

		// quickbar init
		function tb_quickbar_init() {
			// Add localization support
			load_plugin_textdomain( 'tbqb', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
			// Register the Quickbar as sidebar
			register_sidebar( array(
				'name'          =>	'TBQuickbar',
				'id'            =>	'tb_quickbar',
				'description'   =>	__('drag here your favorite widgets','tbqb'),
				'before_widget'	=>	'<li id="%1$s" class="tbqb_widget %2$s">',
				'after_widget'	=>	'</div></div></li>',
				'before_title'	=>	'<h4 class="tbqb_w_title">',
				'after_title'		=>	' &raquo;</h4><div class="fw_pul_cont"><div class="fw_pul">',
			));
		}

		// create plugin options page
		function tbqb_create_settings_page() {
			$page = add_plugins_page( 'TB Quickbar Settings', 'TwoBeers Quickbar', 'manage_options', 'tb-quickbar-settings', array( &$this, 'tbqb_settings_page' ) );
			add_action( 'admin_print_scripts-' . $page, array( &$this, 'tbqb_settings_page_head' ) );
		}

		// admin styles and scripts
		function tbqb_settings_page_head() {
			$tbqb_options = get_option( $this->tbqb_options_name );

			wp_enqueue_script( 'tb_color_picker', TBQBPLGN_URLPATH . 'js/tb_color_picker.js', array(), '0.1', false );
			echo '<link rel="stylesheet" href="' . TBQBPLGN_URLPATH . 'css/tbqb_settings_style.css" type="text/css" />';

			echo '
			<style type="text/css">
				#quickbarpreview {
					background-image: url(\'' . TBQBPLGN_URLPATH . 'images/overlay.png\');
					background-color: ' . $tbqb_options['backcolor'] . ';
					border: 1px solid ' . $tbqb_options['bordercolor'] . ';
				}
				#qbp_text {
					color: ' . $tbqb_options['fontcolor'] . ';
					border-right: 1px solid ' . $tbqb_options['bordercolor'] . ';
				}
				#qbp_hitext {
					color: ' . $tbqb_options['highcolor'] . ';
				}
			</style>';
		}

		// set up settings page
		function tbqb_settings_page() {
		  if ( !current_user_can( 'manage_options' ) ) {
		    wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		  }

		  $tbqb_options = get_option( $this->tbqb_options_name );

			//check for updated values and return false for disabled ones
			if ( isset($_REQUEST['updated']) ) {
				//return options save message
				echo '<div id="message" class="updated"><p><strong>'.__('Options saved.').'</strong></p></div>';
			}

			echo '
			<script type="text/javascript">
				// <![CDATA[
				crossHairsImg.src = \'' . TBQBPLGN_URLPATH . 'images/crosshairs.png\';
				huePositionImg.src = \'' . TBQBPLGN_URLPATH . 'images/position.png\';
				hueSelectorImg.src = \'' . TBQBPLGN_URLPATH . 'images/h.png\';
				satValImg.src = \'' . TBQBPLGN_URLPATH . 'images/sv.png\';
				// ]]>
			</script>';
			?>

			<div class="wrap">
			<h2>Quickbar Settings</h2>
				<form method="post" action="options.php">
					<?php settings_fields( $this->tbqb_options_name . 'Group' ); ?>
					<div id="tbqb-appearance" class="stylediv" >
						<h3><?php esc_attr_e('Appearance','tbqb'); ?></h3>
						<div id="quickbarpreview">
							<p><span id="qbp_text"><?php esc_attr_e('Normal','tbqb'); ?></span><span id="qbp_hitext"><?php esc_attr_e('Highlighted','tbqb'); ?></span></p>
						</div>
						<div id="jsForm" >
							<table style="border-collapse: collapse; width: 65%;">
								<tr>
									<th><?php _e('name','tbqb'); ?></th>
									<th><?php _e('current','tbqb'); ?></th>
									<th><?php _e('select new','tbqb'); ?></th>
								</tr>
								<tr>
									<td class="tbqb_first_td"><?php esc_attr_e('Normal Text','tbqb'); ?></td>
									<td class="tbqb_second_td"><?php echo $tbqb_options['fontcolor']; ?></td>
									<td style="font-style:italic;text-align:center;"><input type="text" name="<?php echo $this->tbqb_options_name; ?>[fontcolor]" id="fontcolor" value="<?php echo $tbqb_options['fontcolor']; ?>"  onclick="startColorPicker(this)" onkeyup="maskedHex(this)" style="background-color:<?php echo $tbqb_options['fontcolor']; ?>;" /></td>
								</tr>
								<tr>
									<td class="tbqb_first_td"><?php esc_attr_e('Highlighted Text','tbqb'); ?></td>
									<td class="tbqb_second_td"><?php echo $tbqb_options['highcolor']; ?></td>
									<td style="font-style:italic;text-align:center;"><input type="text" name="<?php echo $this->tbqb_options_name; ?>[highcolor]" id="hifontcolor" value="<?php echo $tbqb_options['highcolor']; ?>"  onclick="startColorPicker(this)" onkeyup="maskedHex(this)" style="background-color:<?php echo $tbqb_options['highcolor']; ?>;" /></td>
								</tr>
								<tr>
									<td class="tbqb_first_td"><?php esc_attr_e('Background','tbqb'); ?></td>
									<td class="tbqb_second_td"><?php echo $tbqb_options['backcolor']; ?></td>
									<td style="font-style:italic;text-align:center;"><input type="text" name="<?php echo $this->tbqb_options_name; ?>[backcolor]" id="bgcolor" value="<?php echo $tbqb_options['backcolor']; ?>"  onclick="startColorPicker(this)" onkeyup="maskedHex(this)" style="background-color:<?php echo $tbqb_options['backcolor']; ?>;" /></td>
								</tr>
								<tr>
									<td class="tbqb_first_td"><?php esc_attr_e('Border','tbqb'); ?></td>
									<td class="tbqb_second_td"><?php echo $tbqb_options['bordercolor']; ?></td>
									<td style="font-style:italic;text-align:center;"><input type="text" name="<?php echo $this->tbqb_options_name; ?>[bordercolor]" id="bordercolor" value="<?php echo $tbqb_options['bordercolor']; ?>"  onclick="startColorPicker(this)" onkeyup="maskedHex(this)" style="background-color:<?php echo $tbqb_options['bordercolor']; ?>;" /></td>
								</tr>
							</table>
						</div>
					</div>
					<div class="stylediv">
						<h3><?php _e('Options','tbqb'); ?></h3>
						<table style="border-collapse: collapse; width: 100%;">
							<tr>
								<th><?php _e('name','tbqb'); ?></th>
								<th><?php _e('status','tbqb'); ?></th>
								<th><?php _e('description','tbqb'); ?></th>
								<th><?php _e('require','tbqb'); ?></th>
							</tr>
							<tr>
								<td class="tbqb_first_td"><?php esc_attr_e( 'Quickbar','tbqb' ); ?></td>
								<td class="tbqb_second_td"><input name="<?php echo $this->tbqb_options_name; ?>[quickbar]" value="1" type="checkbox" <?php if ( $tbqb_options['quickbar'] == 1 ) echo ' checked="checked" '; ?> /></td>
								<td class="tbqb_third_td"><small><?php esc_attr_e( 'Show/Hide bottom quickbar','tbqb' ); ?> [default = enable]</small></td>
								<td></td>
							</tr>
							<tr>
								<td class="tbqb_first_td">- <?php esc_attr_e( 'Search Box','tbqb' ); ?></td>
								<td class="tbqb_second_td"><input name="<?php echo $this->tbqb_options_name; ?>[search]" value="1" type="checkbox" <?php if ( $tbqb_options['search'] == 1 ) echo ' checked="checked" '; ?> /></td>
								<td class="tbqb_third_td"><small><?php esc_attr_e( 'Show/Hide quickbar search box','tbqb' ); ?> [default = enable]</small></td>
								<td><small><?php esc_attr_e( 'Quickbar','tbqb' ); ?></small></td>
							</tr>
							<tr>
								<td class="tbqb_first_td">- <?php esc_attr_e('Date','tbqb'); ?></td>
								<td class="tbqb_second_td"><input name="<?php echo $this->tbqb_options_name; ?>[date]" value="1" type="checkbox" <?php if ( $tbqb_options['date'] == 1 ) echo ' checked="checked" '; ?> /></td>
								<td class="tbqb_third_td"><small><?php esc_attr_e( 'Show/Hide quickbar date','tbqb' ); ?> [default = enable]</small></td>
								<td><small><?php esc_attr_e( 'Quickbar','tbqb' ); ?></small></td>
							</tr>
							<tr>
								<td class="tbqb_first_td">- <?php esc_attr_e('Gravatar.','tbqb'); ?></td>
								<td class="tbqb_second_td"><input name="<?php echo $this->tbqb_options_name; ?>[gravatar]" value="1" type="checkbox" <?php if ( $tbqb_options['gravatar'] == 1 ) echo ' checked="checked" '; ?> /></td>
								<td class="tbqb_third_td"><small><?php esc_attr_e( 'Show/Hide quickbar gravatar','tbqb' ); ?> [default = enable]</small></td>
								<td><small><?php esc_attr_e( 'Quickbar','tbqb' ); ?></small></td>
							</tr>
							<tr>
								<td class="tbqb_first_td">-- <?php esc_attr_e('Gravatar Type','tbqb'); ?></td>
								<td style="width: 60px;border-right:1px solid #CCCCCC;text-align:left;" colspan="2">
									<?php
									$tbqb_gravatar = array( 'current-user' => __('Current User','tbqb') , 'fixed' => __('Fixed Gravatar Image','tbqb') );
									foreach ($tbqb_gravatar as $tbqb_gravatar_value => $tbqb_gravatar_option) {
										$tbqb_gravatar_selected = ($tbqb_gravatar_value == $tbqb_options['gravatartype']) ? ' checked="checked"' : '';
										echo <<<HERE
										<input type="radio" name="$this->tbqb_options_name[gravatartype]" title="$tbqb_gravatar_option" value="$tbqb_gravatar_value" $tbqb_gravatar_selected >$tbqb_gravatar_option &nbsp;&nbsp;
HERE;
									}
									?><br />
									<small>[default = current-user]</small>
								</td>
								<td><small><?php esc_attr_e( 'Quickbar - Gravatar','tbqb' ); ?></small></td>
							</tr>
							<tr>
								<td class="tbqb_first_td">--- <?php esc_attr_e('Gravatar Url or Email','tbqb'); ?></td>
								<td style="width: 60px;border-right:1px solid #CCCCCC;text-align:left;" colspan="2">
									<input type="text" size="60" name="<?php echo $this->tbqb_options_name; ?>[gravatardatas]" value="<?php echo esc_attr($tbqb_options['gravatardatas']); ?>" /><br />
									<small>[default = tb-quickbar/images/user.png]</small>
								</td>
								<td><small><?php esc_attr_e( 'Quickbar - Gravatar - Fixed','tbqb' ); ?></small></td>
							</tr>
							<tr>
								<td class="tbqb_first_td">- <?php esc_attr_e('Meta Menu','tbqb'); ?></td>
								<td class="tbqb_second_td"><input name="<?php echo $this->tbqb_options_name; ?>[meta]" value="1" type="checkbox" <?php if ( $tbqb_options['meta'] == 1 ) echo ' checked="checked" '; ?> /></td>
								<td class="tbqb_third_td"><small><?php esc_attr_e( 'Show/Hide quickbar meta','tbqb' ); ?> [default = enable]</small></td>
								<td><small><?php esc_attr_e( 'Quickbar','tbqb' ); ?></small></td>
							</tr>
							<tr>
								<td class="tbqb_first_td">-- <?php esc_attr_e('Welcome Message','tbqb'); ?></td>
								<td class="tbqb_second_td"><input name="<?php echo $this->tbqb_options_name; ?>[welcome]" value="1" type="checkbox" <?php if ( $tbqb_options['welcome'] == 1 ) echo ' checked="checked" '; ?> /></td>
								<td class="tbqb_third_td"><small><?php esc_attr_e( 'Show/Hide quickbar welcome message','tbqb' ); ?> [default = enable]</small></td>
								<td><small><?php esc_attr_e( 'Quickbar - Meta','tbqb' ); ?></small></td>
							</tr>
							<tr>
								<td class="tbqb_first_td">--- <?php esc_attr_e('Welcome User Message Text','tbqb'); ?></td>
								<td style="width: 60px;border-right:1px solid #CCCCCC;text-align:left;" colspan="2">
									<input type="text" size="60" maxlength="20" name="<?php echo $this->tbqb_options_name; ?>[welcometxt]" value="<?php echo esc_attr($tbqb_options['welcometxt']); ?>" /><br />
									<small>[default = Welcome, max 20 characters]</small>
								</td>
								<td><small><?php esc_attr_e( 'Quickbar - Meta - Welcome Message','tbqb' ); ?></small></td>
							</tr>
							<tr>
								<td class="tbqb_first_td">---- <?php esc_attr_e('Show Username','tbqb'); ?></td>
								<td class="tbqb_second_td"><input name="<?php echo $this->tbqb_options_name; ?>[username]" value="1" type="checkbox" <?php if ( $tbqb_options['username'] == 1 ) echo ' checked="checked" '; ?> /></td>
								<td class="tbqb_third_td"><small><?php esc_attr_e( 'Show/Hide quickbar username','tbqb' ); ?> [default = enable]</small></td>
								<td><small><?php esc_attr_e( 'Quickbar - Meta - Welcome Message','tbqb' ); ?></small></td>
							</tr>
							<tr>
								<td class="tbqb_first_td"><?php esc_attr_e('Use Easy Navigation','tbqb'); ?></td>
								<td class="tbqb_second_td"><input name="<?php echo $this->tbqb_options_name; ?>[easynavi]" value="1" type="checkbox" <?php if ( $tbqb_options['easynavi'] == 1 ) echo ' checked="checked" '; ?> /></td>
								<td class="tbqb_third_td"><small><?php esc_attr_e('Hide/Show advance navigation tool on the right of the screen','tbqb'); ?> [default = enable]</small></td>
								<td></td>
							</tr>
							<tr>
								<td class="tbqb_first_td"><?php esc_attr_e('Use Pop-up Menu Animations','tbqb'); ?></td>
								<td class="tbqb_second_td"><input name="<?php echo $this->tbqb_options_name; ?>[jsani]" value="1" type="checkbox" <?php if ( $tbqb_options['jsani'] == 1 ) echo ' checked="checked" '; ?> /></td>
								<td class="tbqb_third_td"><small><?php esc_attr_e('Try disable animations if you encountered problems with javascript','tbqb'); ?> [default = enable]</small></td>
								<td></td>
							</tr>
						</table>
					</div>
					<p style="float: left; clear: both;">
						<input class="button" type="submit" name="Submit" value="<?php esc_attr_e('Update Options','tbqb'); ?>" />
						<a style="font-size: 10px; text-decoration: none; margin-left: 10px; cursor: pointer;" href="<?php esc_attr('plugins.php?page=tb-quickbar-settings'); ?>" target="_self"><?php esc_attr_e('Undo Changes','tbqb'); ?></a>
					</p>
				</form>
				<div class="stylediv" style="clear: both;">
					<p style="margin: 10px; text-align: center; ">
						<?php _e( 'If you like/dislike this plugin, or if you encounter any issues using it, please let us know it.', 'tbqb' ); ?><br />
						<a href="<?php esc_url( 'http://www.twobeers.net/annunci/quickbar-plugin' ); ?>" title="Quickbar plugin" target="_blank"><?php _e( 'Leave a feedback', 'tbqb' ); ?></a>
					</p>
				</div>
			</div>
			<?php
		}


	}	// end TBQuickBarPlugin class

	// initialize TBQuickBarPlugin class
	global $tb_quickbar;
	$tb_quickbar = new TBQuickBarPlugin();

}	// end class check
?>