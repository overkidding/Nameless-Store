<?php
/*
 *	Made by Partydragen
 *  https://partydragen.com/resources/resource/5-store-module/
 *  https://partydragen.com/
 *
 *  License: MIT
 *
 *  Store module - panel products page
 */

// Can the user view the StaffCP?
if(!$user->handlePanelPageLoad('staffcp.store.products')) {
    require_once(ROOT_PATH . '/403.php');
    die();
}

define('PAGE', 'panel');
define('PARENT_PAGE', 'store');
define('PANEL_PAGE', 'store_products');
$page_title = $store_language->get('general', 'products');
require_once(ROOT_PATH . '/core/templates/backend_init.php');
require_once(ROOT_PATH . '/modules/Store/classes/Store.php');

$store = new Store($cache, $store_language);

if(!isset($_GET['action'])) {
	// Get all products and categories
	$categories = DB::getInstance()->query('SELECT * FROM nl2_store_categories WHERE deleted = 0 ORDER BY `order` ASC', array());
	$all_categories = [];

	if($categories->count()){
		$categories = $categories->results();
		
		$currency = $queries->getWhere('store_settings', array('name', '=', 'currency_symbol'));
		if(count($currency))
			$currency = Output::getPurified($currency[0]->value);
		else
			$currency = '';

		foreach($categories as $category){
			$new_category = array(
				'name' => Output::getClean(Output::getDecoded($category->name)),
				'products' => array(),
				'edit_link' => URL::build('/panel/store/categories/', 'action=edit&id=' . Output::getClean($category->id)),
				'delete_link' => URL::build('/panel/store/categories/', 'action=delete&id=' . Output::getClean($category->id))
			);

			$products = DB::getInstance()->query('SELECT * FROM nl2_store_products WHERE category_id = ? AND deleted = 0 ORDER BY `order` ASC', array(Output::getClean($category->id)));

			if($products->count()){
				$products = $products->results();

				foreach($products as $product){
					$new_product = array(
						'id' => Output::getClean($product->id),
						'id_x' => str_replace('{x}', Output::getClean($product->id), $store_language->get('admin', 'id_x')),
						'name' => Output::getClean($product->name),
						'price' => $currency . Output::getClean($product->price) . ' USD',
						'edit_link' => URL::build('/panel/store/products/', 'action=edit&id=' . Output::getClean($product->id)),
						'delete_link' => URL::build('/panel/store/products/', 'action=delete&id=' . Output::getClean($product->id))
					);

					$new_category['products'][] = $new_product;
				}
			}

			$all_categories[] = $new_category;
		}
		
	} else {
		$smarty->assign('NO_PRODUCTS', $store_language->get('general', 'no_products'));
	}

	$smarty->assign(array(
		'ALL_CATEGORIES' => $all_categories,
		'CURRENCY' => $currency,
		'NEW_CATEGORY' => $store_language->get('admin', 'new_category'),
        'NEW_CATEGORY_LINK' => URL::build('/panel/store/categories/', 'action=new'),
		'NEW_PRODUCT' => $store_language->get('admin', 'new_product'),
        'NEW_PRODUCT_LINK' => URL::build('/panel/store/products/', 'action=new'),
		'ARE_YOU_SURE' => $language->get('general', 'are_you_sure'),
		'CONFIRM_DELETE_CATEGORY' => $store_language->get('admin', 'category_confirm_delete'),
		'CONFIRM_DELETE_PRODUCT' => $store_language->get('admin', 'product_confirm_delete'),
		'YES' => $language->get('general', 'yes'),
		'NO' => $language->get('general', 'no'),
	));
	
	$template->addJSFiles(array(
		(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/js/jquery-ui.min.js' => array()
	));

	$template_file = 'store/products.tpl';
} else {
	switch($_GET['action']) {
		case 'new';
			// Create new product
			if(Input::exists()){
				$errors = array();
				if(Token::check(Input::get('token'))){
					$validate = new Validate();
					$validation = $validate->check($_POST, array(
						'name' => array(
							'required' => true,
							'min' => 1,
							'max' => 128
						),
						'description' => array(
							'max' => 100000
						)
					));
					
					if ($validation->passed()){
						// Validate if category exist
						$category = DB::getInstance()->query('SELECT id FROM nl2_store_categories WHERE id = ?', array(Input::get('category')))->results();
						if(!count($category)) {
							$errors[] = $store_language->get('admin', 'invalid_category');
						}
						
						// Get price
						if(!isset($_POST['price']) || !is_numeric($_POST['price']) || $_POST['price'] < 0.01 || $_POST['price'] > 1000 || !preg_match('/^\d+(?:\.\d{2})?$/', $_POST['price'])){
							$errors[] = $store_language->get('admin', 'invalid_price');
						} else {
							$price = number_format($_POST['price'], 2, '.', '');
						}

						// insert into database if there is no errors
						if(!count($errors)) {
							// Get last order
							$last_order = DB::getInstance()->query('SELECT * FROM nl2_store_products ORDER BY `order` DESC LIMIT 1')->results();
							if(count($last_order)) $last_order = $last_order[0]->order;
							else $last_order = 0;

							// Save to database
							$queries->create('store_products', array(
								'name' => Output::getClean(Input::get('name')),
								'description' => Output::getClean(Input::get('description')),
								'category_id' => $category[0]->id,
								'price' => $price,
								'order' => $last_order + 1,
							));
							
							$lastId = $queries->getLastId();
							
							Session::flash('products_success', $store_language->get('admin', 'product_created_successfully'));
							Redirect::to(URL::build('/panel/store/products/', 'action=edit&id=' . $lastId));
							die();
						}
					} else {
						$errors[] = $store_language->get('admin', 'description_max_100000');
					}
				} else {
					// Invalid token
					$errors[] = $language->get('general', 'invalid_token');
				}
			}
			
			$smarty->assign(array(
				'NEW_PRODUCT' => $store_language->get('admin', 'new_product'),
				'BACK' => $language->get('general', 'back'),
                'BACK_LINK' => URL::build('/panel/store/products/'),
				'PRODUCT_NAME' => $store_language->get('admin', 'product_name'),
				'PRODUCT_DESCRIPTION' => $store_language->get('admin', 'product_description'),
				'PRICE' => $store_language->get('admin', 'price'),
				'CATEGORY' => $store_language->get('admin', 'category'),
				'CATEGORY_LIST' => $store->getAllCategories()
			));
			
			$template->addJSFiles(array(
				(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/ckeditor/plugins/spoiler/js/spoiler.js' => array(),
				(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/ckeditor/ckeditor.js' => array()
			));

			$template->addJSScript(Input::createEditor('inputDescription'));
			
			$template_file = 'store/products_new.tpl';
		break;
		case 'edit';
			// Edit product
			if(!isset($_GET['id']) || !is_numeric($_GET['id'])){
				Redirect::to(URL::build('/panel/store/products'));
				die();
			}
			
			$product = DB::getInstance()->query('SELECT * FROM nl2_store_products WHERE id = ?', array($_GET['id']))->results();
			if(!count($product)) {
				Redirect::to(URL::build('/panel/store/products'));
				die();
			}
			$product = $product[0];
			
			if(Input::exists()){
				$errors = array();
				if(Token::check(Input::get('token'))){
					// Update product
					$validate = new Validate();
					$validation = $validate->check($_POST, array(
						'name' => array(
							'required' => true,
							'min' => 1,
							'max' => 128
						),
						'description' => array(
							'max' => 100000
						)
					));
						
					if ($validation->passed()){
						// Validate if category exist
						$category = DB::getInstance()->query('SELECT id FROM nl2_store_categories WHERE id = ?', array(Input::get('category')))->results();
						if(!count($category)) {
							$errors[] = $store_language->get('admin', 'invalid_category');
						}
							
						// Get price
						if(!isset($_POST['price']) || !is_numeric($_POST['price']) || $_POST['price'] < 0.01 || $_POST['price'] > 1000 || !preg_match('/^\d+(?:\.\d{2})?$/', $_POST['price'])){
							$errors[] = $store_language->get('admin', 'invalid_price');
						} else {
							$price = number_format($_POST['price'], 2, '.', '');
						}

						// insert into database if there is no errors
						if(!count($errors)) {
							// Save to database
							$queries->update('store_products', $product->id, array(
								'name' => Output::getClean(Input::get('name')),
								'description' => Output::getClean(Input::get('description')),
								'category_id' => $category[0]->id,
								'price' => $price
							));
								
							Session::flash('products_success', $store_language->get('admin', 'product_updated_successfully'));
							Redirect::to(URL::build('/panel/store/products'));
							die();
						}
					} else {
						$errors[] = $store_language->get('admin', 'description_max_100000');
					}
				} else {
					// Invalid token
					$errors[] = $language->get('general', 'invalid_token');
				}
			}
			
			// Get product commands
			$commands = DB::getInstance()->query('SELECT * FROM nl2_store_products_commands WHERE product_id = ? ORDER BY `order` ASC', array($product->id));
			$commands_array = array();
			if($commands->count()){
				$commands = $commands->results();
				foreach($commands as $command) {
					$type = 'Unknown';
					switch($command->type) {
						case 1:
							$type = 'Purchase';
						break;
						case 2:
							$type = 'Refund';
						break;
						case 3:
							$type = 'Changeback';
						break;
					}
					
					$commands_array[] = array(
						'id' => Output::getClean($command->id),
						'command' => Output::getClean($command->command),
						'type' => $type,
						'requirePlayer' => ($command->require_online ? 'Yes' : 'No'),
                        'edit_link' => URL::build('/panel/store/products', 'action=edit_command&id=' . $product->id . '&command=' . $command->id),
                        'delete_link' => URL::build('/panel/store/products', 'action=delete_command&id=' . $product->id . '&command=' . $command->id),
					);
				}
			}
			
			
			$smarty->assign(array(
				'EDITING_PRODUCT' => str_replace('{x}', Output::getClean($product->name), $store_language->get('admin', 'editing_product_x')),
				'ID' => Output::getClean($product->id),
				'BACK' => $language->get('general', 'back'),
                'BACK_LINK' => URL::build('/panel/store/products/'),
				'PRODUCT_NAME' => $store_language->get('admin', 'product_name'),
				'PRODUCT_NAME_VALUE' => Output::getClean($product->name),
				'PRODUCT_DESCRIPTION' => $store_language->get('admin', 'product_description'),
				'PRODUCT_DESCRIPTION_VALUE' => Output::getPurified(Output::getDecoded($product->description)),
				'PRICE' => $store_language->get('general', 'price'),
				'PRODUCT_PRICE_VALUE' => Output::getClean($product->price),
				'PRODUCT_CATEGORY_VALUE' => Output::getClean($product->category_id),
				'CATEGORY' => $store_language->get('admin', 'category'),
				'CATEGORY_LIST' => $store->getAllCategories(),
				'COMMANDS' => $store_language->get('admin', 'commands'),
				'NEW_COMMAND' => $store_language->get('admin', 'new_command'),
                'NEW_COMMAND_LINK' => URL::build('/panel/store/products/' , 'action=new_command&id=' . $product->id),
				'COMMAND_LIST' => $commands_array
			));
			
			$template->addJSFiles(array(
				(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/ckeditor/plugins/spoiler/js/spoiler.js' => array(),
				(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/ckeditor/ckeditor.js' => array()
			));

			$template->addJSScript(Input::createEditor('inputDescription'));
			
			$template_file = 'store/products_edit.tpl';
		break;
		case 'delete';
			// Delete product
			if(!isset($_GET['id']) || !is_numeric($_GET['id'])){
				Redirect::to(URL::build('/panel/store/products'));
				die();
			}
			
			$product = DB::getInstance()->query('SELECT id FROM `nl2_store_products` WHERE id = ?', array($_GET['id']))->results();
			if(!count($product)) {
				Redirect::to(URL::build('/panel/store/products'));
				die();
			}
			$product = $product[0];
			
			$queries->update('store_products', $product->id, array(
				'deleted' => date('U')
			));
			
			Session::flash('products_success', $store_language->get('admin', 'product_deleted_successfully'));
			Redirect::to(URL::build('/panel/store/products'));
			die();
		break;
		case 'new_command';
			// New command for product
			if(!isset($_GET['id']) || !is_numeric($_GET['id'])){
				Redirect::to(URL::build('/panel/store/products'));
				die();
			}
			
			$product = DB::getInstance()->query('SELECT id, name FROM nl2_store_products WHERE id = ?', array($_GET['id']))->results();
			if(!count($product)) {
				Redirect::to(URL::build('/panel/store/products'));
				die();
			}
			$product = $product[0];
			
			if(Input::exists()){
				$errors = array();
				if(Token::check(Input::get('token'))){
					// New Command
					$validate = new Validate();
					$validation = $validate->check($_POST, array(
						'command' => array(
							'required' => true,
							'min' => 1,
							'max' => 500
						)
					));
						
					if ($validation->passed()){
						$trigger = Input::get('trigger');
						if(!in_array($trigger, array(1,2,3))) {
							$errors[] = 'Invalid Trigger';
						}
							
						$require_player = Input::get('requirePlayer');
						if(!in_array($require_player, array(0,1))) {
							$errors[] = 'Invalid requirePlayer';
						}
							
						if(!count($errors)) {
							// Get last order
							$last_order = DB::getInstance()->query('SELECT id FROM nl2_store_products_commands WHERE product_id = ? ORDER BY `order` DESC LIMIT 1', array($product->id))->results();
							if(count($last_order)) $last_order = $last_order[0]->order;
							else $last_order = 0;
							
							// Save to database
							$queries->create('store_products_commands', array(
								'product_id' => $product->id,
                                'server_id' => 0,
								'type' => $trigger,
								'command' => Output::getClean(Input::get('command')),
								'require_online' => $require_player,
								'order' => $last_order + 1,
							));
							
							Session::flash('products_success', $store_language->get('admin', 'command_created_successfully'));
							Redirect::to(URL::build('/panel/store/products/', 'action=edit&id=' . $product->id));
							die();
						}
					} else {
						$errors[] = $store_language->get('admin', 'command_max');
					}
				} else {
					// Invalid token
					$errors[] = $language->get('general', 'invalid_token');
				}
			}
			
			$smarty->assign(array(
				'ID' => Output::getClean($product->id),
				'NEW_COMMAND' => str_replace('{x}', Output::getClean($product->name), $store_language->get('admin', 'new_command_for_x')),
				'BACK' => $language->get('general', 'back'),
                'BACK_LINK' => URL::build('/panel/store/products/' , 'action=edit&id=' . $product->id),
			));
			
			$template_file = 'store/products_command_new.tpl';
		break;
		case 'edit_command';
			// Editing command for product
			if(!isset($_GET['id']) || !is_numeric($_GET['id']) || !isset($_GET['command']) || !is_numeric($_GET['command'])){
				Redirect::to(URL::build('/panel/store/products'));
				die();
			}
			
			$product = DB::getInstance()->query('SELECT id, name FROM nl2_store_products WHERE id = ?', array($_GET['id']))->results();
			if(!count($product)) {
				Redirect::to(URL::build('/panel/store/products'));
				die();
			}
			$product = $product[0];
			
			$command = DB::getInstance()->query('SELECT * FROM nl2_store_products_commands WHERE id = ?', array($_GET['command']))->results();
			if(!count($command)) {
				Redirect::to(URL::build('/panel/store/products'));
				die();
			}
			$command = $command[0];
			
			if(Input::exists()){
				$errors = array();
				if(Token::check(Input::get('token'))){
					// New Command
					$validate = new Validate();
					$validation = $validate->check($_POST, array(
						'command' => array(
							'required' => true,
							'min' => 1,
							'max' => 500
						)
					));
						
					if ($validation->passed()){
						$trigger = Input::get('trigger');
						if(!in_array($trigger, array(1,2,3))) {
							$errors[] = 'Invalid Trigger';
						}
							
						$require_player = Input::get('requirePlayer');
						if(!in_array($require_player, array(0,1))) {
							$errors[] = 'Invalid requirePlayer';
						}
							
						if(!count($errors)) {
							// Save to database
							$queries->update('store_products_commands', $command->id, array(
								'type' => $trigger,
								'command' => Output::getClean(Input::get('command')),
								'require_online' => $require_player
							));
							
							Session::flash('products_success', $store_language->get('admin', 'command_updated_successfully'));
							Redirect::to(URL::build('/panel/store/products/', 'action=edit&id=' . $product->id));
							die();
						}
					} else {
						$errors[] = $store_language->get('admin', 'command_max');
					}
				} else {
					// Invalid token
					$errors[] = $language->get('general', 'invalid_token');
				}
			}
			
			$smarty->assign(array(
				'ID' => Output::getClean($product->id),
				'EDITING_COMMAND' => str_replace('{x}', Output::getClean($product->name), $store_language->get('admin', 'editing_command_for_x')),
				'BACK' => $language->get('general', 'back'),
                'BACK_LINK' => URL::build('/panel/store/products/' , 'action=edit&id=' . $product->id),
				'TRIGGER_VALUE' => Output::getClean($command->type),
				'REQUIRE_PLAYER_VALUE' => Output::getClean($command->require_online),
				'COMMAND_VALUE' => Output::getClean($command->command),
			));
		
			$template_file = 'store/products_command_edit.tpl';
		break;
		case 'delete_command';
			// Delete product
			if(!isset($_GET['id']) || !is_numeric($_GET['id']) || !isset($_GET['command']) || !is_numeric($_GET['command'])){
				Redirect::to(URL::build('/panel/store/products'));
				die();
			}
			
			$queries->delete('store_products_commands', array('id', '=', $_GET['command']));

			Session::flash('products_success', $store_language->get('admin', 'command_deleted_successfully'));
			Redirect::to(URL::build('/panel/store/products/', 'action=edit&id=' . $_GET['id']));
			die();
		break;
		default:
			Redirect::to(URL::build('/panel/store/products'));
			die();
		break;
	}
}

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, array($navigation, $cc_nav, $mod_nav), $widgets);

if(Session::exists('products_success'))
	$success = Session::flash('products_success');

if(isset($success))
	$smarty->assign(array(
		'SUCCESS' => $success,
		'SUCCESS_TITLE' => $language->get('general', 'success')
	));

if(isset($errors) && count($errors))
	$smarty->assign(array(
		'ERRORS' => $errors,
		'ERRORS_TITLE' => $language->get('general', 'error')
	));

$smarty->assign(array(
	'PARENT_PAGE' => PARENT_PAGE,
	'DASHBOARD' => $language->get('admin', 'dashboard'),
	'STORE' => $store_language->get('general', 'store'),
	'PAGE' => PANEL_PAGE,
	'TOKEN' => Token::get(),
	'SUBMIT' => $language->get('general', 'submit'),
	'PRODUCTS' => $store_language->get('general', 'products')
));

$page_load = microtime(true) - $start;
define('PAGE_LOAD_TIME', str_replace('{x}', round($page_load, 3), $language->get('general', 'page_loaded_in')));

$template->onPageLoad();

require(ROOT_PATH . '/core/templates/panel_navbar.php');

// Display template
$template->displayTemplate($template_file, $smarty);