<?php
/**
 * Envato API
 *
 * Wrapper class for the Envato Marketplace API.
 *
 * This is a fork of the wrapper originally developed by Derek Herman for the
 * Envato WordPress Toolkit project.
 *
 * @package     WordPress
 * @subpackage  Envato API
 * @author      Japh <japh@satellitedev.com>
 * @version     1.0
 * @since       1.0
 */

if ( ! class_exists( 'Envato_API' ) ) :

class Envato_API {
  /**
   * The buyer's Username
   *
   * @var       string
   *
   * @access    public
   * @since     1.0
   */
  public $user_name;

  /**
   * The buyer's API Key
   *
   * @var       string
   *
   * @access    public
   * @since     1.0
   */
  public $api_key;

  /**
   * The default API URL
   *
   * @var       string
   *
   * @access    private
   * @since     1.0
   */
  protected $api_url = 'http://marketplace.envato.com/api/edge/set.json';

  /**
   * Error messages
   *
   * @var       array
   *
   * @access    public
   * @since     1.0
   */
  public $errors = array( 'errors' => '' );

  /**
   * Class contructor method
   *
   * @param     string      The buyer's Username
   * @param     string      The buyer's API Key can be accessed on the marketplaces via My Account -> My Settings -> API Key
   * @return    void        Sets error messages if any.
   *
   * @access    public
   * @since     1.0
   */
  public function __construct( $user_name = '', $api_key = '' ) {

    $this->user_name  = $user_name;
    $this->api_key    = $api_key;

  }

  /**
   * Get public data.
   *
   * @param     string      Available sets: 'active-threads', 'number-of-files', 'forum_posts', 'releases', 'thread-status', 'total-users', 'item-prices', 'user', 'user-items-by-site', 'search', 'popular', 'item', 'collection', 'features', 'new-files', 'new-files-from-user', 'random-new-files'
   * @param     string      Additional set data such as marketplace name or item id.
   * @param     bool        Allow API calls to be cached. Default false.
   * @param     int         Set transient timeout. Default 300 seconds (5 minutes).
   * @return    array       An array of values (possibly cached) from the requested set, or an error message.
   *
   * @access    public
   * @since     1.0
   */
  public function public_set( $set = '', $set_data = '', $allow_cache = true, $timeout = 3600 ) {

    if ( $set == '' ) {
      $this->set_error( 'set', __( 'The API "set" is a required parameter.', 'envato' ) );
    }

    $url = preg_replace( '/set/i', $set . ':' . $set_data, $this->api_url );

    /* set transient ID for later */
    $transient = 'public_' . $set . $set_data;

    if ( $allow_cache ) {
      $cache_results = $this->set_cache( $transient, $url, $timeout );
      $results = $cache_results;
    } else {
      $results = $this->remote_request( $url );
    }

    if ( isset( $results->error ) ) {
      $this->set_error( 'error_' . $set, $results->error );
    }

    if ( $errors = $this->api_errors() ) {
      $this->clear_cache( $transient );
      return $errors;
    }

    if ( isset( $results->$set ) ) {
      return $results->$set;
    }

    return false;

  }

  /**
   * Retrieve the recently active threads for a specific marketplace forum.
   *
   * @param     string      $site The name of the marketplace to retrieve from.
   * @return    object      Details for the active threads.
   *
   * @access    public
   * @since     1.0
   */
  public function active_threads( $site = '', $allow_cache = true, $timeout = 180 ) {

    return $this->public_set( 'active-threads', $site, $allow_cache, $timeout );

  }

  /**
   * Retrieve the number of files in major categories for a specific marketplace.
   *
   * @param     string      $site The name of the marketplace to retrieve from.
   * @return    object      Details for the number of files.
   *
   * @access    public
   * @since     1.0
   */
  public function number_of_files( $site = '', $allow_cache = true, $timeout = 3600 ) {

    return $this->public_set( 'number-of-files', $site, $allow_cache, $timeout );

  }

  /**
   * Retrieve the latest forum posts for a specific user.
   *
   * @param     string      $user_name The name of the user to retrieve from.
   * @return    object      Details for the latest forum posts
   *
   * @access    public
   * @since     1.0
   */
  public function forum_posts( $user_name = '', $allow_cache = true, $timeout = 180 ) {

    if ( empty( $user_name ) ) {
        $user_name = $this->user_name;
    }

    return $this->public_set( 'forum_posts', $user_name, $allow_cache, $timeout );

  }

  /**
   * Retrieve the API release information.
   *
   * @return    object      Details for the API releases
   *
   * @access    public
   * @since     1.0
   */
  public function releases( $allow_cache = true, $timeout = 300 ) {

    return $this->public_set( 'releases', '', $allow_cache, $timeout );

  }

  /**
   * Retrieve the details for a specific forum thread.
   *
   * @param     string      $thread_id The ID of the thread to retrieve from.
   * @return    object      Details for the forum thread
   *
   * @access    public
   * @since     1.0
   */
  public function thread_status( $thread_id = '', $allow_cache = true, $timeout = 180 ) {

    return $this->public_set( 'thread-status', $thread_id, $allow_cache, $timeout );

  }

  /**
   * Retrieve the total number of users for the Envato marketplaces.
   *
   * @return    object      Details of the total number of marketplace users
   *
   * @access    public
   * @since     1.0
   */
  public function total_users( $allow_cache = true, $timeout = 3600 ) {

    return $this->public_set( 'total-users', '', $allow_cache, $timeout );

  }

  /**
   * Retrieve the price and licensing details for a specific marketplace item.
   * (Does not show whether the item is 100% GPL or not)
   *
   * @param     string      $item_id The id of the item you need information for.
   * @return    object      Details for the given item.
   *
   * @access    public
   * @since     1.0
   */
  public function item_prices( $item_id = '', $allow_cache = true, $timeout = 3600 ) {

    return $this->public_set( 'item-prices', $item_id, $allow_cache, $timeout );

  }

  /**
   * Retrieve information for a specific user.
   *
   * @param     string      $user_name The name of the user to retrieve from.
   * @return    object      Details for user
   *
   * @access    public
   * @since     1.0
   */
  public function user( $user_name = '', $allow_cache = true, $timeout = 180 ) {

    if ( empty( $user_name ) ) {
        $user_name = $this->user_name;
    }

    return $this->public_set( 'user', $user_name, $allow_cache, $timeout );

  }

  /**
   * Retrieve the number of items a specific user has for sale on each marketplace.
   *
   * @param     string      $user_name The name of the user to retrieve from.
   * @return    object      Details for the users items for sale
   *
   * @access    public
   * @since     1.0
   */
  public function user_items_by_site( $user_name = '', $allow_cache = true, $timeout = 180 ) {

    if ( empty( $user_name ) ) {
        $user_name = $this->user_name;
    }

    return $this->public_set( 'user-items-by-site', $user_name, $allow_cache, $timeout );

  }

  /**
   * Retrieve the details for a specific marketplace item.
   *
   * @param     string      $item_id The id of the item you need information for.
   * @return    object      Details for the given item.
   *
   * @access    public
   * @since     1.0
   */
  public function item( $item_id = '', $allow_cache = true, $timeout = 3600 ) {

    return $this->public_set( 'item', $item_id, $allow_cache, $timeout );

  }

  /**
   * Set the user name for private set method requests.
   *
   * @param     string      The user name.
   * @return    void
   *
   * @access    public
   * @since     1.0
   */
  public function set_user_name( $user_name = '' ) {

    $this->user_name = $user_name;

  }

  /**
   * Set the API key for private set method requests.
   *
   * @param     string      The API key.
   * @return    void
   *
   * @access    public
   * @since     1.0
   */
  public function set_api_key( $api_key = '' ) {

    $this->api_key = $api_key;

  }

  /**
   * Get private data.
   *
   * @param     string      Available sets: 'vitals', 'earnings-and-sales-by-month', 'statement', 'recent-sales', 'account', 'verify-purchase', 'download-purchase', 'wp-list-themes', 'wp-download'
   * @param     string      The buyer/author username to test against.
   * @param     string      Additional set data such as purchase code or item id.
   * @param     bool        Allow API calls to be cached. Default false.
   * @param     int         Set transient timeout. Default 300 seconds (5 minutes).
   * @return    array       An array of values (possibly cached) from the requested set, or an error message.
   *
   * @access    public
   * @since     1.0
   */
  public function private_set( $set = '', $user_name = '', $set_data = '', $allow_cache = false, $timeout = 300 ) {

    if ( $user_name == '' ) {
      $user_name = $this->user_name;
    }

    if ( $user_name == '' ) {
      $this->set_error( 'user_name', __( 'Please enter your Envato Marketplace Username.', 'envato' ) );
    }

    if ( $this->api_key == '' ) {
      $this->set_error( 'api_key', __( 'Please enter your Envato Marketplace API Key.', 'envato' ) );
    }

    if ( $set == '' ) {
      $this->set_error( 'set', __( 'The API "set" is a required parameter.', 'envato' ) );
    }

    $url = preg_replace( '/set/i', $user_name . '/' . $this->api_key . '/' . $set . ':' . $set_data, $this->api_url );

    /* set transient ID for later */
    $transient = $user_name . '_' . $set . $set_data;

    if ( $allow_cache ) {
      $cache_results = $this->set_cache( $transient, $url, $timeout );
      $results = $cache_results;
    } else {
      $results = $this->remote_request( $url );
    }

    if ( isset( $results->error ) ) {
      $this->set_error( 'error_' . $set, $results->error );
    }

    if ( $errors = $this->api_errors() ) {
      $this->clear_cache( $transient );
      return $errors;
    }

    if ( isset( $results->$set ) ) {
      return $results->$set;
    }

    return false;

  }

  /**
   * Used to list purchased themes.
   *
   * @param     bool        Allow API calls to be cached. Default true.
   * @param     int         Set transient timeout. Default 300 seconds (5 minutes).
   * @return    object      If user has purchased themes, returns an object containing those details.
   *
   * @access    public
   * @since     1.0
   */
  public function wp_list_themes( $allow_cache = true, $timeout = 300 ) {

    return $this->private_set( 'wp-list-themes', $this->user_name, '', $allow_cache, $timeout );

  }

  /**
   * Used to download a purchased item.
   *
   * This method does not allow caching.
   *
   * @param     string      The purchased items id
   * @return    string|bool If item purchased, returns the download URL.
   *
   * @access    public
   * @since     1.0
   */
  public function wp_download( $item_id ) {

    if ( ! isset( $item_id ) ) {
      $this->set_error( 'item_id', __( 'The Envato Marketplace "item ID" is a required parameter.', 'envato' ) );
    }

    $download = $this->private_set( 'wp-download', $this->user_name, $item_id );

    if ( $errors = $this->api_errors() ) {
      return $errors;
    } else if ( isset( $download->url ) ) {
      return $download->url;
    }

    return false;
  }

  /**
   * Set cache with the Transients API.
   *
   * @link      http://codex.wordpress.org/Transients_API
   *
   * @param     string      Transient ID.
   * @param     string      The URL of the API request.
   * @param     int         Set transient timeout. Default 300 seconds (5 minutes).
   * @return    mixed
   *
   * @access    public
   * @since     1.0
   */
  public function set_cache( $transient = '', $url = '', $timeout = 300 ) {

    if ( $transient == '' || $url == '' ) {
      return false;
    }

    /* keep the code below cleaner */
    $transient = $this->validate_transient( $transient );
    $transient_timeout = '_transient_timeout_' . $transient;

    /* set original cache before we destroy it */
    $old_cache = get_option( $transient_timeout ) < time() ? get_option( $transient ) : '';

    /* look for a cached result and return if exists */
    if ( false !== $results = get_transient( $transient ) ) {
      return $results;
    }

    /* create the cache and allow filtering before it's saved */
    if ( $results = apply_filters( 'envato_api_set_cache', $this->remote_request( $url ), $transient ) ) {
      set_transient( $transient, $results, $timeout );
      return $results;
    }

    return false;

  }

  /**
   * Clear cache with the Transients API.
   *
   * @link      http://codex.wordpress.org/Transients_API
   *
   * @param     string      Transient ID.
   * @return    void
   *
   * @access    public
   * @since     1.0
   */
  public function clear_cache( $transient = '' ) {

    delete_transient( $transient );

  }

  /**
   * Helper function to validate transient ID's.
   *
   * @param     string      The transient ID.
   * @return    string      Returns a DB safe transient ID.
   *
   * @access    public
   * @since     1.0
   */
  public function validate_transient( $id = '' ) {

    return preg_replace( '/[^A-Za-z0-9\_\-]/i', '', str_replace( ':', '_', $id ) );

  }

  /**
   * Helper function to set error messages.
   *
   * @param     string      The error array id.
   * @param     string      The error message.
   * @return    void
   *
   * @access    public
   * @since     1.0
   */
  public function set_error( $id, $error ) {

    $this->errors['errors'][$id] = $error;

  }

  /**
   * Helper function to return the set errors.
   *
   * @return    array       The errors array.
   *
   * @access    public
   * @since     1.0
   */
  public function api_errors() {

    if ( ! empty( $this->errors['errors'] ) ) {
      return $this->errors['errors'];
    }

  }

  /**
   * Helper function to query the marketplace API via wp_remote_request.
   *
   * @param     string      The url to access.
   * @return    object      The results of the wp_remote_request request.
   *
   * @access    private
   * @since     1.0
   */
  protected function remote_request( $url ) {

    if ( empty( $url ) ) {
      return false;
    }

    $request = wp_remote_request( $url );

    if ( is_wp_error( $request ) ) {
    	echo $request->get_error_message();
    	return false;
    }

    $data = json_decode( $request['body'] );

    if ( $request['response']['code'] == 200 ) {
      return $data;
    } else {
      $this->set_error( 'http_code', $request['response']['code'] );
    }

    if ( isset( $data->error ) ) {
      $this->set_error( 'api_error', $data->error );
    }

    return false;
  }

  /**
   * Helper function to print arrays to the screen ofr testing.
   *
   * @param     array       The array to print out
   * @return    string
   *
   * @access    public
   * @since     1.0
   */
  public function pretty_print( $array ) {

    echo '<pre>';
    print_r( $array );
    echo '</pre>';

  }
}

endif;
