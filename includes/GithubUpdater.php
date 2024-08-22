<?php
namespace JasonTheAdams\BBCustomAttributes;
/**
 * Github updater
 *
 * @example Github release body:
 * 	Tested: 6.3
 *	Icons: 1x|https://domainname.com/icon-256x256.png?rev=2818463,2x|https://domainname.com/icon-256x256.png?rev=2818463
 *  Banners: 1x|https://domainname.com/banner-720x250.png
 *	RequiresPHP: 7.0
 *
 *	|||
 *	Add your changes here
 *
 */
class GithubUpdater {

	private $file;
	private $plugin;
	private $basename;
	private $active;
	private $username;
	private $repository;
	private $authorize_token;
	private $github_response;

	private $plugin_settings;

	/**
	 * __constructor for the class
	 * @param [type] $file [description]
	 */
	public function __construct( $file ) {
		$this->file = $file;
		add_action( 'admin_init', array( $this, 'set_plugin_properties' ) );
		return $this;
	}

	/**
	 * [set_plugin_properties description]
	 */
	public function set_plugin_properties() {
		$this->plugin	= get_plugin_data( $this->file );
		$this->basename = plugin_basename( $this->file );
		$this->active	= is_plugin_active( $this->basename );
	}

	/**
	 * [set_username description]
	 * @param [type] $username [description]
	 */
	public function set_username( $username ) {
		$this->username = $username;
	}

	/**
	 * [set_settings description]
	 * @param [type] $settings [description]
	 */
	public function set_settings( $settings ) {

		// set some defaults in case someone forgets to set these
		$defaults = array(
			'requires'			=> '5.4',
			'tested'			=> '6.3',
			'rating'			=> '100.0',
			'num_ratings'			=> '10',
			'downloaded'			=> '10',
			'added'				=> '2023-08-20',
			'banners'			=> false,
		);

		$settings = wp_parse_args( $settings , $defaults );

		$this->plugin_settings = $settings;
	}

	/**
	 * [set_repository description]
	 * @param [type] $repository [description]
	 */
	public function set_repository( $repository ) {
		$this->repository = $repository;
	}

	/**
	 * [authorize description]
	 * @param  [type] $token [description]
	 * @return [type]        [description]
	 */
	public function authorize( $token ) {
		$this->authorize_token = $token;
	}

	/**
	 * [get_repository_info description]
	 * @return [type] [description]
	 */
	private function get_repository_info() {

		// Do we have a response?
	    if ( is_null( $this->github_response ) ) {
	    	// Build URI
	        $request_uri = sprintf( 'https://api.github.com/repos/%s/%s/releases', $this->username, $this->repository );

	        // Is there an access token?
	        if( $this->authorize_token ) {
	        	// Append it
	            $request_uri = add_query_arg( 'access_token', $this->authorize_token, $request_uri );
	        }

	        // Get JSON and parse it
	        $response = json_decode( wp_remote_retrieve_body( wp_remote_get( $request_uri ) ), true );

	        // If it is an array
	        if( is_array( $response ) ) {
	        	// Get the first item
	            $response = current( $response );
	        }
	        // Is there an access token?
	        if( $this->authorize_token ) {
	        	// Update our zip url with token
	            $response['zipball_url'] = add_query_arg( 'access_token', $this->authorize_token, $response['zipball_url'] );
	        }

			// try to get metadata from the release body
			$metadata = $this->get_tmpfile_data( $response['body']);

			// merge the data with the response
			$response = array_merge( $response, $metadata);

	        // Set it to our property
	        $this->github_response = $response;
	        return $response;
	    }

	}

	/**
	 * [initialize description]
	 * @return [type] [description]
	 */
	public function initialize() {
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'modify_transient' ), 10, 1 );
		add_filter( 'plugins_api', array( $this, 'plugin_popup' ), 10, 3);
		add_filter( 'upgrader_post_install', array( $this, 'after_install' ), 10, 3 );
	}

	/**
	 * [modify_transient description]
	 * @param  [type] $transient [description]
	 * @return [type]            [description]
	 */
	public function modify_transient( $transient ) {

		// Check if transient has a checked property
		if ( property_exists( $transient, 'checked') ) {

		 	// Did Wordpress check for updates?
			if ( $checked = $transient->checked ) {

				// return early if our plugin hasn't been checked
				if( !isset( $checked[ $this->basename ] ) ) return $transient;

				// Get the repo info
				$this->get_repository_info();

				// Check if we're out of date
				$out_of_date = version_compare( $this->github_response['tag_name'], $checked[ $this->basename ], 'gt' );
				if( $out_of_date ) {

					// Get the ZIP
					$new_files = $this->github_response['zipball_url'];

					// Create valid slug
					$slug = current( explode('/', $this->basename ) );

					// setup our plugin info
					$plugin = array(
						'url' => $this->plugin["PluginURI"],
						'slug' => $slug,
						'package' => $new_files,
						'tested' => $this->github_response['tested'],
						'icons' => $this->github_response['icons'],
						'banners' => $this->github_response['banners'],
						'banners_rtl' => [],
						'requires_php' => $this->github_response['requires_php'],
						'new_version' => $this->github_response['tag_name'],
					);

					// Return it in response
					$transient->response[$this->basename] = (object) $plugin;
				}
			}
		}
		
		// Return filtered transient
		return $transient;
	}

	/**
	 * get_tmpfile_data
	 * 
	 * takes a string, creates a temp file and tries to get meta data from the tmp file
	 * since I couldn't find a function that does what I wanted
	 *
	 * @param  mixed $string
	 * @return void
	 */
	private function get_tmpfile_data( $string ) {


		// create a wp temp file in the 
		$temp_file = wp_tempnam();
		$temp = fopen($temp_file, 'r+');
		
		// make sure to also delete the file when done or even when scripts fail
		register_shutdown_function( function() use( $temp_file ) {
			@unlink( $temp_file );
		} );		

		$tmpfilename = stream_get_meta_data($temp)['uri'];
		fwrite( $temp, $string);

        $file_headers = \get_file_data( 
            $tmpfilename,
            [
                'tested' => 'Tested',
				'icons' => 'Icons',
				'banners' => 'Banners',
				'requires_php' => 'RequiresPHP',
            ]
        );

		$icons = $file_headers[ 'icons' ] ? array_map( 'trim', explode(',', $file_headers[ 'icons' ] ) ) : false;
		$banners = $file_headers[ 'banners' ] ? array_map( 'trim', explode(',', $file_headers[ 'banners' ] ) ) : false;

		$username = $this->username;
		$repository = $this->repository;

		// decompose the icons, if provided
		if (is_array($icons)) {
			$icons = array_reduce( $icons, function ($acc , $item) use ($username,$repository) { 
				$ex_item = explode('|', $item);
				$acc[$ex_item[0]] = sprintf("https://github.com/%s/%s" , $username, $repository ) . $ex_item[1];
				return $acc;
			} , []);
		}

		// decompose the banners, if provided
		if (is_array($banners)) {
			$banners = array_reduce( $banners, function ($acc , $item) use ($username,$repository) { 
				$ex_item = explode('|', $item);
				$acc[$ex_item[0]] = sprintf("https://github.com/%s/%s" , $username, $repository ) . $ex_item[1];
				return $acc;
			} , []);
		}

		// try to find the update_description delimiter
		$update_description = explode( '|||' , $string );

		$updates = ( sizeof($update_description) == 2 ) ? $update_description[1] : '';

		$data = [
            'tested' => $file_headers[ 'tested' ],
            'requires_php' => $file_headers[ 'requires_php' ],
            'icons' => $icons,
            'banners' => $banners,
			'updates' => $updates,
        ];

		// the register_shutdown function will also make sure the temp-file
		// gets deleted whenever something fails

        return $data;




	}

	/**
	 * [plugin_popup description]
	 * @param  [type] $result [description]
	 * @param  [type] $action [description]
	 * @param  [type] $args   [description]
	 * @return [type]         [description]
	 */
	public function plugin_popup( $result, $action, $args ) {

		// If there is a slug
		if( ! empty( $args->slug ) ) {

			// And it's our slug
			if( $args->slug == current( explode( '/' , $this->basename ) ) ) {

				// Get our repo info
				$this->get_repository_info();
				// Set it to an array
				$plugin = array(
					'name'				=> $this->plugin["Name"],
					'slug'				=> $this->basename,
					'version'			=> $this->github_response['tag_name'],
					'author'			=> $this->plugin["AuthorName"],
					'author_profile'	=> $this->plugin["AuthorURI"],
					'last_updated'		=> $this->github_response['published_at'],
					'homepage'			=> $this->plugin["PluginURI"],
					'short_description' => $this->plugin["Description"],
					'sections'			=> array(
						'Description'	=> $this->plugin["Description"],
						'Updates'		=> $this->github_response['updates'],
					),
					'banners'			=> $this->github_response[ 'banners' ],
					'download_link'		=> $this->github_response['zipball_url']
				);

				// merge with other settings that can be set
				$plugin = wp_parse_args( $plugin, $this->plugin_settings );

				// Return the data
				return (object) $plugin;
			}
		}
		// Otherwise return default
		return $result;
	}

	/**
	 * [after_install description]
	 * @param  [type] $response   [description]
	 * @param  [type] $hook_extra [description]
	 * @param  [type] $result     [description]
	 * @return [type]             [description]
	 */
	public function after_install( $response, $hook_extra, $result ) {

		// Get global FS object
		global $wp_filesystem;

		// Our plugin directory
		$install_directory = plugin_dir_path( $this->file );

		// Move files to the plugin dir
		$wp_filesystem->move( $result['destination'], $install_directory );

		// Set the destination for the rest of the stack
		$result['destination'] = $install_directory;

		// If it was active
		if ( $this->active ) {

			// Reactivate
			activate_plugin( $this->basename );
		}

		return $result;
	}
}