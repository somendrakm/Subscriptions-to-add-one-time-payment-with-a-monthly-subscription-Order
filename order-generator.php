<?php
/**
 * @package Akismet
 */
/*
Plugin Name: One Time Subscription with monthly order generator 
Plugin URI: https://akismet.com/
Description: Subscriptions to add one time payment with a monthly subscription Order
Version: 1
Author: Automattic
Author URI: https://corlax.com/
*/

//shedule even for daily
if ( ! wp_next_scheduled( 'my_task_hook' ) ) {
  wp_schedule_event( time(), 'daily', 'my_task_hook' );
}

add_action( 'my_task_hook', 'my_daily_cron_job' );

//con job function for daily
function my_daily_cron_job() {
	
	//this filter all shop_subscription
    $customer_subscriptions = get_posts( array(
        'numberposts' => -1,
        'post_type'   => 'shop_subscription', // WC orders post type
        'post_status' => 'wc-active' // Only orders with status "completed"
    ) );
   
    // Iterating through each post subscription object
    foreach( $customer_subscriptions as $customer_subscription ){
   
        // IMPORTANT HERE: Getting an instance of the WC_Subscription object
        $subscription = new WC_Subscription($customer_subscription->ID);
		
		// find the billing interval and only do if its longer than a month
        $interval = $subscription->data['billing_interval'];

        // Getting the related ORDER ID 
        if($interval > 1) {
			 // find the date of subsctiption created and convert object into array
             $date_created = (array)$subscription->data['date_created'];
			//find the date of subscription end and convert object into array
             $schedule_end = (array)$subscription->data['schedule_end'];
			
			 // get the start date from array
             $subscriptiondate = $date_created['date'];
			// get the end date from array
             $endate = $schedule_end['date'];
			
			//get the subscription order id for get user details and update partent id of order
             $order_id = $subscription->data['id'];;
			//current date of today
             $todayday = date("d");
			
			//change usbscription start date into day of the month
             $dayofmonth = date("d", strtotime($subscriptiondate));
			
			// what is check is that today is the same day as a subscription day and check if the subscription is still vaild
             if($dayofmonth == $todayday & strtotime(date("Y-m-d")) <= strtotime(date("Y-m-d", strtotime($endate))) ) {
           
				 //billing user details
                $billing = array(
                    'first_name' => get_post_meta($order_id, '_billing_first_name', true),
                    'last_name'  => get_post_meta($order_id, '_billing_last_name', true),
                    'company'    => get_post_meta($order_id, '_billing_company', true),
                    'email'      => get_post_meta($order_id, '_billing_email', true),
                    'phone'      => get_post_meta($order_id, '_billing_phone', true),
                    'address_1'  => get_post_meta($order_id, '_billing_address_1', true),
                    'address_2'  => get_post_meta($order_id, '_billing_address_2', true),
                    'city'       => get_post_meta($order_id, '_billing_city', true),
                    'state'      => get_post_meta($order_id, '_billing_state', true),
                    'postcode'   => get_post_meta($order_id, '_billing_postcode', true),
                    'country'    => get_post_meta($order_id, '_billing_country', true)
                );
       
				 //shipping billing details
                $shipping = array(
                    'first_name' => get_post_meta($order_id, '_shipping_first_name', true),
                    'last_name'  => get_post_meta($order_id, '_shipping_last_name', true),
                    'company'    => get_post_meta($order_id, '_shipping_company', true),
                    'email'      => get_post_meta($order_id, '_shipping_email', true),
                    'phone'      => get_post_meta($order_id, '_shipping_phone', true),
                    'address_1'  => get_post_meta($order_id, '_shipping_address_1', true),
                    'address_2'  => get_post_meta($order_id, '_shipping_address_2', true),
                    'city'       => get_post_meta($order_id, '_shipping_city', true),
                    'state'      => get_post_meta($order_id, '_shipping_state', true),
                    'postcode'   => get_post_meta($order_id, '_shipping_postcode', true),
                    'country'    => get_post_meta($order_id, '_shipping_country', true)
                );
       
				 //this will create new order
                $order = wc_create_order();
				//this will get new order id
                $order_id_new = trim(str_replace('#', '', $order->get_order_number()));
				 
				//here is the main part add the id of the subscription product. 
				$subscription_product_id = '5257'; //5257 is a just example you can change according your need
                $order->add_product( get_product( $subscription_product_id ), 1 ); //(get_product with id and next is for quantity)
                $order->set_address( $billing, 'billing' );
                $order->set_address( $shipping, 'shipping' );
                $order->update_status('on-hold');
                $order->calculate_totals();   
				 
                global $wpdb;
                wp_update_post(
                    array(
                        'ID' => $order_id,
                        'post_parent' => $order_id_new
                    )
                );
              
			 }
             
        }
   
    }
}
