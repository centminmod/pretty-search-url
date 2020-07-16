<?php
/*
Plugin name: Pretty Search Url
Description: Redirect search to pretty /search/ urls
Author: George Liu
Author URI: https://github.com/centminmod/pretty-search-url
Version: 0.1
Released under the GNU General Public License (GPL)
http://www.gnu.org/licenses/gpl.txt
*/

function wpb_change_search_url() {
  if ( is_search() && ! empty( $_GET['s'] ) ) {
    wp_redirect( home_url( "/search/" ) . urlencode( get_query_var( 's' ) ) . "/");
    exit();
  }
}
add_action( 'template_redirect', 'wpb_change_search_url' );