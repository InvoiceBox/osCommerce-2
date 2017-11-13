<?php
/*
	invoicebox payment module
*/
ini_set('error_reporting', E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
	class invoicebox {
		var $code, $title, $description, $enabled;
		var $params = array('unit_id' => '', 'limit_ids' => '', 'account_id' => '');

		// class constructor
		function invoicebox() {
			global $order;

			$this->code = 'invoicebox';
			$this->title = MODULE_PAYMENT_INVOICEBOX_TEXT_TITLE;
			$this->description = MODULE_PAYMENT_INVOICEBOX_TEXT_DESCRIPTION;
			$this->sort_order = MODULE_PAYMENT_INVOICEBOX_SORT_ORDER;
			$this->enabled =  true;
			$this->form_action_url = 'https://go.invoicebox.ru/module_inbox_auto.u';

			if ((int)MODULE_PAYMENT_INVOICEBOX_PREPARE_ORDER_STATUS_ID > 0) {
				$this->order_status = MODULE_PAYMENT_INVOICEBOX_PREPARE_ORDER_STATUS_ID;
			}

			if (is_object($order)) $this->update_status();
		}

		// class methods
		function update_status() {
			return false;
		}

		function javascript_validation() {
			return false;
		}
		
		function selection() {
		
			global $cart_invoicebox_Standard_ID;

			if (tep_session_is_registered('cart_invoicebox_Standard_ID'))
			{
				$order_id = substr($cart_invoicebox_Standard_ID, strpos($cart_invoicebox_Standard_ID, '-')+1);

				$check_query = tep_db_query('select orders_id from '.TABLE_ORDERS_STATUS_HISTORY.' where orders_id = "'.(int)$order_id.'" limit 1');

				if (tep_db_num_rows($check_query) < 1)
				{
					tep_db_query('delete from '.TABLE_ORDERS.' where orders_id = "'.(int)$order_id.'"');
					tep_db_query('delete from '.TABLE_ORDERS_TOTAL.' where orders_id = "'.(int)$order_id.'"');
					tep_db_query('delete from '.TABLE_ORDERS_STATUS_HISTORY.' where orders_id = "'.(int)$order_id.'"');
					tep_db_query('delete from '.TABLE_ORDERS_PRODUCTS.' where orders_id = "'.(int)$order_id.'"');
					tep_db_query('delete from '.TABLE_ORDERS_PRODUCTS_ATTRIBUTES.' where orders_id = "'.(int)$order_id.'"');
					tep_db_query('delete from '.TABLE_ORDERS_PRODUCTS_DOWNLOAD.' where orders_id = "'.(int)$order_id.'"');

					tep_session_unregister('cart_invoicebox_Standard_ID');
				}
			}
			return array('id' => $this->code, 'module' => $this->title);
		}

		function pre_confirmation_check() {
			  global $cartID, $cart;

			if ( empty($cart->cartID))
			{
				$cartID = $cart->cartID = $cart->generate_cart_id();
			}

			if (!tep_session_is_registered('cartID'))
			{
				tep_session_register('cartID');
			}

		}

		function confirmation()
    {
        global $cartID, $cart_invoicebox_Standard_ID, $customer_id, $languages_id, $order, $order_total_modules;

        if (tep_session_is_registered('cartID'))
        {
            $insert_order = false;

            if (tep_session_is_registered('cart_invoicebox_Standard_ID'))
            {
                $order_id = substr($cart_invoicebox_Standard_ID, strpos($cart_invoicebox_Standard_ID, '-')+1);

                $curr_check = tep_db_query("select currency from ".TABLE_ORDERS." where orders_id = '".(int)$order_id."'");
                $curr = tep_db_fetch_array($curr_check);

                if (($curr['currency'] != $order->info['currency']) || ($cartID != substr($cart_invoicebox_Standard_ID, 0, strlen($cartID))))
                {
                    $check_query = tep_db_query('select orders_id from '.TABLE_ORDERS_STATUS_HISTORY.' where orders_id = "'.(int)$order_id.'" limit 1');

                    if (tep_db_num_rows($check_query) < 1)
                    {
                        tep_db_query('delete from '.TABLE_ORDERS.' where orders_id = "'.(int)$order_id.'"');
                        tep_db_query('delete from '.TABLE_ORDERS_TOTAL.' where orders_id = "'.(int)$order_id.'"');
                        tep_db_query('delete from '.TABLE_ORDERS_STATUS_HISTORY.' where orders_id = "'.(int)$order_id.'"');
                        tep_db_query('delete from '.TABLE_ORDERS_PRODUCTS.' where orders_id = "'.(int)$order_id.'"');
                        tep_db_query('delete from '.TABLE_ORDERS_PRODUCTS_ATTRIBUTES.' where orders_id = "'.(int)$order_id.'"');
                        tep_db_query('delete from '.TABLE_ORDERS_PRODUCTS_DOWNLOAD.' where orders_id = "'.(int)$order_id.'"');
                    }

                    $insert_order = true;
                }
            } else
            {
                $insert_order = true;
            }

            if ($insert_order == true)
            {
                $order_totals = array ();
                if (is_array($order_total_modules->modules))
                {
                    reset($order_total_modules->modules);
                    while ( list (, $value) = each($order_total_modules->modules))
                    {
                        $class = substr($value, 0, strrpos($value, '.'));
                        if ($GLOBALS[$class]->enabled)
                        {
                            for ($i = 0, $n = sizeof($GLOBALS[$class]->output); $i < $n; $i++)
                            {
                                if (tep_not_null($GLOBALS[$class]->output[$i]['title']) && tep_not_null($GLOBALS[$class]->output[$i]['text']))
                                {
                                    $order_totals[] = array ('code'=>$GLOBALS[$class]->code,
                                    'title'=>$GLOBALS[$class]->output[$i]['title'],
                                    'text'=>$GLOBALS[$class]->output[$i]['text'],
                                    'value'=>$GLOBALS[$class]->output[$i]['value'],
                                    'sort_order'=>$GLOBALS[$class]->sort_order);
                                }
                            }
                        }
                    }
                }

                $sql_data_array = array ('customers_id'=>$customer_id,
                'customers_name'=>$order->customer['firstname'].' '.$order->customer['lastname'],
                'customers_company'=>$order->customer['company'],
                'customers_street_address'=>$order->customer['street_address'],
                'customers_suburb'=>$order->customer['suburb'],
                'customers_city'=>$order->customer['city'],
                'customers_postcode'=>$order->customer['postcode'],
                'customers_state'=>$order->customer['state'],
                'customers_country'=>$order->customer['country']['title'],
                'customers_telephone'=>$order->customer['telephone'],
                'customers_email_address'=>$order->customer['email_address'],
                'customers_address_format_id'=>$order->customer['format_id'],
                'delivery_name'=>$order->delivery['firstname'].' '.$order->delivery['lastname'],
                'delivery_company'=>$order->delivery['company'],
                'delivery_street_address'=>$order->delivery['street_address'],
                'delivery_suburb'=>$order->delivery['suburb'],
                'delivery_city'=>$order->delivery['city'],
                'delivery_postcode'=>$order->delivery['postcode'],
                'delivery_state'=>$order->delivery['state'],
                'delivery_country'=>$order->delivery['country']['title'],
                'delivery_address_format_id'=>$order->delivery['format_id'],
                'billing_name'=>$order->billing['firstname'].' '.$order->billing['lastname'],
                'billing_company'=>$order->billing['company'],
                'billing_street_address'=>$order->billing['street_address'],
                'billing_suburb'=>$order->billing['suburb'],
                'billing_city'=>$order->billing['city'],
                'billing_postcode'=>$order->billing['postcode'],
                'billing_state'=>$order->billing['state'],
                'billing_country'=>$order->billing['country']['title'],
                'billing_address_format_id'=>$order->billing['format_id'],
                'payment_method'=>$order->info['payment_method'],
                'cc_type'=>$order->info['cc_type'],
                'cc_owner'=>$order->info['cc_owner'],
                'cc_number'=>$order->info['cc_number'],
                'cc_expires'=>$order->info['cc_expires'],
                'date_purchased'=>'now()',
                'orders_status'=>$order->info['order_status'],
                'currency'=>$order->info['currency'],
                'currency_value'=>$order->info['currency_value']);

                tep_db_perform(TABLE_ORDERS, $sql_data_array);

                $insert_id = tep_db_insert_id();

                for ($i = 0, $n = sizeof($order_totals); $i < $n; $i++)
                {
                    $sql_data_array = array ('orders_id'=>$insert_id,
                    'title'=>$order_totals[$i]['title'],
                    'text'=>$order_totals[$i]['text'],
                    'value'=>$order_totals[$i]['value'],
                    'class'=>$order_totals[$i]['code'],
                    'sort_order'=>$order_totals[$i]['sort_order']);

                    tep_db_perform(TABLE_ORDERS_TOTAL, $sql_data_array);
                }

                for ($i = 0, $n = sizeof($order->products); $i < $n; $i++)
                {
                    $sql_data_array = array ('orders_id'=>$insert_id,
                    'products_id'=>tep_get_prid($order->products[$i]['id']),
                    'products_model'=>$order->products[$i]['model'],
                    'products_name'=>$order->products[$i]['name'],
                    'products_price'=>$order->products[$i]['price'],
                    'final_price'=>$order->products[$i]['final_price'],
                    'products_tax'=>$order->products[$i]['tax'],
                    'products_quantity'=>$order->products[$i]['qty']);

                    tep_db_perform(TABLE_ORDERS_PRODUCTS, $sql_data_array);

                    $order_products_id = tep_db_insert_id();

                    $attributes_exist = '0';
                    if ( isset ($order->products[$i]['attributes']))
                    {
                        $attributes_exist = '1';
                        for ($j = 0, $n2 = sizeof($order->products[$i]['attributes']); $j < $n2; $j++)
                        {
                            if (DOWNLOAD_ENABLED == 'true')
                            {
                                $attributes_query = "select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix, pad.products_attributes_maxdays, pad.products_attributes_maxcount , pad.products_attributes_filename
                                       from ".TABLE_PRODUCTS_OPTIONS." popt, ".TABLE_PRODUCTS_OPTIONS_VALUES." poval, ".TABLE_PRODUCTS_ATTRIBUTES." pa
                                       left join ".TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD." pad
                                       on pa.products_attributes_id=pad.products_attributes_id
                                       where pa.products_id = '".$order->products[$i]['id']."'
                                       and pa.options_id = '".$order->products[$i]['attributes'][$j]['option_id']."'
                                       and pa.options_id = popt.products_options_id
                                       and pa.options_values_id = '".$order->products[$i]['attributes'][$j]['value_id']."'
                                       and pa.options_values_id = poval.products_options_values_id
                                       and popt.language_id = '".$languages_id."'
                                       and poval.language_id = '".$languages_id."'";
                                $attributes = tep_db_query($attributes_query);
                            } else
                            {
                                $attributes = tep_db_query("select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix from ".TABLE_PRODUCTS_OPTIONS." popt, ".TABLE_PRODUCTS_OPTIONS_VALUES." poval, ".TABLE_PRODUCTS_ATTRIBUTES." pa where pa.products_id = '".$order->products[$i]['id']."' and pa.options_id = '".$order->products[$i]['attributes'][$j]['option_id']."' and pa.options_id = popt.products_options_id and pa.options_values_id = '".$order->products[$i]['attributes'][$j]['value_id']."' and pa.options_values_id = poval.products_options_values_id and popt.language_id = '".$languages_id."' and poval.language_id = '".$languages_id."'");
                            }
                            $attributes_values = tep_db_fetch_array($attributes);

                            $sql_data_array = array ('orders_id'=>$insert_id,
                            'orders_products_id'=>$order_products_id,
                            'products_options'=>$attributes_values['products_options_name'],
                            'products_options_values'=>$attributes_values['products_options_values_name'],
                            'options_values_price'=>$attributes_values['options_values_price'],
                            'price_prefix'=>$attributes_values['price_prefix']);

                            tep_db_perform(TABLE_ORDERS_PRODUCTS_ATTRIBUTES, $sql_data_array);

                            if ((DOWNLOAD_ENABLED == 'true') && isset ($attributes_values['products_attributes_filename']) && tep_not_null($attributes_values['products_attributes_filename']))
                            {
                                $sql_data_array = array ('orders_id'=>$insert_id,
                                'orders_products_id'=>$order_products_id,
                                'orders_products_filename'=>$attributes_values['products_attributes_filename'],
                                'download_maxdays'=>$attributes_values['products_attributes_maxdays'],
                                'download_count'=>$attributes_values['products_attributes_maxcount']);

                                tep_db_perform(TABLE_ORDERS_PRODUCTS_DOWNLOAD, $sql_data_array);
                            }
                        }
                    }
                }

                $cart_invoicebox_Standard_ID = $cartID.'-'.$insert_id;
                tep_session_register('cart_invoicebox_Standard_ID');
            }
        }

        return false;
    }

		function process_button() {
			      global $cart,$cartID,$customer_id, $order, $sendto, $currency, $cart_invoicebox_Standard_ID,$shipping, $order_total_modules;

					  $total_tax = $order->info['tax'];
					$order_id = substr($cart_invoicebox_Standard_ID, strpos($cart_invoicebox_Standard_ID, '-')+1);
				// remove shipping tax in total tax value
					  if ( isset($shipping['cost']) ) {
						$total_tax -= ($order->info['shipping_cost'] - $shipping['cost']);
					  }
					$currency='RUB';
					switch (MODULE_PAYMENT_INVOICEBOX_TEST_MODE) {
								case 'Real':
									$test_mode = '0';
									break;
								case 'Test':
									$test_mode = '1';
									break;
							}
					$signatureValue = md5(
					MODULE_PAYMENT_INVOICEBOX_SHOP_ID.
					$order_id.
					$this->format_raw($order->info['total']).
					$currency.
					MODULE_PAYMENT_INVOICEBOX_DATA_INTEGRITY_CODE
					); 
					  $process_button_string = '';
					  $parameters = array('itransfer_participant_id' => MODULE_PAYMENT_INVOICEBOX_SHOP_ID,
										  'itransfer_participant_ident' => MODULE_PAYMENT_INVOICEBOX_REGION_SHOP_ID,
										  'itransfer_order_id' => $order_id,
										  'itransfer_testmode' => $test_mode,
										  'itransfer_body_type' => "PRIVATE",
										  'itransfer_order_amount' => $this->format_raw($order->info['total']),
										  'itransfer_order_currency_ident' => $currency,
										  'itransfer_participant_sign' =>  $signatureValue,
										  'CMS' => 'OSCOMMERCE',
										  'itransfer_order_description' => 'Оплата заказа ',
										  'itransfer_person_email' => $order->customer['email_address'],
										  'itransfer_person_phone' => $order->customer['telephone'],
										  'itransfer_url_return' => tep_href_link(FILENAME_CHECKOUT_SUCCESS, '', 'NONSSL'),
										  'itransfer_url_notify' => tep_href_link('ext/modules/payment/invoicebox/callback.php', '', 'NONSSL', false, false));
										 
					 
					  if (is_numeric($sendto) && ($sendto > 0)) {
						$parameters['itransfer_person_name'] = $order->delivery['firstname'].' '.$order->delivery['lastname'];
						
					  } else {
						$parameters['itransfer_person_name'] = $order->billing['firstname'].' '.$order->billing['lastname'];
						
					  }

					  
					  $item_params = array();
						$product_quantity = 0;
					  $line_item_no = 1;

					  foreach ($order->products as $product) {
						$product_quantity += $product['qty'];
						$product_price = $this->format_raw($product['final_price'] + tep_calculate_tax($product['final_price'], $product['tax']));
						$item_params['itransfer_item' . $line_item_no.'_name'] = $product['name'];
						$item_params['itransfer_item' . $line_item_no.'_price'] = $product_price;
						$item_params['itransfer_item' . $line_item_no.'_quantity'] = $product['qty'];
						$item_params['itransfer_item'.$line_item_no.'_vatrate'] = $product['tax'];
						$item_params['itransfer_item'.$line_item_no.'_measure'] = 'шт.';

						$line_item_no++;
					  }

					 $parameters['itransfer_order_quantity'] = $product_quantity;
					if($order->info['shipping_cost']>0){
						$item_params['itransfer_item' . $line_item_no.'_name'] = $order->info['shipping_method'];
						$item_params['itransfer_item' . $line_item_no.'_price'] = $order->info['shipping_cost'];
						$item_params['itransfer_item' . $line_item_no.'_quantity'] = 1;
						$item_params['itransfer_item'.$line_item_no.'_vatrate'] = 0;
						$item_params['itransfer_item'.$line_item_no.'_measure'] = 'шт.';
					}
					  
						$parameters = array_merge($parameters, $item_params);
					 
//$parameters['test'] = '<pre>'.print_r($order,1).'</pre>';
					  
						foreach ($parameters as $key => $value) {
						  $process_button_string .= tep_draw_hidden_field($key, $value).'
						  ';
						}
					  $cart->reset(true);

        // unregister session variables used during checkout
        tep_session_unregister('sendto');
        tep_session_unregister('billto');
        tep_session_unregister('shipping');
        tep_session_unregister('payment');
        tep_session_unregister('comments');

        tep_session_unregister('cart_inpay_Standard_ID');

					  return $process_button_string;
	}

		function before_process() {
		global $customer_id, $order, $order_totals, $sendto, $billto, $languages_id, $payment, $currencies, $cart, $cart_inpay_Standard_ID;
        global $$payment;
			$cart->reset(true);

        // unregister session variables used during checkout
        tep_session_unregister('sendto');
        tep_session_unregister('billto');
        tep_session_unregister('shipping');
        tep_session_unregister('payment');
        tep_session_unregister('comments');

        tep_session_unregister('cart_inpay_Standard_ID');
		}

		function after_process() {
			return false;
		}
		
		function output_error() {
			return false;
		}

		function check() {
			
			return true;
		}

		function install() {
			$check_query = tep_db_query("select orders_status_id from " . TABLE_ORDERS_STATUS . " where orders_status_name = 'Preparing [Invoicebox]' limit 1");

			if (tep_db_num_rows($check_query) < 1) {
				$status_query = tep_db_query("select max(orders_status_id) as status_id from " . TABLE_ORDERS_STATUS);
				$status = tep_db_fetch_array($status_query);

				$status_id = $status['status_id']+1;

				$languages = tep_get_languages();

				for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
					tep_db_query("insert into " . TABLE_ORDERS_STATUS . " (orders_status_id, language_id, orders_status_name) values ('" . $status_id . "', '" . $languages[$i]['id'] . "', 'Preparing [Invoicebox]')");
				}

				$flags_query = tep_db_query("describe " . TABLE_ORDERS_STATUS . " public_flag");
				if (tep_db_num_rows($flags_query) == 1) {
					tep_db_query("update " . TABLE_ORDERS_STATUS . " set public_flag = 1 and downloads_flag = 0 where orders_status_id = '" . $status_id . "'");
				}
			} else {
				$check = tep_db_fetch_array($check_query);

				$status_id = $check['orders_status_id'];
			} 
		
			
			
			tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Shop ID', 'MODULE_PAYMENT_INVOICEBOX_SHOP_ID', '', 'Shop ID in Invoicebox system.', '6', '3', now())");
			tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Region shop ID', 'MODULE_PAYMENT_INVOICEBOX_REGION_SHOP_ID', '', 'Region shop ID in Invoicebox system.', '6', '3', now())");
			tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('API Code', 'MODULE_PAYMENT_INVOICEBOX_DATA_INTEGRITY_CODE', '', 'API Cod in Invoicebox system and registration system of the merchant', '6', '4', now())");
			tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Test Mode', 'MODULE_PAYMENT_INVOICEBOX_TEST_MODE', 'Real', 'It indicates if the request is made in a test mode', '6', '5', 'tep_cfg_select_option(array(\'Real\', \'Test\'), ', now())");
			tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Preparing Order Status', 'MODULE_PAYMENT_INVOICEBOX_PREPARE_ORDER_STATUS_ID', '0', 'Set the status of prepared orders made with this payment module to this value', '6', '7', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
			tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Acknowledged Order Status', 'MODULE_PAYMENT_INVOICEBOX_ORDER_STATUS_ID', '0', 'Set the status of orders made with this payment module to this value', '6', '8', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
			tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of display.', 'MODULE_PAYMENT_INVOICEBOX_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first', '6', '9', now())");
		}

		function remove() {
			tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
		}

		function keys() {
			return array('MODULE_PAYMENT_INVOICEBOX_REGION_SHOP_ID', 'MODULE_PAYMENT_INVOICEBOX_SHOP_ID',  'MODULE_PAYMENT_INVOICEBOX_DATA_INTEGRITY_CODE', 'MODULE_PAYMENT_INVOICEBOX_TEST_MODE', 'MODULE_PAYMENT_INVOICEBOX_PREPARE_ORDER_STATUS_ID', 'MODULE_PAYMENT_INVOICEBOX_ORDER_STATUS_ID', 'MODULE_PAYMENT_INVOICEBOX_SORT_ORDER');
		}
		
		function format_raw($number, $currency_code = '', $currency_value = '') {
		  global $currencies, $currency;

		  if (empty($currency_code) || !$this->is_set($currency_code)) {
			$currency_code = $currency;
		  }

		  if (empty($currency_value) || !is_numeric($currency_value)) {
			$currency_value = $currencies->currencies[$currency_code]['value'];
		  }

		  return number_format(tep_round($number * $currency_value, $currencies->currencies[$currency_code]['decimal_places']), $currencies->currencies[$currency_code]['decimal_places'], '.', '');
		}
	}
?>
