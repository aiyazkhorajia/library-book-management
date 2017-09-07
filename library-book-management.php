<?php
/*
 * Plugin Name:       Library Book Management
 * Plugin URI:        http://github.com/aiyaz/library_management
 * Description:       Single Post Meta Manager displays the post meta data associated with a given post.
 * Version:           1.0.0
 * Author:            Aiyaz
 * Author URI:        http://ayaz.co.nf
 * Text Domain:       library-manager-locale
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 */
 
if ( ! defined( 'WPINC' ) ) {
    die;
}

include_once('includes/library-book-management-class.php');

$ExportPost = new libspace\LibraryManagement();