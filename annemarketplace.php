<?php
/*
Plugin Name: Anne Marketplace
Plugin URI: http://127.0.0.1/anne/
Description: Plugin para Marketplace
Version: 1.0
Author: Diego Antoniança    
Author URI: http://127.0.0.1/anne/
Text Domain: annemarketplace
License: GPL2
*/

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'plugins_loaded', 'annemarketplace_plugin_init' );

function annemarketplace_plugin_init() {

    register_activation_hook( __FILE__, 'annemarketplace_activation');
    register_deactivation_hook( __FILE__, 'annemarketplace_deactivation');
    register_uninstall_hook(__FILE__, 'annemarketplace_uninstall');
  
    //INSERÇÃO DE CSS
    annemarketplace_add_css_admin();

    //INSERÇÃO DE JS
    annemarketplace_add_js_admin();

    //INSERÇÃO DO JS - MEDIA UPLOAD
    add_action( 'admin_enqueue_scripts', 'annemarketplace_add_js_media' );

    //CRIAÇÃO DO MENU
    add_action( 'admin_menu', 'annemarketplace_menu' );

    //INCLUDES



    include 'includes/class-wp-annemarketplace-variables.php';
    include 'includes/class-wp-annemarketplace-dashboard.php';
    include 'includes/class-wp-annemarketplace-options.php';
    include 'includes/class-wp-annemarketplace-product.php';

}


function annemarketplace_add_css_admin(){
    wp_enqueue_style('Style_CSS', plugin_dir_url(__FILE__). 'admin/css/style_marketplace.css');
    wp_enqueue_style('Bootstrap_Min_CSS', plugin_dir_url(__FILE__). 'admin/css/bootstrap.min.css');
    wp_enqueue_style( 'Select2_Min_CSS', plugin_dir_url(__FILE__). 'admin/css/select2.min.css');
}


function annemarketplace_add_js_admin(){
    wp_enqueue_script( 'Bootstrap_Bundle_Min_JS', plugin_dir_url(__FILE__) .'admin/js/bootstrap.bundle.min.js', array( 'jquery' ) );
    wp_enqueue_script( 'Select2_Min_JS', plugin_dir_url(__FILE__) .'admin/js/select2.min.js', array(), '1.0.1', true );
    wp_enqueue_script( 'Scripts_JS', plugin_dir_url(__FILE__) .'admin/js/scripts.js', array( 'jquery' ) );

}


function annemarketplace_add_js_media() {
    if ( ! did_action( 'wp_enqueue_media' ) ) {
        wp_enqueue_media();
    }  
}


function annemarketplace_menu(){
    add_menu_page('Anne Marketplace', 'Anne Marketplace', 'manage_options', 'annemarketplace', 'Dashboard::layout', 'dashicons-palmtree', 10);
}


function annemarketplace_activation(){}


function annemarketplace_deactivation(){}


function annemarketplace_uninstall(){}


function annemarketplace_code_head(){}
 

function annemarketplace_code_body(){}