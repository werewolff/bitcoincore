<?php

class Bitcoincore
{
 public function init(){

 }

 public function plugin_activation(){
     global $wpdb;

     $table_name_categories = $wpdb->prefix . BTCPLG_TBL_CATEGORIES;
     $table_name_versions = $wpdb->prefix . BTCPLG_TBL_VERSIONS;
     $table_name_methods = $wpdb->prefix . BTCPLG_TBL_METHODS;
     $table_name_methods_versions = $wpdb->prefix . BTCPLG_TBL_METHODS_VERSIONS;
     $table_name_posts = $wpdb->posts;
     $charset_collate = $wpdb->get_charset_collate();
     if ($wpdb->get_var("show tables like '$table_name_methods'") != $table_name_methods) {

         $sql = "CREATE TABLE $table_name_categories (
	  id int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	  name varchar(50) NOT NULL,
	  PRIMARY KEY (ID, NAME)
	) $charset_collate; ";
         //
         $sql .= "CREATE TABLE $table_name_versions (
	  id int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	  name varchar(20) NOT NULL,
	  page_id bigint(20) UNSIGNED NOT NULL,
	  PRIMARY KEY (ID, NAME),
	  FOREIGN KEY (page_id) REFERENCES $table_name_posts (ID) ON DELETE CASCADE ON UPDATE CASCADE
	) $charset_collate; ";
         //
         $sql .= "CREATE TABLE $table_name_methods (
	  id int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	  name varchar(20) NOT NULL,
	  PRIMARY KEY (ID, NAME)
	) $charset_collate; ";
         //
         $sql .= "CREATE TABLE $table_name_methods_versions (
        method_id int(10) UNSIGNED NOT NULL,
        version_id int(10) UNSIGNED NOT NULL,
        category_id int(10) UNSIGNED NOT NULL,
        page_id bigint(20) UNSIGNED NOT NULL,
	  FOREIGN KEY (method_id) REFERENCES $table_name_methods (id) ON DELETE CASCADE ON UPDATE CASCADE,
	  FOREIGN KEY (version_id) REFERENCES $table_name_versions (id) ON DELETE CASCADE ON UPDATE CASCADE,
	  FOREIGN KEY (category_id) REFERENCES $table_name_categories (id) ON DELETE CASCADE ON UPDATE CASCADE,
	  FOREIGN KEY (page_id) REFERENCES $table_name_posts (ID) ON DELETE CASCADE ON UPDATE CASCADE
	) $charset_collate;";

         require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
         dbDelta($sql);

     }
 }
}