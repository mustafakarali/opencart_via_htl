<?php

static $registry = NULL;

// Error Handler
function error_handler_for_export($errno, $errstr, $errfile, $errline) {
	global $registry;
	
	switch ($errno) {
		case E_NOTICE:
		case E_USER_NOTICE:
			$errors = "Notice";
			break;
		case E_WARNING:
		case E_USER_WARNING:
			$errors = "Warning";
			break;
		case E_ERROR:
		case E_USER_ERROR:
			$errors = "Fatal Error";
			break;
		default:
			$errors = "Unknown";
			break;
	}
	
	$config = $registry->get('config');
	$url = $registry->get('url');
	$request = $registry->get('request');
	$session = $registry->get('session');
	$log = $registry->get('log');
	
	if ($config->get('config_error_log')) {
		$log->write('PHP ' . $errors . ':  ' . $errstr . ' in ' . $errfile . ' on line ' . $errline);
	}

	if (($errors=='Warning') || ($errors=='Unknown')) {
		return true;
	}

	if (($errors != "Fatal Error") && isset($request->get['route']) && ($request->get['route']!='tool/export/download'))  {
		if ($config->get('config_error_display')) {
			echo '<b>' . $errors . '</b>: ' . $errstr . ' in <b>' . $errfile . '</b> on line <b>' . $errline . '</b>';
		}
	} else {
		$session->data['export_error'] = array( 'errstr'=>$errstr, 'errno'=>$errno, 'errfile'=>$errfile, 'errline'=>$errline );
		$token = $request->get['token'];
		$link = $url->link( 'tool/export', 'token='.$token, 'SSL' );
		header('Status: ' . 302);
		header('Location: ' . str_replace(array('&amp;', "\n", "\r"), array('&', '', ''), $link));
		exit();
	}

	return true;
}


function fatal_error_shutdown_handler_for_export()
{
	$last_error = error_get_last();
	if ($last_error['type'] === E_ERROR) {
		// fatal error
		error_handler_for_export(E_ERROR, $last_error['message'], $last_error['file'], $last_error['line']);
	}
}


class ModelToolExport extends Model {

	private $error = array();

	function clean( &$str, $allowBlanks=FALSE ) {
		$result = "";
		$n = strlen( $str );
		for ($m=0; $m<$n; $m++) {
			$ch = substr( $str, $m, 1 );
			if (($ch==" ") && (!$allowBlanks) || ($ch=="\n") || ($ch=="\r") || ($ch=="\t") || ($ch=="\0") || ($ch=="\x0B")) {
				continue;
			}
			$result .= $ch;
		}
		return $result;
	}


	function multiquery( &$database, $sql ) {
		foreach (explode(";\n", $sql) as $sql) {
			$sql = trim($sql);
			if ($sql) {
				$database->query($sql);
			}
		}
	}


	protected function getDefaultLanguageId( &$database ) {
		$code = $this->config->get('config_language');
		$sql = "SELECT language_id FROM `".DB_PREFIX."language` WHERE code = '$code'";
		$result = $database->query( $sql );
		$languageId = 1;
		if ($result->rows) {
			foreach ($result->rows as $row) {
				$languageId = $row['language_id'];
				break;
			}
		}
		return $languageId;
	}


	protected function getDefaultWeightUnit() {
		$weightUnit = $this->config->get( 'config_weight_class' );
		return $weightUnit;
	}


	protected function getDefaultMeasurementUnit() {
		$measurementUnit = $this->config->get( 'config_length_class' );
		return $measurementUnit;
	}



	function storeManufacturersIntoDatabase( &$database, &$products, &$manufacturerIds ) {
		// find all manufacturers already stored in the database
		$sql = "SELECT `manufacturer_id`, `name` FROM `".DB_PREFIX."manufacturer`;";
		$result = $database->query( $sql );
		if ($result->rows) {
			foreach ($result->rows as $row) {
				$manufacturerId = $row['manufacturer_id'];
				$name = $row['name'];
				if (!isset($manufacturerIds[$name])) {
					$manufacturerIds[$name] = $manufacturerId;
				} else if ($manufacturerIds[$name] < $manufacturerId) {
					$manufacturerIds[$name] = $manufacturerId;
				}
			}
		}

		// add newly introduced manufacturers to the database
		$maxManufacturerId=0;
		foreach ($manufacturerIds as $manufacturerId) {
			$maxManufacturerId = max( $maxManufacturerId, $manufacturerId );
		}
		$sql = "INSERT INTO `".DB_PREFIX."manufacturer` (`manufacturer_id`, `name`, `image`, `sort_order`) VALUES "; 
		$k = strlen( $sql );
		$first = TRUE;
		foreach ($products as $product) {
			$manufacturerName = $product['manufacturer'];
			if ($manufacturerName=="") {
				continue;
			}
			if (!isset($manufacturerIds[$manufacturerName])) {
				$maxManufacturerId += 1;
				$manufacturerId = $maxManufacturerId;
				$manufacturerIds[$manufacturerName] = $manufacturerId;
				$sql .= ($first) ? "\n" : ",\n";
				$first = FALSE;
				$sql .= "($manufacturerId, '".$database->escape($manufacturerName)."', '', 0)";
			}
		}
		$sql .= ";\n";
		if (strlen( $sql ) > $k+2) {
			$database->query( $sql );
		}
		
		// populate manufacturer_to_store table
		$storeIdsForManufacturers = array();
		foreach ($products as $product) {
			$manufacturerName = $product['manufacturer'];
			if ($manufacturerName=="") {
				continue;
			}
			$manufacturerId = $manufacturerIds[$manufacturerName];
			$storeIds = $product['store_ids'];
			if (!isset($storeIdsForManufacturers[$manufacturerId])) {
				$storeIdsForManufacturers[$manufacturerId] = array();
			}
			foreach ($storeIds as $storeId) {
				if (!in_array($storeId,$storeIdsForManufacturers[$manufacturerId])) {
					$storeIdsForManufacturers[$manufacturerId][] = $storeId;
					$sql2 = "INSERT INTO `".DB_PREFIX."manufacturer_to_store` (`manufacturer_id`,`store_id`) VALUES ($manufacturerId,$storeId);";
					$database->query( $sql2 );
				}
			}
		}
		return TRUE;
	}


	function getWeightClassIds( &$database ) {
		// find the default language id
		$languageId = $this->getDefaultLanguageId($database);
		
		// find all weight classes already stored in the database
		$weightClassIds = array();
		$sql = "SELECT `weight_class_id`, `unit` FROM `".DB_PREFIX."weight_class_description` WHERE `language_id`=$languageId;";
		$result = $database->query( $sql );
		if ($result->rows) {
			foreach ($result->rows as $row) {
				$weightClassId = $row['weight_class_id'];
				$unit = $row['unit'];
				if (!isset($weightClassIds[$unit])) {
					$weightClassIds[$unit] = $weightClassId;
				}
			}
		}

		return $weightClassIds;
	}


	function getLengthClassIds( &$database ) {
		// find the default language id
		$languageId = $this->getDefaultLanguageId($database);
		
		// find all length classes already stored in the database
		$lengthClassIds = array();
		$sql = "SELECT `length_class_id`, `unit` FROM `".DB_PREFIX."length_class_description` WHERE `language_id`=$languageId;";
		$result = $database->query( $sql );
		if ($result->rows) {
			foreach ($result->rows as $row) {
				$lengthClassId = $row['length_class_id'];
				$unit = $row['unit'];
				if (!isset($lengthClassIds[$unit])) {
					$lengthClassIds[$unit] = $lengthClassId;
				}
			}
		}

		return $lengthClassIds;
	}


	function getLayoutIds( &$database ) {
		$result = $database->query( "SELECT * FROM `".DB_PREFIX."layout`" );
		$layoutIds = array();
		foreach ($result->rows as $row) {
			$layoutIds[$row['name']] = $row['layout_id'];
		}
		return $layoutIds;
	}


	protected function getAvailableStoreIds( &$database ) {
		$sql = "SELECT store_id FROM `".DB_PREFIX."store`;";
		$result = $database->query( $sql );
		$storeIds = array(0);
		foreach ($result->rows as $row) {
			if (!in_array((int)$row['store_id'],$storeIds)) {
				$storeIds[] = (int)$row['store_id'];
			}
		}
		return $storeIds;
	}


	function storeProductsIntoDatabase( &$database, &$products ) 
	{
		// find the default language id
		$languageId = $this->getDefaultLanguageId($database);
		
		// start transaction, remove products
		/*$sql = "START TRANSACTION;\n";
		$sql .= "DELETE FROM `".DB_PREFIX."product`;\n";
		$sql .= "DELETE FROM `".DB_PREFIX."product_description` WHERE language_id=$languageId;\n";
		$sql .= "DELETE FROM `".DB_PREFIX."product_to_category`;\n";
		$sql .= "DELETE FROM `".DB_PREFIX."product_to_store`;\n";
		$sql .= "DELETE FROM `".DB_PREFIX."manufacturer_to_store`;\n";
		$sql .= "DELETE FROM `".DB_PREFIX."url_alias` WHERE `query` LIKE 'product_id=%';\n";
		$sql .= "DELETE FROM `".DB_PREFIX."product_related`;\n";
		$sql .= "DELETE FROM `".DB_PREFIX."product_to_layout`;\n";
		$this->multiquery( $database, $sql );*/
		
		// get pre-defined layouts
		$layoutIds = $this->getLayoutIds( $database );
		
		// get pre-defined store_ids
		$availableStoreIds = $this->getAvailableStoreIds( $database );
		
		// store or update manufacturers
		$manufacturerIds = array();
		$ok = $this->storeManufacturersIntoDatabase( $database, $products, $manufacturerIds );
		if (!$ok) {
			$database->query( 'ROLLBACK;' );
			return FALSE;
		}
		
		// get weight classes
		$weightClassIds = $this->getWeightClassIds( $database );
		
		// get length classes
		$lengthClassIds = $this->getLengthClassIds( $database );
		
		// generate and execute SQL for storing the products
		foreach ($products as $product) {
			$productId = $product['product_id'];
			$productName = $database->escape($product['name']);
			$categories = $product['categories'];
			$quantity = $product['quantity'];
			$model = $database->escape($product['model']);
			$manufacturerName = $product['manufacturer'];
			$manufacturerId = ($manufacturerName=="") ? 0 : $manufacturerIds[$manufacturerName];
			$imageName = $product['image'];
			$shipping = $product['shipping'];
			$shipping = ((strtoupper($shipping)=="YES") || (strtoupper($shipping)=="Y") || (strtoupper($shipping)=="TRUE")) ? 1 : 0;
			$price = trim($product['price']);
			$points = $product['points'];
			$dateAdded = $product['date_added'];
			$dateModified = $product['date_modified'];
			$dateAvailable = $product['date_available'];
			$weight = ($product['weight']=="") ? 0 : $product['weight'];
			$unit = $product['unit'];
			$weightClassId = (isset($weightClassIds[$unit])) ? $weightClassIds[$unit] : 0;
			$status = $product['status'];
			$status = ((strtoupper($status)=="TRUE") || (strtoupper($status)=="YES") || (strtoupper($status)=="ENABLED")) ? 1 : 0;
			$taxClassId = $product['tax_class_id'];
			$viewed = $product['viewed'];
			$productDescription = $database->escape($product['description']);
			$stockStatusId = $product['stock_status_id'];
			$meta_description = $database->escape($product['meta_description']);
			$length = $product['length'];
			$width = $product['width'];
			$height = $product['height'];
			$keyword = $database->escape($product['seo_keyword']);
			$lengthUnit = $product['measurement_unit'];
			$lengthClassId = (isset($lengthClassIds[$lengthUnit])) ? $lengthClassIds[$lengthUnit] : 0;
			$sku = $database->escape($product['sku']);
			$upc = $database->escape($product['upc']);
			$ean = $database->escape($product['ean']);
			$jan = $database->escape($product['jan']);
			$isbn = $database->escape($product['isbn']);
			$mpn = $database->escape($product['mpn']);
			$location = $database->escape($product['location']);
			$storeIds = $product['store_ids'];
			$layout = $product['layout'];
			$related = $product['related_ids'];
			$subtract = $product['subtract'];
			$subtract = ((strtoupper($subtract)=="TRUE") || (strtoupper($subtract)=="YES") || (strtoupper($subtract)=="ENABLED")) ? 1 : 0;
			$minimum = $product['minimum'];
			$meta_keywords = $database->escape($product['meta_keywords']);
			$tags = $database->escape($product['tags']);
			$sort_order = $product['sort_order'];
			$sql  = "INSERT INTO `".DB_PREFIX."product` (`product_id`,`quantity`,`sku`,`upc`,`ean`,`jan`,`isbn`,`mpn`,`location`,";
			$sql .= "`stock_status_id`,`model`,`manufacturer_id`,`image`,`shipping`,`price`,`points`,`date_added`,`date_modified`,`date_available`,`weight`,`weight_class_id`,`status`,";
			$sql .= "`tax_class_id`,`viewed`,`length`,`width`,`height`,`length_class_id`,`sort_order`,`subtract`,`minimum`) VALUES ";
			$sql .= "($productId,$quantity,'$sku','$upc','$ean','$jan','$isbn','$mpn','$location',";
			$sql .= "$stockStatusId,'$model',$manufacturerId,'$imageName',$shipping,$price,$points,";
			$sql .= ($dateAdded=='NOW()') ? "$dateAdded," : "'$dateAdded',";
			$sql .= ($dateModified=='NOW()') ? "$dateModified," : "'$dateModified',";
			$sql .= ($dateAvailable=='NOW()') ? "$dateAvailable," : "'$dateAvailable',";
			$sql .= "$weight,$weightClassId,$status,";
			$sql .= "$taxClassId,$viewed,$length,$width,$height,'$lengthClassId','$sort_order','$subtract','$minimum');";
			$sql2 = "INSERT INTO `".DB_PREFIX."product_description` (`product_id`,`language_id`,`name`,`description`,`meta_description`,`meta_keyword`,`tag`) VALUES ";
			$sql2 .= "($productId,$languageId,'$productName','$productDescription','$meta_description','$meta_keywords','$tags');";
			$database->query($sql);
			$database->query($sql2);
			if (count($categories) > 0) {
				$sql = "INSERT INTO `".DB_PREFIX."product_to_category` (`product_id`,`category_id`) VALUES ";
				$first = TRUE;
				foreach ($categories as $categoryId) {
					$sql .= ($first) ? "\n" : ",\n";
					$first = FALSE;
					$sql .= "($productId,$categoryId)";
				}
				$sql .= ";";
				$database->query($sql);
			}
			if ($keyword) {
				$sql4 = "INSERT INTO `".DB_PREFIX."url_alias` (`query`,`keyword`) VALUES ('product_id=$productId','$keyword');";
				$database->query($sql4);
			}
			foreach ($storeIds as $storeId) {
				if (in_array((int)$storeId,$availableStoreIds)) {
					$sql6 = "INSERT INTO `".DB_PREFIX."product_to_store` (`product_id`,`store_id`) VALUES ($productId,$storeId);";
					$database->query($sql6);
				}
			}
			$layouts = array();
			foreach ($layout as $layoutPart) {
				$nextLayout = explode(':',$layoutPart);
				if ($nextLayout===FALSE) {
					$nextLayout = array( 0, $layoutPart );
				} else if (count($nextLayout)==1) {
					$nextLayout = array( 0, $layoutPart );
				}
				if ( (count($nextLayout)==2) && (in_array((int)$nextLayout[0],$availableStoreIds)) && (is_string($nextLayout[1])) ) {
					$storeId = (int)$nextLayout[0];
					$layoutName = $nextLayout[1];
					if (isset($layoutIds[$layoutName])) {
						$layoutId = (int)$layoutIds[$layoutName];
						if (!isset($layouts[$storeId])) {
							$layouts[$storeId] = $layoutId;
						}
					}
				}
			}
			foreach ($layouts as $storeId => $layoutId) {
				$sql7 = "INSERT INTO `".DB_PREFIX."product_to_layout` (`product_id`,`store_id`,`layout_id`) VALUES ($productId,$storeId,$layoutId);";
				$database->query($sql7);
			}
			if (count($related) > 0) {
				$sql = "INSERT INTO `".DB_PREFIX."product_related` (`product_id`,`related_id`) VALUES ";
				$first = TRUE;
				foreach ($related as $relatedId) {
					$sql .= ($first) ? "\n" : ",\n";
					$first = FALSE;
					$sql .= "($productId,$relatedId)";
				}
				$sql .= ";";
				$database->query($sql);
			}
		}
		
		// final commit
		$database->query("COMMIT;");
		return TRUE;
	}


	protected function detect_encoding( $str ) {
		// auto detect the character encoding of a string
		return mb_detect_encoding( $str, 'UTF-8,ISO-8859-15,ISO-8859-1,cp1251,KOI8-R' );
	}


	// hook function for reading additional cells in class extensions
	protected function moreProductCells( $i, &$j, &$worksheet, $product ) {
		return $product;
	}


	function uploadProducts( &$reader, &$database ) {
		// find the default language id and default units
		$languageId = $this->getDefaultLanguageId($database);
		$defaultWeightUnit = $this->getDefaultWeightUnit();
		$defaultMeasurementUnit = $this->getDefaultMeasurementUnit();
		$defaultStockStatusId = $this->config->get('config_stock_status_id');
		
		$data = $reader->getSheet(1);
		$products = array();
		$product = array();
		$isFirstRow = TRUE;
		$i = 0;
		$k = $data->getHighestRow();
		for ($i=0; $i<$k; $i+=1) {
			$j = 1;
			if ($isFirstRow) {
				$isFirstRow = FALSE;
				continue;
			}
			$productId = trim($this->getCell($data,$i,$j++));
			if ($productId=="") {
				continue;
			}
			$name = $this->getCell($data,$i,$j++);
			$name = htmlentities( $name, ENT_QUOTES, $this->detect_encoding($name) );
			$categories = $this->getCell($data,$i,$j++);
			$sku = $this->getCell($data,$i,$j++,'');
			$upc = $this->getCell($data,$i,$j++,'');
			$ean = $this->getCell($data,$i,$j++,'');
			$jan = $this->getCell($data,$i,$j++,'');
			$isbn = $this->getCell($data,$i,$j++,'');
			$mpn = $this->getCell($data,$i,$j++,'');
			$location = $this->getCell($data,$i,$j++,'');
			$quantity = $this->getCell($data,$i,$j++,'0');
			$model = $this->getCell($data,$i,$j++,'   ');
			$manufacturer = $this->getCell($data,$i,$j++);
			$imageName = $this->getCell($data,$i,$j++);
			$shipping = $this->getCell($data,$i,$j++,'yes');
			$price = $this->getCell($data,$i,$j++,'0.00');
			$points = $this->getCell($data,$i,$j++,'0');
			$dateAdded = $this->getCell($data,$i,$j++);
			$dateAdded = ((is_string($dateAdded)) && (strlen($dateAdded)>0)) ? $dateAdded : "NOW()";
			$dateModified = $this->getCell($data,$i,$j++);
			$dateModified = ((is_string($dateModified)) && (strlen($dateModified)>0)) ? $dateModified : "NOW()";
			$dateAvailable = $this->getCell($data,$i,$j++);
			$dateAvailable = ((is_string($dateAvailable)) && (strlen($dateAvailable)>0)) ? $dateAvailable : "NOW()";
			$weight = $this->getCell($data,$i,$j++,'0');
			$unit = $this->getCell($data,$i,$j++,$defaultWeightUnit);
			$length = $this->getCell($data,$i,$j++,'0');
			$width = $this->getCell($data,$i,$j++,'0');
			$height = $this->getCell($data,$i,$j++,'0');
			$measurementUnit = $this->getCell($data,$i,$j++,$defaultMeasurementUnit);
			$status = $this->getCell($data,$i,$j++,'true');
			$taxClassId = $this->getCell($data,$i,$j++,'0');
			$viewed = $this->getCell($data,$i,$j++,'0');
			$langId = $this->getCell($data,$i,$j++,'1');
			if ($langId!=$languageId) {
				continue;
			}
			$keyword = $this->getCell($data,$i,$j++);
			$description = $this->getCell($data,$i,$j++);
			$description = htmlentities( $description, ENT_QUOTES, $this->detect_encoding($description) );
			$meta_description = $this->getCell($data,$i,$j++);
			$meta_description = htmlentities( $meta_description, ENT_QUOTES, $this->detect_encoding($meta_description) );
			$meta_keywords = $this->getCell($data,$i,$j++);
			$meta_keywords = htmlentities( $meta_keywords, ENT_QUOTES, $this->detect_encoding($meta_keywords) );
			$stockStatusId = $this->getCell($data,$i,$j++,$defaultStockStatusId);
			$storeIds = $this->getCell($data,$i,$j++);
			$layout = $this->getCell($data,$i,$j++);
			$related = $this->getCell($data,$i,$j++);
			$tags = $this->getCell($data,$i,$j++);
			$tags = htmlentities( $tags, ENT_QUOTES, $this->detect_encoding($tags) );
			$sort_order = $this->getCell($data,$i,$j++,'0');
			$subtract = $this->getCell($data,$i,$j++,'true');
			$minimum = $this->getCell($data,$i,$j++,'1');
			$product = array();
			$product['product_id'] = $productId;
			$product['name'] = $name;
			$categories = trim( $this->clean($categories, FALSE) );
			$product['categories'] = ($categories=="") ? array() : explode( ",", $categories );
			if ($product['categories']===FALSE) {
				$product['categories'] = array();
			}
			$product['quantity'] = $quantity;
			$product['model'] = $model;
			$product['manufacturer'] = $manufacturer;
			$product['image'] = $imageName;
			$product['shipping'] = $shipping;
			$product['price'] = $price;
			$product['points'] = $points;
			$product['date_added'] = $dateAdded;
			$product['date_modified'] = $dateModified;
			$product['date_available'] = $dateAvailable;
			$product['weight'] = $weight;
			$product['unit'] = $unit;
			$product['status'] = $status;
			$product['tax_class_id'] = $taxClassId;
			$product['viewed'] = $viewed;
			$product['language_id'] = $languageId;
			$product['description'] = $description;
			$product['stock_status_id'] = $stockStatusId;
			$product['meta_description'] = $meta_description;
			$product['length'] = $length;
			$product['width'] = $width;
			$product['height'] = $height;
			$product['seo_keyword'] = $keyword;
			$product['measurement_unit'] = $measurementUnit;
			$product['sku'] = $sku;
			$product['upc'] = $upc;
			$product['ean'] = $ean;
			$product['jan'] = $jan;
			$product['isbn'] = $isbn;
			$product['mpn'] = $mpn;
			$product['location'] = $location;
			$storeIds = trim( $this->clean($storeIds, FALSE) );
			$product['store_ids'] = ($storeIds=="") ? array() : explode( ",", $storeIds );
			if ($product['store_ids']===FALSE) {
				$product['store_ids'] = array();
			}
			$product['related_ids'] = ($related=="") ? array() : explode( ",", $related );
			if ($product['related_ids']===FALSE) {
				$product['related_ids'] = array();
			}
			$product['layout'] = ($layout=="") ? array() : explode( ",", $layout );
			if ($product['layout']===FALSE) {
				$product['layout'] = array();
			}
			$product['subtract'] = $subtract;
			$product['minimum'] = $minimum;
			$product['meta_keywords'] = $meta_keywords;
			$product['tags'] = $tags;
			$product['sort_order'] = $sort_order;
			$products[$productId] = $this->moreProductCells( $i, $j, $data, $product );
		}
		return $this->storeProductsIntoDatabase( $database, $products );
	}


	function storeCategoriesIntoDatabase( &$database, &$categories ) 
	{
		// find the default language id
		$languageId = $this->getDefaultLanguageId($database);

		// start transaction, remove categories
		/*$sql = "START TRANSACTION;\n";
		$sql .= "DELETE FROM `".DB_PREFIX."category`;\n";
		$sql .= "DELETE FROM `".DB_PREFIX."category_description` WHERE language_id=$languageId;\n";
		$sql .= "DELETE FROM `".DB_PREFIX."category_to_store`;\n";
		$sql .= "DELETE FROM `".DB_PREFIX."url_alias` WHERE `query` LIKE 'category_id=%';\n";
		$sql .= "DELETE FROM `".DB_PREFIX."category_to_layout`;\n";
		$sql .= "DELETE FROM `".DB_PREFIX."category_path`;\n";
		$this->multiquery( $database, $sql );
		*/
		// get pre-defined layouts
		$layoutIds = $this->getLayoutIds( $database );
		
		// get pre-defined store_ids
		$availableStoreIds = $this->getAvailableStoreIds( $database );
		
		// generate and execute SQL for inserting the categories
		foreach ($categories as $category) {
			$categoryId = $category['category_id'];
			$imageName = $category['image'];
			$parentId = $category['parent_id'];
			$top = $category['top'];
			$top = ((strtoupper($top)=="TRUE") || (strtoupper($top)=="YES") || (strtoupper($top)=="ENABLED")) ? 1 : 0;
			$columns = $category['columns'];
			$sortOrder = $category['sort_order'];
			$dateAdded = $category['date_added'];
			$dateModified = $category['date_modified'];
			$languageId = $category['language_id'];
			$name = $database->escape($category['name']);
			$description = $database->escape($category['description']);
			$meta_description = $database->escape($category['meta_description']);
			$meta_keywords = $database->escape($category['meta_keywords']);
			$keyword = $database->escape($category['seo_keyword']);
			$storeIds = $category['store_ids'];
			$layout = $category['layout'];
			$status = $category['status'];
			$status = ((strtoupper($status)=="TRUE") || (strtoupper($status)=="YES") || (strtoupper($status)=="ENABLED")) ? 1 : 0;
			$sql2 = "INSERT INTO `".DB_PREFIX."category` (`category_id`, `image`, `parent_id`, `top`, `column`, `sort_order`, `date_added`, `date_modified`, `status`) VALUES ";
			$sql2 .= "( $categoryId, '$imageName', $parentId, $top, $columns, $sortOrder, ";
			$sql2 .= ($dateAdded=='NOW()') ? "$dateAdded," : "'$dateAdded',";
			$sql2 .= ($dateModified=='NOW()') ? "$dateModified," : "'$dateModified',";
			$sql2 .= " $status);";
			$database->query( $sql2 );
			$sql3 = "INSERT INTO `".DB_PREFIX."category_description` (`category_id`, `language_id`, `name`, `description`, `meta_description`, `meta_keyword`) VALUES ";
			$sql3 .= "( $categoryId, $languageId, '$name', '$description', '$meta_description', '$meta_keywords' );";
			$database->query( $sql3 );
			if ($keyword) {
				$sql5 = "INSERT INTO `".DB_PREFIX."url_alias` (`query`,`keyword`) VALUES ('category_id=$categoryId','$keyword');";
				$database->query($sql5);
			}
			foreach ($storeIds as $storeId) {
				if (in_array((int)$storeId,$availableStoreIds)) {
					$sql6 = "INSERT INTO `".DB_PREFIX."category_to_store` (`category_id`,`store_id`) VALUES ($categoryId,$storeId);";
					$database->query($sql6);
				}
			}
			$layouts = array();
			foreach ($layout as $layoutPart) {
				$nextLayout = explode(':',$layoutPart);
				if ($nextLayout===FALSE) {
					$nextLayout = array( 0, $layoutPart );
				} else if (count($nextLayout)==1) {
					$nextLayout = array( 0, $layoutPart );
				}
				if ( (count($nextLayout)==2) && (in_array((int)$nextLayout[0],$availableStoreIds)) && (is_string($nextLayout[1])) ) {
					$storeId = (int)$nextLayout[0];
					$layoutName = $nextLayout[1];
					if (isset($layoutIds[$layoutName])) {
						$layoutId = (int)$layoutIds[$layoutName];
						if (!isset($layouts[$storeId])) {
							$layouts[$storeId] = $layoutId;
						}
					}
				}
			}
			foreach ($layouts as $storeId => $layoutId) {
				$sql7 = "INSERT INTO `".DB_PREFIX."category_to_layout` (`category_id`,`store_id`,`layout_id`) VALUES ($categoryId,$storeId,$layoutId);";
				$database->query($sql7);
			}
		}
		
		// restore category paths for faster lookups on the frontend
		$this->load->model( 'catalog/category' );
		$this->model_catalog_category->repairCategories(0);

		// final commit
		$database->query( "COMMIT;" );
		return TRUE;
	}


	// hook function for reading additional cells in class extensions
	protected function moreCategoryCells( $i, &$j, &$worksheet, $category ) {
		return $category;
	}


	function uploadCategories( &$reader, &$database ) 
	{
		// find the default language id
		$languageId = $this->getDefaultLanguageId($database);
		
		$data = $reader->getSheet(0);
		$categories = array();
		$isFirstRow = TRUE;
		$i = 0;
		$k = $data->getHighestRow();
		for ($i=0; $i<$k; $i+=1) {
			$j = 1;
			if ($isFirstRow) {
				$isFirstRow = FALSE;
				continue;
			}
			$categoryId = trim($this->getCell($data,$i,$j++));
			if ($categoryId=="") {
				continue;
			}
			$parentId = $this->getCell($data,$i,$j++,'0');
			$name = $this->getCell($data,$i,$j++);
			$name = htmlentities( $name, ENT_QUOTES, $this->detect_encoding($name) );
			$top = $this->getCell($data,$i,$j++,($parentId=='0')?'true':'false');
			$columns = $this->getCell($data,$i,$j++,($parentId=='0')?'1':'0');
			$sortOrder = $this->getCell($data,$i,$j++,'0');
			$imageName = trim($this->getCell($data,$i,$j++));
			$dateAdded = trim($this->getCell($data,$i,$j++));
			$dateAdded = ((is_string($dateAdded)) && (strlen($dateAdded)>0)) ? $dateAdded : "NOW()";
			$dateModified = trim($this->getCell($data,$i,$j++));
			$dateModified = ((is_string($dateModified)) && (strlen($dateModified)>0)) ? $dateModified : "NOW()";
			$langId = $this->getCell($data,$i,$j++,'1');
			if ($langId != $languageId) {
				continue;
			}
			$keyword = $this->getCell($data,$i,$j++);
			$description = $this->getCell($data,$i,$j++);
			$description = htmlentities( $description, ENT_QUOTES, $this->detect_encoding($description) );
			$meta_description = $this->getCell($data,$i,$j++);
			$meta_description = htmlentities( $meta_description, ENT_QUOTES, $this->detect_encoding($meta_description) );
			$meta_keywords = $this->getCell($data,$i,$j++);
			$meta_keywords = htmlentities( $meta_keywords, ENT_QUOTES, $this->detect_encoding($meta_keywords) );
			$storeIds = $this->getCell($data,$i,$j++);
			$layout = $this->getCell($data,$i,$j++,'');
			$status = $this->getCell($data,$i,$j++,'true');
			$category = array();
			$category['category_id'] = $categoryId;
			$category['image'] = $imageName;
			$category['parent_id'] = $parentId;
			$category['sort_order'] = $sortOrder;
			$category['date_added'] = $dateAdded;
			$category['date_modified'] = $dateModified;
			$category['language_id'] = $languageId;
			$category['name'] = $name;
			$category['top'] = $top;
			$category['columns'] = $columns;
			$category['description'] = $description;
			$category['meta_description'] = $meta_description;
			$category['meta_keywords'] = $meta_keywords;
			$category['seo_keyword'] = $keyword;
			$storeIds = trim( $this->clean($storeIds, FALSE) );
			$category['store_ids'] = ($storeIds=="") ? array() : explode( ",", $storeIds );
			if ($category['store_ids']===FALSE) {
				$category['store_ids'] = array();
			}
			$category['layout'] = ($layout=="") ? array() : explode( ",", $layout );
			if ($category['layout']===FALSE) {
				$category['layout'] = array();
			}
			$category['status'] = $status;
			$categories[$categoryId] = $this->moreCategoryCells( $i, $j, $data, $category );
		}
		return $this->storeCategoriesIntoDatabase( $database, $categories );
	}


	function storeOptionsIntoDatabase( &$database, &$options ) 
	{
		// find the default language id
		$languageId = $this->getDefaultLanguageId($database);
		
		// reuse old options and old option_values where possible
		$optionIds = array();       // indexed by [name][type]
		$optionValueIds = array();  // indexed by [name][type][value][image]
		$maxOptionSortOrder = 0;
		$maxOptionId = 0;
		$maxOptionValueId = 0;
		$sql  = "SELECT o.*, od.name, ovd.option_value_id, ovd.name AS value, ov.image FROM `".DB_PREFIX."option` o ";
		$sql .= "INNER JOIN `".DB_PREFIX."option_description` od ON od.option_id=o.option_id AND od.language_id=$languageId ";
		$sql .= "LEFT JOIN `".DB_PREFIX."option_value` ov ON ov.option_id=o.option_id ";
		$sql .= "LEFT JOIN  `".DB_PREFIX."option_value_description` ovd ON ovd.option_value_id=ov.option_value_id AND ovd.language_id=$languageId ";
		$result = $database->query( $sql );
		foreach ($result->rows as $row) {
			$name = $row['name'];
			$type = $row['type'];
			$value = $row['value'];
			$optionId = $row['option_id'];
			$optionValueId = $row['option_value_id'];
			$image = $row['image'];
			$optionSortOrder = $row['sort_order'];
			
			if ($maxOptionId < $optionId) {
				$maxOptionId = $optionId;
			}
			if ($maxOptionValueId < $optionValueId) {
				$maxOptionValueId = $optionValueId;
			}
			if ($maxOptionSortOrder < $optionSortOrder) {
				$maxOptionSortOrder = $optionSortOrder;
			}
			if (!isset($optionIds[$name])) {
				$optionIds[$name] = array();
			}
			if (!isset($optionIds[$name][$type])) {
				$optionIds[$name][$type] = $optionId;
			}
			if (!isset($optionValueIds[$name])) {
				$optionValueIds[$name] = array();
			}
			if (!isset($optionValueIds[$name][$type])) {
				$optionValueIds[$name][$type] = array();
			}
			if (!isset($optionValueIds[$name][$type][$value])) {
				$optionValueIds[$name][$type][$value] = array();
			}
			if (!isset($optionValueIds[$name][$type][$value][$image])) {
				$optionValueIds[$name][$type][$value][$image] = $optionValueId;
			}
		}
		
		// start transaction, remove product options and product option values from database
		/*$sql = "START TRANSACTION;\n";
		$sql .= "DELETE FROM `".DB_PREFIX."product_option`;\n";
		$sql .= "DELETE FROM `".DB_PREFIX."product_option_value`;\n";
		$this->multiquery( $database, $sql );*/
		
		// add product options and product option values to the database
		$productOptionIds = array(); // indexed by [product_id][option_id]
		$maxProductOptionId = 0;
		$maxProductOptionValueId = 0;
		foreach ($options as $option) {
			$productId = $option['product_id'];
			$langId = $option['language_id'];
			$name = $option['option'];
			$type = $option['type'];
			$value = $option['value'];
			$image = $option['image'];
			$required = $option['required'];
			$required = ((strtoupper($required)=="TRUE") || (strtoupper($required)=="YES") || (strtoupper($required)=="ENABLED")) ? 1 : 0;
			if (!isset($optionIds[$name])) {
				$optionIds[$name] = array();
			}
			if (!isset($optionIds[$name][$type])) {
				$maxOptionId += 1;
				$optionId = $maxOptionId;
				$optionIds[$name][$type] = $optionId;
				$maxOptionSortOrder += 1;
				$sql = "INSERT INTO `".DB_PREFIX."option` (`option_id`,`type`,`sort_order`) VALUES ($optionId,'$type',$maxOptionSortOrder);";
				$database->query( $sql );
				$sql = "INSERT INTO `".DB_PREFIX."option_description` (`option_id`,`language_id`,`name`) VALUES ($optionId,$langId,'".$database->escape($name)."');";
				$database->query( $sql);
			}
			if (($type=='select') || ($type=='checkbox') || ($type=='radio') || ($type=='image')) {
				if (!isset($optionValueIds[$name])) {
					$optionValueIds[$name] = array();
				}
				if (!isset($optionValueIds[$name][$type])) {
					$optionValueIds[$name][$type] = array();
				}
				if (!isset($optionValueIds[$name][$type][$value])) {
					$optionValueIds[$name][$type][$value] = array();
				}
				if (!isset($optionValueIds[$name][$type][$value][$image])) {
					$maxOptionValueId += 1;
					$optionValueId = $maxOptionValueId;
					$optionValueIds[$name][$type][$value][$image] = $optionValueId;
					$sortOrder = ($option['sort_order'] == '') ? 0 : $option['sort_order'];
					$optionId = $optionIds[$name][$type];
					$sql  = "INSERT INTO `".DB_PREFIX."option_value` (`option_value_id`,`option_id`,`image`,`sort_order`) VALUES ";
					$sql .= "($optionValueId,$optionId,'".$database->escape($image)."',$sortOrder);";
					$database->query( $sql );
					$sql  = "INSERT INTO `".DB_PREFIX."option_value_description` (`option_value_id`,`language_id`,`option_id`,`name`) VALUES ";
					$sql .= "($optionValueId,$langId,$optionId,'".$database->escape($value)."');";
					$database->query( $sql );
				} else {
					$optionValueId = $optionValueIds[$name][$type][$value][$image];
					$sortOrder = ($option['sort_order'] == '') ? 0 : $option['sort_order'];
					$optionId = $optionIds[$name][$type];
					$sql  = "UPDATE `".DB_PREFIX."option_value` SET `image`='".$database->escape($image)."', `sort_order`=$sortOrder ";
					$sql .= "WHERE `option_value_id`=$optionValueId AND `option_id`=$optionId";
					$database->query( $sql );
				}
			}
			if (!isset($productOptionIds[$productId])) {
				$productOptionIds[$productId] = array();
			}
			$optionId = $optionIds[$name][$type];
			if (!isset($productOptionIds[$productId][$optionId])) {
				$maxProductOptionId += 1;
				$productOptionId = $maxProductOptionId;
				$productOptionIds[$productId][$optionId] = $productOptionId;
				if (($type!='select') && ($type!='checkbox') && ($type!='radio') && ($type!='image')) {
					$productOptionValue = $value;
				} else {
					$productOptionValue = '';
				}
				$sql  = "INSERT INTO `".DB_PREFIX."product_option` (`product_option_id`,`product_id`,`option_id`,`option_value`,`required`) VALUES ";
				$sql .= "($productOptionId,$productId,$optionId,'".$database->escape($productOptionValue)."',$required);";
				$database->query( $sql );
			}
			if (($type=='select') || ($type=='checkbox') || ($type=='radio') || ($type=='image')) {
				$quantity = $option['quantity'];
				$subtract = $option['subtract'];
				$subtract = ((strtoupper($subtract)=="TRUE") || (strtoupper($subtract)=="YES") || (strtoupper($subtract)=="ENABLED")) ? 1 : 0;
				$price = $option['price'];
				$pricePrefix = $option['price_prefix'];
				$points = $option['points'];
				$pointsPrefix = $option['points_prefix'];
				$weight = $option['weight'];
				$weightPrefix = $option['weight_prefix'];
				$sortOrder= $option['sort_order'];
				$maxProductOptionValueId += 1;
				$productOptionValueId = $maxProductOptionValueId;
				$optionId = $optionIds[$name][$type];
				$optionValueId = $optionValueIds[$name][$type][$value][$image];
				$productOptionId = $productOptionIds[$productId][$optionId];
				$sql  = "INSERT INTO `".DB_PREFIX."product_option_value` (`product_option_value_id`,`product_option_id`,`product_id`,`option_id`,`option_value_id`,`quantity`,`subtract`,`price`,`price_prefix`,`points`,`points_prefix`,`weight`,`weight_prefix`) VALUES ";
				$sql .= "($productOptionValueId,$productOptionId,$productId,$optionId,$optionValueId,$quantity,$subtract,$price,'$pricePrefix',$points,'$pointsPrefix',$weight,'$weightPrefix');";
				$database->query( $sql );
			}
		}
		
		$database->query("COMMIT;");
		return TRUE;
	}


	// hook function for reading additional cells in class extensions
	protected function moreOptionCells( $i, &$j, &$worksheet, $option ) {
		return $option;
	}


	function uploadOptions( &$reader, &$database ) 
	{
		// find the default language id
		$languageId = $this->getDefaultLanguageId($database);
		
		$data = $reader->getSheet(3);
		$options = array();
		$i = 0;
		$k = $data->getHighestRow();
		$isFirstRow = TRUE;
		for ($i=0; $i<$k; $i+=1) {
			$j = 1;
			if ($isFirstRow) {
				$isFirstRow = FALSE;
				continue;
			}
			$productId = trim($this->getCell($data,$i,$j++));
			if ($productId=="") {
				continue;
			}
			$langId = $this->getCell($data,$i,$j++);
			if ($langId!=$languageId) {
				continue;
			}
			$option = $this->getCell($data,$i,$j++);
			$type = $this->getCell($data,$i,$j++,'select');
			$value = $this->getCell($data,$i,$j++,'');
			$image = $this->getCell($data,$i,$j++,'');
			$required = $this->getCell($data,$i,$j++,'true');
			$quantity = $this->getCell($data,$i,$j++,'0');
			$subtract = $this->getCell($data,$i,$j++,'false');
			$price = $this->getCell($data,$i,$j++,'0');
			$pricePrefix = $this->getCell($data,$i,$j++,'+');
			$points = $this->getCell($data,$i,$j++,'0');
			$pointsPrefix = $this->getCell($data,$i,$j++,'+');
			$weight = $this->getCell($data,$i,$j++,'0.00');
			$weightPrefix = $this->getCell($data,$i,$j++,'+');
			$sortOrder = $this->getCell($data,$i,$j++,'0');
			$options[$i] = array();
			$options[$i]['product_id'] = $productId;
			$options[$i]['language_id'] = $languageId;
			$options[$i]['option'] = $option;
			$options[$i]['type'] = $type;
			$options[$i]['value'] = $value;
			$options[$i]['image'] = $image;
			$options[$i]['required'] = $required;
			if (($type=='select') || ($type=='checkbox') || ($type=='radio') || ($type=='image')) {
				$options[$i]['quantity'] = $quantity;
				$options[$i]['subtract'] = $subtract;
				$options[$i]['price'] = $price;
				$options[$i]['price_prefix'] = $pricePrefix;
				$options[$i]['points'] = $points;
				$options[$i]['points_prefix'] = $pointsPrefix;
				$options[$i]['weight'] = $weight;
				$options[$i]['weight_prefix'] = $weightPrefix;
				$options[$i]['sort_order'] = $sortOrder;
			}
			$options[$i] = $this->moreOptionCells( $i, $j, $data, $options[$i] );
		}
		return $this->storeOptionsIntoDatabase( $database, $options );
	}


	function storeAttributesIntoDatabase( &$database, &$attributes ) {
		// find the default language id
		$languageId = $this->getDefaultLanguageId($database);
		
		// reuse old attribute_groups and attributes where possible
		$attributeGroupIds = array();    // indexed by [group]
		$attributeIds = array();         // indexed by [group][name]
		$maxAttributeGroupSortOrder = 0;
		$maxAttributeGroupId = 0;
		$maxAttributeId = 0;
		$maxAttributeSortOrders = array(); // index by [group]
		$sql  = "SELECT ag.attribute_group_id, ag.sort_order AS group_sort_order, agd.name AS `group`, a.sort_order, ad.attribute_id, ad.name FROM `".DB_PREFIX."attribute_group` ag ";
		$sql .= "INNER JOIN `".DB_PREFIX."attribute_group_description` agd ON agd.attribute_group_id=ag.attribute_group_id AND agd.language_id=$languageId ";
		$sql .= "LEFT JOIN  `".DB_PREFIX."attribute` a ON a.attribute_group_id=ag.attribute_group_id ";
		$sql .= "INNER JOIN  `".DB_PREFIX."attribute_description` ad ON ad.attribute_id=a.attribute_id AND ad.language_id=$languageId ";
		$result = $database->query( $sql );
		foreach ($result->rows as $row) {
			$attributeGroupId = $row['attribute_group_id'];
			$attributeId = $row['attribute_id'];
			$group = $row['group'];
			$name = $row['name'];
			$attributeGroupSortOrder = $row['group_sort_order'];
			$attributeSortOrder = $row['sort_order'];
			if ($maxAttributeGroupId < $attributeGroupId) {
				$maxAttributeGroupId = $attributeGroupId;
			}
			if ($maxAttributeId < $attributeId) {
				$maxAttributeId = $attributeId;
			}
			if ($maxAttributeGroupSortOrder < $attributeGroupSortOrder) {
				$maxAttributeGroupSortOrder = $attributeGroupSortOrder;
			}
			if (!isset($maxAttributeSortOrders[$group])) {
				$maxAttributeSortOrders[$group] = $attributeSortOrder;
			}
			if ($maxAttributeSortOrders[$group] < $attributeSortOrder) {
				$maxAttributeSortOrders[$group] = $attributeSortOrder;
			}
			if (!isset($attributeGroupIds[$group])) {
				$attributeGroupIds[$group] = $attributeGroupId;
			}
			if (!isset($attributeIds[$group])) {
				$attributeIds[$group] = array();
			}
			if (!isset($attributeIds[$group][$name])) {
				$attributeIds[$group][$name] = $attributeId;
			}
		}
		
		// start transaction, remove product attributes from database
		/*$sql = "START TRANSACTION;\n";
		$sql .= "DELETE FROM `".DB_PREFIX."product_attribute` WHERE language_id=$languageId;\n";
		$this->multiquery( $database, $sql );*/
		
		// add product attributes to the database
		foreach ($attributes as $attribute) {
			$productId = $attribute['product_id'];
			$langId = $attribute['language_id'];
			$group = $attribute['group'];
			$name = $attribute['name'];
			$text = $attribute['text'];
			if (!isset($attributeGroupIds[$group])) {
				$maxAttributeGroupId += 1;
				$maxAttributeGroupSortOrder += 1;
				$attributeGroupId = $maxAttributeGroupId;
				$attributeGroupIds[$group] = $attributeGroupId;
				$sql  = "INSERT INTO `".DB_PREFIX."attribute_group` (`attribute_group_id`,`sort_order`) VALUES ";
				$sql .= "($attributeGroupId,$maxAttributeGroupSortOrder);";
				$database->query( $sql );
				$sql  = "INSERT INTO `".DB_PREFIX."attribute_group_description` (`attribute_group_id`,`language_id`,`name`) VALUES ";
				$sql .= "($attributeGroupId,$langId,'".$database->escape($group)."');";
				$database->query( $sql );
			}
			if (!isset($attributeIds[$group])) {
				$attributeIds[$group] = array();
			}
			if (!isset($attributeIds[$group][$name])) {
				$maxAttributeId += 1;
				$attributeId = $maxAttributeId;
				$attributeIds[$group][$name] = $attributeId;
				$attributeGroupId = $attributeGroupIds[$group];
				if (!isset($maxAttributeSortOrders[$group])) {
					$maxAttributeSortOrders[$group] = 0;
				}
				$maxAttributeSortOrders[$group] += 1;
				$attributeSortOrder = $maxAttributeSortOrders[$group];
				$sql  = "INSERT INTO `".DB_PREFIX."attribute` (`attribute_id`,`attribute_group_id`,`sort_order`) VALUES ";
				$sql .= "($attributeId,$attributeGroupId,$attributeSortOrder);";
				$database->query( $sql );
				$sql  = "INSERT INTO `".DB_PREFIX."attribute_description` (`attribute_id`,`language_id`,`name`) VALUES ";
				$sql .= "($attributeId,$langId,'".$database->escape($name)."');";
				$database->query( $sql );
			}
			$attributeId = $attributeIds[$group][$name];
			$sql  = "INSERT INTO `".DB_PREFIX."product_attribute` (`product_id`,`attribute_id`,`language_id`,`text`) VALUES ";
			$sql .= "($productId,$attributeId,$langId,'".$database->escape( $text )."');";
			$database->query( $sql );
		}
		
		$database->query("COMMIT;");
		return TRUE;
	}


	// hook function for reading additional cells in class extensions
	protected function moreAttributeCells( $i, &$j, &$worksheet, $attribute ) {
		return $attribute;
	}


	function uploadAttributes( &$reader, &$database ) 
	{
		// find the default language id
		$languageId = $this->getDefaultLanguageId($database);
		
		$data = $reader->getSheet(4);
		$attributes = array();
		$i = 0;
		$k = $data->getHighestRow();
		$isFirstRow = TRUE;
		for ($i=0; $i<$k; $i+=1) {
			$j = 1;
			if ($isFirstRow) {
				$isFirstRow = FALSE;
				continue;
			}
			$productId = trim($this->getCell($data,$i,$j++));
			if ($productId=="") {
				continue;
			}
			$langId = $this->getCell($data,$i,$j++);
			if ($langId!=$languageId) {
				continue;
			}
			$group = trim($this->getCell($data,$i,$j++));
			if ($group=='') {
				continue;
			}
			$name = trim($this->getCell($data,$i,$j++));
			if ($name=='') {
				continue;
			}
			$text = $this->getCell($data,$i,$j++);
			$attributes[$i] = array();
			$attributes[$i]['product_id'] = $productId;
			$attributes[$i]['language_id'] = $languageId;
			$attributes[$i]['group'] = $group;
			$attributes[$i]['name'] = $name;
			$attributes[$i]['text'] = $text;
			$attributes[$i] = $this->moreAttributeCells( $i, $j, $data, $attributes[$i] );
		}
		return $this->storeAttributesIntoDatabase( $database, $attributes );
	}


	function storeSpecialsIntoDatabase( &$database, &$specials )
	{
		/*$sql = "START TRANSACTION;\n";
		$sql .= "DELETE FROM `".DB_PREFIX."product_special`;\n";
		$this->multiquery( $database, $sql );*/

		// find the default language id
		$languageId = $this->getDefaultLanguageId($database);

		// find existing customer groups from the database
		$sql = "SELECT `customer_group_id`, `name` FROM `".DB_PREFIX."customer_group_description` WHERE language_id=$languageId";
		$result = $database->query( $sql );
		$maxCustomerGroupId = 0;
		$customerGroups = array();
		foreach ($result->rows as $row) {
			$customerGroupId = $row['customer_group_id'];
			$name = $row['name'];
			if (!isset($customerGroups[$name])) {
				$customerGroups[$name] = $customerGroupId;
			}
			if ($maxCustomerGroupId < $customerGroupId) {
				$maxCustomerGroupId = $customerGroupId;
			}
		}

		// add additional customer groups into the database
		foreach ($specials as $special) {
			$name = $special['customer_group'];
			if (!isset($customerGroups[$name])) {
				$maxCustomerGroupId += 1;
				$sql  = "INSERT INTO `".DB_PREFIX."customer_group` (`customer_group_id`, `approval`, `company_id_display`, `company_id_required`, `tax_id_display`, `tax_id_required`, `sort_order`) VALUES "; 
				$sql .= "($maxCustomerGroupId, 0, 1, 0, 0, 1, 1)";
				$sql .= ";\n";
				$database->query($sql);
				$sql  = "INSERT INTO `".DB_PREFIX."customer_group_description` (`customer_group_id`, `language_id`, `name`, `description`) VALUES ";
				$sql .= "($maxCustomerGroupId, $languageId, '".$database->escape($name)."', '' )"; 
				$sql .= ";\n";
				$database->query($sql);
				$customerGroups[$name] = $maxCustomerGroupId;
			}
		}

		// store product specials into the database
		$productSpecialId = 0;
		$first = TRUE;
		$sql = "INSERT INTO `".DB_PREFIX."product_special` (`product_special_id`,`product_id`,`customer_group_id`,`priority`,`price`,`date_start`,`date_end` ) VALUES "; 
		foreach ($specials as $special) {
			$productSpecialId += 1;
			$productId = $special['product_id'];
			$name = $special['customer_group'];
			$customerGroupId = $customerGroups[$name];
			$priority = $special['priority'];
			$price = $special['price'];
			$dateStart = $special['date_start'];
			$dateEnd = $special['date_end'];
			$sql .= ($first) ? "\n" : ",\n";
			$first = FALSE;
			$sql .= "($productSpecialId,$productId,$customerGroupId,$priority,$price,'$dateStart','$dateEnd')";
		}
		if (!$first) {
			$database->query($sql);
		}

		$database->query("COMMIT;");
		return TRUE;
	}


	// hook function for reading additional cells in class extensions
	protected function moreSpecialCells( $i, &$j, &$worksheet, $special ) {
		return $special;
	}


	function uploadSpecials( &$reader, &$database ) 
	{
		$data = $reader->getSheet(5);
		$specials = array();
		$i = 0;
		$k = $data->getHighestRow();
		$isFirstRow = TRUE;
		for ($i=0; $i<$k; $i+=1) {
			$j = 1;
			if ($isFirstRow) {
				$isFirstRow = FALSE;
				continue;
			}
			$productId = trim($this->getCell($data,$i,$j++));
			if ($productId=="") {
				continue;
			}
			$customerGroup = trim($this->getCell($data,$i,$j++));
			if ($customerGroup=="") {
				continue;
			}
			$priority = $this->getCell($data,$i,$j++,'0');
			$price = $this->getCell($data,$i,$j++,'0');
			$dateStart = $this->getCell($data,$i,$j++,'0000-00-00');
			$dateEnd = $this->getCell($data,$i,$j++,'0000-00-00');
			$specials[$i] = array();
			$specials[$i]['product_id'] = $productId;
			$specials[$i]['customer_group'] = $customerGroup;
			$specials[$i]['priority'] = $priority;
			$specials[$i]['price'] = $price;
			$specials[$i]['date_start'] = $dateStart;
			$specials[$i]['date_end'] = $dateEnd;
			$specials[$i] = $this->moreSpecialCells( $i, $j, $data, $specials[$i] );
		}
		return $this->storeSpecialsIntoDatabase( $database, $specials );
	}


	function storeDiscountsIntoDatabase( &$database, &$discounts )
	{
		/*$sql = "START TRANSACTION;\n";
		$sql .= "DELETE FROM `".DB_PREFIX."product_discount`;\n";
		$this->multiquery( $database, $sql );*/

		// find the default language id
		$languageId = $this->getDefaultLanguageId($database);

		// find existing customer groups from the database
		$sql = "SELECT `customer_group_id`, `name` FROM `".DB_PREFIX."customer_group_description` WHERE language_id=$languageId";
		$result = $database->query( $sql );
		$maxCustomerGroupId = 0;
		$customerGroups = array();
		foreach ($result->rows as $row) {
			$customerGroupId = $row['customer_group_id'];
			$name = $row['name'];
			if (!isset($customerGroups[$name])) {
				$customerGroups[$name] = $customerGroupId;
			}
			if ($maxCustomerGroupId < $customerGroupId) {
				$maxCustomerGroupId = $customerGroupId;
			}
		}

		// add additional customer groups into the database
		foreach ($discounts as $discount) {
			$name = $discount['customer_group'];
			if (!isset($customerGroups[$name])) {
				$maxCustomerGroupId += 1;
				$sql  = "INSERT INTO `".DB_PREFIX."customer_group` (`customer_group_id`, `approval`, `company_id_display`, `company_id_required`, `tax_id_display`, `tax_id_required`, `sort_order`) VALUES "; 
				$sql .= "($maxCustomerGroupId, 0, 1, 0, 0, 1, 1)";
				$sql .= ";\n";
				$database->query($sql);
				$sql  = "INSERT INTO `".DB_PREFIX."customer_group_description` (`customer_group_id`, `language_id`, `name`, `description`) VALUES ";
				$sql .= "($maxCustomerGroupId, $languageId, '".$database->escape($name)."', '' )"; 
				$sql .= ";\n";
				$database->query($sql);
				$customerGroups[$name] = $maxCustomerGroupId;
			}
		}

		// store product discounts into the database
		$productDiscountId = 0;
		$first = TRUE;
		$sql = "INSERT INTO `".DB_PREFIX."product_discount` (`product_discount_id`,`product_id`,`customer_group_id`,`quantity`,`priority`,`price`,`date_start`,`date_end` ) VALUES "; 
		foreach ($discounts as $discount) {
			$productDiscountId += 1;
			$productId = $discount['product_id'];
			$name = $discount['customer_group'];
			$customerGroupId = $customerGroups[$name];
			$quantity = $discount['quantity'];
			$priority = $discount['priority'];
			$price = $discount['price'];
			$dateStart = $discount['date_start'];
			$dateEnd = $discount['date_end'];
			$sql .= ($first) ? "\n" : ",\n";
			$first = FALSE;
			$sql .= "($productDiscountId,$productId,$customerGroupId,$quantity,$priority,$price,'$dateStart','$dateEnd')";
		}
		if (!$first) {
			$database->query($sql);
		}

		$database->query("COMMIT;");
		return TRUE;
	}


	// hook function for reading additional cells in class extensions
	protected function moreDiscountCells( $i, &$j, &$worksheet, $discount ) {
		return $discount;
	}


	function uploadDiscounts( &$reader, &$database ) 
	{
		$data = $reader->getSheet(6);
		$discounts = array();
		$i = 0;
		$k = $data->getHighestRow();
		$isFirstRow = TRUE;
		for ($i=0; $i<$k; $i+=1) {
			$j = 1;
			if ($isFirstRow) {
				$isFirstRow = FALSE;
				continue;
			}
			$productId = trim($this->getCell($data,$i,$j++));
			if ($productId=="") {
				continue;
			}
			$customerGroup = trim($this->getCell($data,$i,$j++));
			if ($customerGroup=="") {
				continue;
			}
			$quantity = $this->getCell($data,$i,$j++,'0');
			$priority = $this->getCell($data,$i,$j++,'0');
			$price = $this->getCell($data,$i,$j++,'0');
			$dateStart = $this->getCell($data,$i,$j++,'0000-00-00');
			$dateEnd = $this->getCell($data,$i,$j++,'0000-00-00');
			$discounts[$i] = array();
			$discounts[$i]['product_id'] = $productId;
			$discounts[$i]['customer_group'] = $customerGroup;
			$discounts[$i]['quantity'] = $quantity;
			$discounts[$i]['priority'] = $priority;
			$discounts[$i]['price'] = $price;
			$discounts[$i]['date_start'] = $dateStart;
			$discounts[$i]['date_end'] = $dateEnd;
			$discounts[$i] = $this->moreDiscountCells( $i, $j, $data, $discounts[$i] );
		}
		return $this->storeDiscountsIntoDatabase( $database, $discounts );
	}


	function storeRewardsIntoDatabase( &$database, &$rewards )
	{
		/*$sql = "START TRANSACTION;\n";
		$sql .= "DELETE FROM `".DB_PREFIX."product_reward`;\n";
		$this->multiquery( $database, $sql );*/

		// find the default language id
		$languageId = $this->getDefaultLanguageId($database);

		// find existing customer groups from the database
		$sql = "SELECT `customer_group_id`, `name` FROM `".DB_PREFIX."customer_group_description` WHERE language_id=$languageId";
		$result = $database->query( $sql );
		$maxCustomerGroupId = 0;
		$customerGroups = array();
		foreach ($result->rows as $row) {
			$customerGroupId = $row['customer_group_id'];
			$name = $row['name'];
			if (!isset($customerGroups[$name])) {
				$customerGroups[$name] = $customerGroupId;
			}
			if ($maxCustomerGroupId < $customerGroupId) {
				$maxCustomerGroupId = $customerGroupId;
			}
		}

		// add additional customer groups into the database
		foreach ($rewards as $reward) {
			$name = $reward['customer_group'];
			if (!isset($customerGroups[$name])) {
				$maxCustomerGroupId += 1;
				$sql  = "INSERT INTO `".DB_PREFIX."customer_group` (`customer_group_id`, `approval`, `company_id_display`, `company_id_required`, `tax_id_display`, `tax_id_required`, `sort_order`) VALUES "; 
				$sql .= "($maxCustomerGroupId, 0, 1, 0, 0, 1, 1)";
				$sql .= ";\n";
				$database->query($sql);
				$sql  = "INSERT INTO `".DB_PREFIX."customer_group_description` (`customer_group_id`, `language_id`, `name`, `description`) VALUES ";
				$sql .= "($maxCustomerGroupId, $languageId, '".$database->escape($name)."', '' )"; 
				$sql .= ";\n";
				$database->query($sql);
				$customerGroups[$name] = $maxCustomerGroupId;
			}
		}

		// store product rewards into the database
		$productRewardId = 0;
		$first = TRUE;
		$sql = "INSERT INTO `".DB_PREFIX."product_reward` (`product_reward_id`,`product_id`,`customer_group_id`,`points` ) VALUES "; 
		foreach ($rewards as $reward) {
			$productRewardId += 1;
			$productId = $reward['product_id'];
			$name = $reward['customer_group'];
			$customerGroupId = $customerGroups[$name];
			$points = $reward['points'];
			$sql .= ($first) ? "\n" : ",\n";
			$first = FALSE;
			$sql .= "($productRewardId,$productId,$customerGroupId,$points)";
		}
		if (!$first) {
			$database->query($sql);
		}

		$database->query("COMMIT;");
		return TRUE;
	}


	// hook function for reading additional cells in class extensions
	protected function moreRewardCells( $i, &$j, &$worksheet, $reward ) {
		return $reward;
	}


	function uploadRewards( &$reader, &$database ) 
	{
		$data = $reader->getSheet(7);
		$rewards = array();
		$i = 0;
		$k = $data->getHighestRow();
		$isFirstRow = TRUE;
		for ($i=0; $i<$k; $i+=1) {
			$j= 1;
			if ($isFirstRow) {
				$isFirstRow = FALSE;
				continue;
			}
			$productId = trim($this->getCell($data,$i,$j++));
			if ($productId=="") {
				continue;
			}
			$customerGroup = trim($this->getCell($data,$i,$j++));
			if ($customerGroup=="") {
				continue;
			}
			$points = $this->getCell($data,$i,$j++,'0');
			$rewards[$i] = array();
			$rewards[$i]['product_id'] = $productId;
			$rewards[$i]['customer_group'] = $customerGroup;
			$rewards[$i]['points'] = $points;
			$rewards[$i] = $this->moreRewardCells( $i, $j, $data, $rewards[$i] );
		}
		return $this->storeRewardsIntoDatabase( $database, $rewards );
	}


	function storeAdditionalImagesIntoDatabase( &$database, &$images )
	{
		// start transaction
		/*$sql = "START TRANSACTION;\n";
		
		// delete old additional product images from database
		$sql = "DELETE FROM `".DB_PREFIX."product_image`";
		$database->query( $sql );*/
		
		// store additional images into the database
		$productImageId = 0;
		$first = TRUE;
		$sql = "INSERT INTO `".DB_PREFIX."product_image` (`product_image_id`,`product_id`,`image`,`sort_order` ) VALUES "; 
		foreach ($images as $image) {
			$productImageId += 1;
			$productId = $image['product_id'];
			$imageName = $image['image'];
			$sortOrder = $image['sort_order'];
			$sql .= ($first) ? "\n" : ",\n";
			$first = FALSE;
			$sql .= "($productImageId,$productId,'".$database->escape($imageName)."',$sortOrder)";
		}
		if (!$first) {
			$database->query($sql);
		}

		$database->query("COMMIT;");
		return TRUE;
	}


	// hook function for reading additional cells in class extensions
	protected function moreAdditionalImageCells( $i, &$j, &$worksheet, $image ) {
		return $image;
	}


	function uploadAdditionalImages( &$reader, &$database ) 
	{
		$data = $reader->getSheet(2);
		$images = array();
		$i = 0;
		$k = $data->getHighestRow();
		$isFirstRow = TRUE;
		for ($i=0; $i<$k; $i+=1) {
			$j= 1;
			if ($isFirstRow) {
				$isFirstRow = FALSE;
				continue;
			}
			$productId = trim($this->getCell($data,$i,$j++));
			if ($productId=="") {
				continue;
			}
			$image = $this->getCell($data,$i,$j++,'');
			$sortOrder = $this->getCell($data,$i,$j++,'0');
			$images[$i] = array();
			$images[$i]['product_id'] = $productId;
			$images[$i]['image'] = $image;
			$images[$i]['sort_order'] = $sortOrder;
			$images[$i] = $this->moreAdditionalImageCells( $i, $j, $worksheet, $images[$i] );
		}
		return $this->storeAdditionalImagesIntoDatabase( $database, $images );
	}


	function getCell(&$worksheet,$row,$col,$default_val='') {
		$col -= 1; // we use 1-based, PHPExcel uses 0-based column index
		$row += 1; // we use 0-based, PHPExcel used 1-based row index
		return ($worksheet->cellExistsByColumnAndRow($col,$row)) ? $worksheet->getCellByColumnAndRow($col,$row)->getValue() : $default_val;
	}


	function validateHeading( &$data, &$expected ) {
		$heading = array();
		$k = PHPExcel_Cell::columnIndexFromString( $data->getHighestColumn() );
		if ($k != count($expected)) {
			return FALSE;
		}
		$i = 0;
		for ($j=1; $j <= $k; $j+=1) {
			$heading[] = $this->getCell($data,$i,$j);
		}
		$valid = TRUE;
		for ($i=0; $i < count($expected); $i+=1) {
			if (!isset($heading[$i])) {
				$valid = FALSE;
				break;
			}
			if (strtolower($heading[$i]) != strtolower($expected[$i])) {
				$valid = FALSE;
				break;
			}
		}
		return $valid;
	}


	protected function expectedCategoriesHeading() {
		return array( "category_id", "parent_id", "name", "top", "columns", "sort_order", "image_name", "date_added", "date_modified", "language_id", "seo_keyword", "description", "meta_description", "meta_keywords", "store_ids", "layout", "status\nenabled" );
	}


	function validateCategories( &$reader )
	{
		$expectedCategoriesHeading = $this->expectedCategoriesHeading();
		$data =& $reader->getSheet(0);
		return $this->validateHeading( $data, $expectedCategoriesHeading );
	}


	protected function expectedProductsHeading() {
		return array( "product_id", "name", "categories", "sku", "upc", "ean", "jan", "isbn", "mpn", "location", "quantity", "model", "manufacturer", "image_name", "requires\nshipping", "price", "points", "date_added", "date_modified", "date_available", "weight", "unit", "length", "width", "height", "length\nunit", "status\nenabled", "tax_class_id", "viewed", "language_id", "seo_keyword", "description", "meta_description", "meta_keywords", "stock_status_id", "store_ids", "layout", "related_ids", "tags", "sort_order", "subtract", "minimum" );
	}


	function validateProducts( &$reader )
	{
		$expectedProductsHeading = $this->expectedProductsHeading();
		$data =& $reader->getSheet(1);
		return $this->validateHeading( $data, $expectedProductsHeading );
	}


	protected function expectedAdditionalImagesHeading() {
		return array( "product_id", "image", "sort_order" );
	}


	function validateAdditionalImages( &$reader )
	{
		$expectedAdditionalImagesHeading = expectedAdditionalImagesHeading();
		$data =& $reader->getSheet(2);
		return $this->validateHeading( $data, $expectedAdditionalImagesHeading );
	}


	protected function expectedOptionsHeading() {
		return array( "product_id", "language_id", "option", "type", "value", "image", "required", "quantity", "subtract", "price", "price\nprefix", "points", "points\nprefix", "weight", "weight\nprefix", "sort_order" );
	}


	function validateOptions( &$reader )
	{
		$expectedOptionsHeading = $this->expectedOptionsHeading();
		$data =& $reader->getSheet(3);
		return $this->validateHeading( $data, $expectedOptionsHeading );
	}


	protected function expectedAttributesHeading() {
		return array( "product_id", "language_id", "attribute_group", "attribute_name", "text" );
	}


	function validateAttributes( &$reader )
	{
		$expectedAttributesHeading = $this->expectedAttributesHeading();
		$data =& $reader->getSheet(4);
		return $this->validateHeading( $data, $expectedAttributesHeading );
	}


	protected function expectedSpecialsHeading() {
		return array( "product_id", "customer_group", "priority", "price", "date_start", "date_end" );
	}


	function validateSpecials( &$reader )
	{
		$expectedSpecialsHeading = $this->expectedSpecialsHeading();
		$data =& $reader->getSheet(5);
		return $this->validateHeading( $data, $expectedSpecialsHeading );
	}


	protected function expectedDiscountsHeading() {
		return array( "product_id", "customer_group", "quantity", "priority", "price", "date_start", "date_end" );
	}


	function validateDiscounts( &$reader )
	{
		$expectedDiscountsHeading = $this->expectedDiscountsHeading();
		$data =& $reader->getSheet(6);
		return $this->validateHeading( $data, $expectedDiscountsHeading );
	}


	protected function expectedRewardsHeading() {
		return array( "product_id", "customer_group", "points" );
	}


	function validateRewards( &$reader )
	{
		$expectedRewardsHeading = $this->expectedRewardsHeading();
		$data =& $reader->getSheet(7);
		return $this->validateHeading( $data, $expectedRewardsHeading );
	}


	protected function expectedWorkSheets() {
		return array('Categories','Products','AdditionalImages','Options','Attributes','Specials','Discounts','Rewards');
	}


	function validateUpload( &$reader )
	{
		$expectedWorksheets = $this->expectedWorksheets();
		$sheetNames = $reader->getSheetNames();
		foreach ($expectedWorksheets as $key=>$expectedWorksheet) {
			if (!isset($sheetNames[$key]) || ($sheetNames[$key]!=$expectedWorksheet)) {
				error_log(date('Y-m-d H:i:s - ', time()).$this->language->get( 'error_sheet_count' )."\n",3,DIR_LOGS."error.txt");
				return FALSE;
			}
		}
		if (!$this->validateCategories( $reader )) {
			error_log(date('Y-m-d H:i:s - ', time()).$this->language->get('error_categories_header')."\n",3,DIR_LOGS."error.txt");
			return FALSE;
		}
		if (!$this->validateProducts( $reader )) {
			error_log(date('Y-m-d H:i:s - ', time()).$this->language->get('error_products_header')."\n",3,DIR_LOGS."error.txt");
			return FALSE;
		}
		if (!$this->validateOptions( $reader )) {
			error_log(date('Y-m-d H:i:s - ', time()).$this->language->get('error_options_header')."\n",3,DIR_LOGS."error.txt");
			return FALSE;
		}
		if (!$this->validateAttributes( $reader )) {
			error_log(date('Y-m-d H:i:s - ', time()).$this->language->get('error_attributes_header')."\n",3,DIR_LOGS."error.txt");
			return FALSE;
		}
		if (!$this->validateSpecials( $reader )) {
			error_log(date('Y-m-d H:i:s - ', time()).$this->language->get('error_specials_header')."\n",3,DIR_LOGS."error.txt");
			return FALSE;
		}
		if (!$this->validateDiscounts( $reader )) {
			error_log(date('Y-m-d H:i:s - ', time()).$this->language->get('error_discounts_header')."\n",3,DIR_LOGS."error.txt");
			return FALSE;
		}
		if (!$this->validateRewards( $reader )) {
			error_log(date('Y-m-d H:i:s - ', time()).$this->language->get('error_rewards_header')."\n",3,DIR_LOGS."error.txt");
			return FALSE;
		}
		return TRUE;
	}


	function clearCache() {
		$this->cache->delete('*');
	}


	function upload( $filename ) {
		// we use our own error handler
		global $registry;
		$registry = $this->registry;
		set_error_handler('error_handler_for_export',E_ALL);
		register_shutdown_function('fatal_error_shutdown_handler_for_export');

		try {
			$database =& $this->db;
			$this->session->data['export_nochange'] = 1;

			// we use the PHPExcel package from http://phpexcel.codeplex.com/
			$cwd = getcwd();
			chdir( DIR_SYSTEM.'PHPExcel' );
			require_once( 'Classes/PHPExcel.php' );
			chdir( $cwd );

			// parse uploaded spreadsheet file
			$inputFileType = PHPExcel_IOFactory::identify($filename);
			$objReader = PHPExcel_IOFactory::createReader($inputFileType);
			$objReader->setReadDataOnly(true);
			$reader = $objReader->load($filename);

			// read the various worksheets and load them to the database
			$ok = $this->validateUpload( $reader );
			if (!$ok) {
				return FALSE;
			}
			$this->clearCache();
			$this->session->data['export_nochange'] = 0;
			$ok = $this->uploadCategories( $reader, $database );
			if (!$ok) {
				return FALSE;
			}
			$ok = $this->uploadProducts( $reader, $database );
			if (!$ok) {
				return FALSE;
			}
			$ok = $this->uploadAdditionalImages( $reader, $database );
			if (!$ok) {
				return FALSE;
			}
			$ok = $this->uploadOptions( $reader, $database );
			if (!$ok) {
				return FALSE;
			}
			$ok = $this->uploadAttributes( $reader, $database );
			if (!$ok) {
				return FALSE;
			}
			$ok = $this->uploadSpecials( $reader, $database );
			if (!$ok) {
				return FALSE;
			}
			$ok = $this->uploadDiscounts( $reader, $database );
			if (!$ok) {
				return FALSE;
			}
			$ok = $this->uploadRewards( $reader, $database );
			if (!$ok) {
				return FALSE;
			}
			return $ok;
		} catch (Exception $e) {
			$errstr = $e->getMessage();
			$errline = $e->getLine();
			$errfile = $e->getFile();
			$errno = $e->getCode();
			$this->session->data['export_error'] = array( 'errstr'=>$errstr, 'errno'=>$errno, 'errfile'=>$errfile, 'errline'=>$errline );
			if ($this->config->get('config_error_log')) {
				$this->log->write('PHP ' . get_class($e) . ':  ' . $errstr . ' in ' . $errfile . ' on line ' . $errline);
			}
			return FALSE;
		}
	}



	function getStoreIdsForCategories( &$database ) {
		$sql =  "SELECT category_id, store_id FROM `".DB_PREFIX."category_to_store` cs;";
		$storeIds = array();
		$result = $database->query( $sql );
		foreach ($result->rows as $row) {
			$categoryId = $row['category_id'];
			$storeId = $row['store_id'];
			if (!isset($storeIds[$categoryId])) {
				$storeIds[$categoryId] = array();
			}
			if (!in_array($storeId,$storeIds[$categoryId])) {
				$storeIds[$categoryId][] = $storeId;
			}
		}
		return $storeIds;
	}


	function getLayoutsForCategories( &$database ) {
		$sql  = "SELECT cl.*, l.name FROM `".DB_PREFIX."category_to_layout` cl ";
		$sql .= "LEFT JOIN `".DB_PREFIX."layout` l ON cl.layout_id = l.layout_id ";
		$sql .= "ORDER BY cl.category_id, cl.store_id;";
		$result = $database->query( $sql );
		$layouts = array();
		foreach ($result->rows as $row) {
			$categoryId = $row['category_id'];
			$storeId = $row['store_id'];
			$name = $row['name'];
			if (!isset($layouts[$categoryId])) {
				$layouts[$categoryId] = array();
			}
			$layouts[$categoryId][$storeId] = $name;
		}
		return $layouts;
	}


	protected function setCell( &$worksheet, $row/*1-based*/, $col/*0-based*/, $val, $style=NULL ) {
		$worksheet->setCellValueByColumnAndRow( $col, $row, $val );
		if ($style) {
			$worksheet->getStyleByColumnAndRow($col,$row)->applyFromArray( $style );
		}
	}


	protected function getCategories( &$database, $languageId ) {
		$query  = "SELECT c.* , cd.*, ua.keyword FROM `".DB_PREFIX."category` c ";
		$query .= "INNER JOIN `".DB_PREFIX."category_description` cd ON cd.category_id = c.category_id ";
		$query .= " AND cd.language_id=$languageId ";
		$query .= "LEFT JOIN `".DB_PREFIX."url_alias` ua ON ua.query=CONCAT('category_id=',c.category_id) ";
		$query .= "ORDER BY c.`parent_id`, `sort_order`, c.`category_id`;";
		$result = $database->query( $query );
		return $result->rows;
	}


	protected function populateCategoriesWorksheet( &$worksheet, &$database, $languageId, &$boxFormat, &$textFormat ) {
		// Set the column widths
		$j = 0;
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(strlen('category_id')+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(strlen('parent_id')+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('name'),32)+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('top'),5)+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(strlen('columns')+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(strlen('sort_order')+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('image_name'),12)+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('date_added'),19)+1,$textFormat);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('date_modified'),19)+1,$textFormat);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('language_id'),2)+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('seo_keyword'),16)+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('description'),32)+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('meta_description'),32)+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('meta_keywords'),32)+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('store_ids'),16)+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('layout'),16)+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('status'),5)+1,$textFormat);
		
		// The heading row
		$i = 1;
		$j = 0;
		$this->setCell( $worksheet, $i, $j++, 'category_id', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'parent_id', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'name', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'top', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'columns', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'sort_order', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'image_name', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'date_added', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'date_modified', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'language_id', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'seo_keyword', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'description', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'meta_description', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'meta_keywords', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'store_ids', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'layout', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, "status\nenabled", $boxFormat );
		$worksheet->getRowDimension($i)->setRowHeight(30);
		
		// The actual categories data
		$i += 1;
		$j = 0;
		$storeIds = $this->getStoreIdsForCategories( $database );
		$layouts = $this->getLayoutsForCategories( $database );
		$categories = $this->getCategories( $database, $languageId );
		foreach ($categories as $row) {
			$worksheet->getRowDimension($i)->setRowHeight(26);
			$this->setCell( $worksheet, $i, $j++, $row['category_id'] );
			$this->setCell( $worksheet, $i, $j++, $row['parent_id'] );
			$this->setCell( $worksheet, $i, $j++, html_entity_decode($row['name'],ENT_QUOTES,'UTF-8') );
			$this->setCell( $worksheet, $i, $j++, ($row['top']==0) ? "false" : "true", $textFormat );
			$this->setCell( $worksheet, $i, $j++, $row['column'] );
			$this->setCell( $worksheet, $i, $j++, $row['sort_order'] );
			$this->setCell( $worksheet, $i, $j++, $row['image'] );
			$this->setCell( $worksheet, $i, $j++, $row['date_added'], $textFormat );
			$this->setCell( $worksheet, $i, $j++, $row['date_modified'], $textFormat );
			$this->setCell( $worksheet, $i, $j++, $row['language_id'] );
			$this->setCell( $worksheet, $i, $j++, ($row['keyword']) ? $row['keyword'] : '' );
			$this->setCell( $worksheet, $i, $j++, html_entity_decode($row['description'],ENT_QUOTES,'UTF-8') );
			$this->setCell( $worksheet, $i, $j++, html_entity_decode($row['meta_description'],ENT_QUOTES,'UTF-8') );
			$this->setCell( $worksheet, $i, $j++, html_entity_decode($row['meta_keyword'],ENT_QUOTES,'UTF-8') );
			$storeIdList = '';
			$categoryId = $row['category_id'];
			if (isset($storeIds[$categoryId])) {
				foreach ($storeIds[$categoryId] as $storeId) {
					$storeIdList .= ($storeIdList=='') ? $storeId : ','.$storeId;
				}
			}
			$this->setCell( $worksheet, $i, $j++, $storeIdList, $textFormat );
			$layoutList = '';
			if (isset($layouts[$categoryId])) {
				foreach ($layouts[$categoryId] as $storeId => $name) {
					$layoutList .= ($layoutList=='') ? $storeId.':'.$name : ','.$storeId.':'.$name;
				}
			}
			$this->setCell( $worksheet, $i, $j++, $layoutList, $textFormat );
			$this->setCell( $worksheet, $i, $j++, ($row['status']==0) ? "false" : "true", $textFormat );
			$i += 1;
			$j = 0;
		}
	}


	protected function getStoreIdsForProducts( &$database ) {
		$sql =  "SELECT product_id, store_id FROM `".DB_PREFIX."product_to_store` ps;";
		$storeIds = array();
		$result = $database->query( $sql );
		foreach ($result->rows as $row) {
			$productId = $row['product_id'];
			$storeId = $row['store_id'];
			if (!isset($storeIds[$productId])) {
				$storeIds[$productId] = array();
			}
			if (!in_array($storeId,$storeIds[$productId])) {
				$storeIds[$productId][] = $storeId;
			}
		}
		return $storeIds;
	}


	protected function getLayoutsForProducts( &$database ) {
		$sql  = "SELECT pl.*, l.name FROM `".DB_PREFIX."product_to_layout` pl ";
		$sql .= "LEFT JOIN `".DB_PREFIX."layout` l ON pl.layout_id = l.layout_id ";
		$sql .= "ORDER BY pl.product_id, pl.store_id;";
		$result = $database->query( $sql );
		$layouts = array();
		foreach ($result->rows as $row) {
			$productId = $row['product_id'];
			$storeId = $row['store_id'];
			$name = $row['name'];
			if (!isset($layouts[$productId])) {
				$layouts[$productId] = array();
			}
			$layouts[$productId][$storeId] = $name;
		}
		return $layouts;
	}


	protected function getProducts( &$database, $languageId ) {
		$query  = "SELECT ";
		$query .= "  p.product_id,";
		$query .= "  pd.name,";
		$query .= "  GROUP_CONCAT( DISTINCT CAST(pc.category_id AS CHAR(11)) SEPARATOR \",\" ) AS categories,";
		$query .= "  p.sku,";
		$query .= "  p.upc,";
		$query .= "  p.ean,";
		$query .= "  p.jan,";
		$query .= "  p.isbn,";
		$query .= "  p.mpn,";
		$query .= "  p.location,";
		$query .= "  p.quantity,";
		$query .= "  p.model,";
		$query .= "  m.name AS manufacturer,";
		$query .= "  p.image AS image_name,";
		$query .= "  p.shipping,";
		$query .= "  p.price,";
		$query .= "  p.points,";
		$query .= "  p.date_added,";
		$query .= "  p.date_modified,";
		$query .= "  p.date_available,";
		$query .= "  p.weight,";
		$query .= "  wc.unit,";
		$query .= "  p.length,";
		$query .= "  p.width,";
		$query .= "  p.height,";
		$query .= "  p.status,";
		$query .= "  p.tax_class_id,";
		$query .= "  p.viewed,";
		$query .= "  p.sort_order,";
		$query .= "  pd.language_id,";
		$query .= "  ua.keyword,";
		$query .= "  pd.description, ";
		$query .= "  pd.meta_description, ";
		$query .= "  pd.meta_keyword, ";
		$query .= "  pd.tag, ";
		$query .= "  p.stock_status_id, ";
		$query .= "  mc.unit AS length_unit, ";
		$query .= "  p.subtract, ";
		$query .= "  p.minimum, ";
		$query .= "  GROUP_CONCAT( DISTINCT CAST(pr.related_id AS CHAR(11)) SEPARATOR \",\" ) AS related ";
		$query .= "FROM `".DB_PREFIX."product` p ";
		$query .= "LEFT JOIN `".DB_PREFIX."product_description` pd ON p.product_id=pd.product_id ";
		$query .= "  AND pd.language_id=$languageId ";
		$query .= "LEFT JOIN `".DB_PREFIX."product_to_category` pc ON p.product_id=pc.product_id ";
		$query .= "LEFT JOIN `".DB_PREFIX."url_alias` ua ON ua.query=CONCAT('product_id=',p.product_id) ";
		$query .= "LEFT JOIN `".DB_PREFIX."manufacturer` m ON m.manufacturer_id = p.manufacturer_id ";
		$query .= "LEFT JOIN `".DB_PREFIX."weight_class_description` wc ON wc.weight_class_id = p.weight_class_id ";
		$query .= "  AND wc.language_id=$languageId ";
		$query .= "LEFT JOIN `".DB_PREFIX."length_class_description` mc ON mc.length_class_id=p.length_class_id ";
		$query .= "  AND mc.language_id=$languageId ";
		$query .= "LEFT JOIN `".DB_PREFIX."product_related` pr ON pr.product_id=p.product_id ";
		$query .= "GROUP BY p.product_id ";
		$query .= "ORDER BY p.product_id, pc.category_id; ";
		$result = $database->query( $query );
		return $result->rows;
	}


	function populateProductsWorksheet( &$worksheet, &$database, $languageId, &$priceFormat, &$boxFormat, &$weightFormat, &$textFormat )
	{
		// Set the column widths
		$j = 0;
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('product_id'),4)+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('name'),30)+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('categories'),12)+1,$textFormat);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('sku'),10)+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('upc'),12)+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('ean'),14)+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('jan'),13)+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('isbn'),13)+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('mpn'),15)+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('location'),10)+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('quantity'),4)+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('model'),8)+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('manufacturer'),10)+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('image_name'),12)+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('shipping'),5)+1,$textFormat);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('price'),10)+1,$priceFormat);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('points'),5)+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('date_added'),19)+1,$textFormat);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('date_modified'),19)+1,$textFormat);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('date_available'),10)+1,$textFormat);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('weight'),6)+1,$weightFormat);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('unit'),3)+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('length'),8)+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('width'),8)+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('height'),8)+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('length'),3)+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('status'),5)+1,$textFormat);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('tax_class_id'),2)+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('viewed'),5)+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('language_id'),2)+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('seo_keyword'),16)+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('description'),32)+1,$textFormat);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('meta_description'),32)+1,$textFormat);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('meta_keywords'),32)+1,$textFormat);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('stock_status_id'),3)+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('store_ids'),16)+1,$textFormat);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('layout'),16)+1,$textFormat);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('related_ids'),16)+1,$textFormat);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('tags'),32)+1,$textFormat);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('sort_order'),8)+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('subtract'),5)+1,$textFormat);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('minimum'),8)+1);

		// The product headings row
		$i = 1;
		$j = 0;
		$this->setCell( $worksheet, $i, $j++, 'product_id', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'name', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'categories', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'sku', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'upc', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'ean', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'jan', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'isbn', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'mpn', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'location', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'quantity', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'model', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'manufacturer', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'image_name', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, "requires\nshipping", $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'price', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'points', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'date_added', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'date_modified', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'date_available', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'weight', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'unit', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'length', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'width', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'height', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, "length\nunit", $boxFormat );
		$this->setCell( $worksheet, $i, $j++, "status\nenabled", $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'tax_class_id', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'viewed', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'language_id', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'seo_keyword', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'description', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'meta_description', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'meta_keywords', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'stock_status_id', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'store_ids', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'layout', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'related_ids', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'tags', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'sort_order', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, "subtract", $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'minimum', $boxFormat );
		$worksheet->getRowDimension($i)->setRowHeight(30);
		
		// The actual products data
		$i += 1;
		$j = 0;
		$storeIds = $this->getStoreIdsForProducts( $database );
		$layouts = $this->getLayoutsForProducts( $database );
		$products = $this->getProducts( $database, $languageId );
		foreach ($products as $row) {
			$worksheet->getRowDimension($i)->setRowHeight(26);
			$productId = $row['product_id'];
			$this->setCell( $worksheet, $i, $j++, $productId );
			$this->setCell( $worksheet, $i, $j++, html_entity_decode($row['name'],ENT_QUOTES,'UTF-8') );
			$this->setCell( $worksheet, $i, $j++, $row['categories'], $textFormat );
			$this->setCell( $worksheet, $i, $j++, $row['sku'] );
			$this->setCell( $worksheet, $i, $j++, $row['upc'] );
			$this->setCell( $worksheet, $i, $j++, $row['ean'] );
			$this->setCell( $worksheet, $i, $j++, $row['jan'] );
			$this->setCell( $worksheet, $i, $j++, $row['isbn'] );
			$this->setCell( $worksheet, $i, $j++, $row['mpn'] );
			$this->setCell( $worksheet, $i, $j++, $row['location'] );
			$this->setCell( $worksheet, $i, $j++, $row['quantity'] );
			$this->setCell( $worksheet, $i, $j++, $row['model'] );
			$this->setCell( $worksheet, $i, $j++, $row['manufacturer'] );
			$this->setCell( $worksheet, $i, $j++, $row['image_name'] );
			$this->setCell( $worksheet, $i, $j++, ($row['shipping']==0) ? "no" : "yes", $textFormat );
			$this->setCell( $worksheet, $i, $j++, $row['price'], $priceFormat );
			$this->setCell( $worksheet, $i, $j++, $row['points'] );
			$this->setCell( $worksheet, $i, $j++, $row['date_added'], $textFormat );
			$this->setCell( $worksheet, $i, $j++, $row['date_modified'], $textFormat );
			$this->setCell( $worksheet, $i, $j++, $row['date_available'], $textFormat );
			$this->setCell( $worksheet, $i, $j++, $row['weight'], $weightFormat );
			$this->setCell( $worksheet, $i, $j++, $row['unit'] );
			$this->setCell( $worksheet, $i, $j++, $row['length'] );
			$this->setCell( $worksheet, $i, $j++, $row['width'] );
			$this->setCell( $worksheet, $i, $j++, $row['height'] );
			$this->setCell( $worksheet, $i, $j++, $row['length_unit'] );
			$this->setCell( $worksheet, $i, $j++, ($row['status']==0) ? "false" : "true", $textFormat );
			$this->setCell( $worksheet, $i, $j++, $row['tax_class_id'] );
			$this->setCell( $worksheet, $i, $j++, $row['viewed'] );
			$this->setCell( $worksheet, $i, $j++, $row['language_id'] );
			$this->setCell( $worksheet, $i, $j++, ($row['keyword']) ? $row['keyword'] : '' );
			$this->setCell( $worksheet, $i, $j++, html_entity_decode($row['description'],ENT_QUOTES,'UTF-8'), $textFormat, TRUE );
			$this->setCell( $worksheet, $i, $j++, html_entity_decode($row['meta_description'],ENT_QUOTES,'UTF-8'), $textFormat );
			$this->setCell( $worksheet, $i, $j++, html_entity_decode($row['meta_keyword'],ENT_QUOTES,'UTF-8'), $textFormat );
			$this->setCell( $worksheet, $i, $j++, $row['stock_status_id'] );
			$storeIdList = '';
			if (isset($storeIds[$productId])) {
				foreach ($storeIds[$productId] as $storeId) {
					$storeIdList .= ($storeIdList=='') ? $storeId : ','.$storeId;
				}
			}
			$this->setCell( $worksheet, $i, $j++, $storeIdList, $textFormat );
			$layoutList = '';
			if (isset($layouts[$productId])) {
				foreach ($layouts[$productId] as $storeId => $name) {
					$layoutList .= ($layoutList=='') ? $storeId.':'.$name : ','.$storeId.':'.$name;
				}
			}
			$this->setCell( $worksheet, $i, $j++, $layoutList, $textFormat );
			$this->setCell( $worksheet, $i, $j++, $row['related'], $textFormat );
			$this->setCell( $worksheet, $i, $j++, ($row['tag']) ? $row['tag'] : '' );
			$this->setCell( $worksheet, $i, $j++, $row['sort_order'] );
			$this->setCell( $worksheet, $i, $j++, ($row['subtract']==0) ? "false" : "true", $textFormat );
			$this->setCell( $worksheet, $i, $j++, $row['minimum'] );
			$i += 1;
			$j = 0;
		}
	}


	protected function getAdditionalImages( &$database ) {
		$query  = "SELECT `product_id`, `image`, `sort_order` ";
		$query .= "FROM `".DB_PREFIX."product_image` pi ";
		$query .= "ORDER BY pi.`product_id`, pi.`sort_order`, pi.`image`;";
		$result = $database->query( $query );
		return $result->rows;
	}


	protected function populateAdditionalImagesWorksheet( &$worksheet, &$database, &$boxFormat )
	{
		// Set the column widths
		$j = 0;
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('product_id'),4)+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('image'),30)+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('sort_order'),5)+1);
		
		// The additional images headings row
		$i = 1;
		$j = 0;
		$this->setCell( $worksheet, $i, $j++, 'product_id', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'image', $boxFormat  );
		$this->setCell( $worksheet, $i, $j++, 'sort_order', $boxFormat  );
		$worksheet->getRowDimension($i)->setRowHeight(30);
		
		// The actual additional images data
		$i += 1;
		$j = 0;
		$addtionalImages = $this->getAdditionalImages( $database );
		foreach ($addtionalImages as $row) {
			$worksheet->getRowDimension($i)->setRowHeight(13);
			$this->setCell( $worksheet, $i, $j++, $row['product_id'] );
			$this->setCell( $worksheet, $i, $j++, $row['image'] );
			$this->setCell( $worksheet, $i, $j++, $row['sort_order'] );
			$i += 1;
			$j = 0;
		}
	}


	protected function getOptions( &$database, $languageId ) {
		$query  = "SELECT po.product_id,";
		$query .= "  po.option_id,";
		$query .= "  po.option_value AS default_value,";
		$query .= "  po.required,";
		$query .= "  pov.option_value_id,";
		$query .= "  pov.quantity,";
		$query .= "  pov.subtract,";
		$query .= "  pov.price,";
		$query .= "  pov.price_prefix,";
		$query .= "  pov.points,";
		$query .= "  pov.points_prefix,";
		$query .= "  pov.weight,";
		$query .= "  pov.weight_prefix,";
		$query .= "  ovd.name AS option_value,";
		$query .= "  ov.image,";
		$query .= "  ov.sort_order,";
		$query .= "  od.name AS option_name,";
		$query .= "  o.type ";
		$query .= "FROM `".DB_PREFIX."product_option` po ";
		$query .= "LEFT JOIN `".DB_PREFIX."option` o ON o.option_id=po.option_id ";
		$query .= "LEFT JOIN `".DB_PREFIX."product_option_value` pov ON pov.product_option_id = po.product_option_id ";
		$query .= "LEFT JOIN `".DB_PREFIX."option_value` ov ON ov.option_value_id=pov.option_value_id ";
		$query .= "LEFT JOIN `".DB_PREFIX."option_value_description` ovd ON ovd.option_value_id=ov.option_value_id AND ovd.language_id=$languageId ";
		$query .= "LEFT JOIN `".DB_PREFIX."option_description` od ON od.option_id=o.option_id AND od.language_id=$languageId ";
		$query .= "ORDER BY po.product_id, po.option_id, pov.option_value_id;";
		$result = $database->query( $query );
		return $result->rows;
	}


	protected function populateOptionsWorksheet( &$worksheet, &$database, $languageId, &$priceFormat, &$boxFormat, &$weightFormat, $textFormat )
	{
		// Set the column widths
		$j = 0;
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('product_id'),4)+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('language_id'),2)+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('option'),30)+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('type'),10)+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('value'),30)+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('image'),12)+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('required'),5)+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('quantity'),4)+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('subtract'),5)+1,$textFormat);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('price'),10)+1,$priceFormat);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('price'),5)+1,$textFormat);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('points'),10)+1,$priceFormat);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('points'),5)+1,$textFormat);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('weight'),10)+1,$priceFormat);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('weight'),5)+1,$textFormat);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('sort_order'),5)+1);
		
		// The options headings row
		$i = 1;
		$j = 0;
		$this->setCell( $worksheet, $i, $j++, 'product_id', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'language_id', $boxFormat  );
		$this->setCell( $worksheet, $i, $j++, 'option', $boxFormat  );
		$this->setCell( $worksheet, $i, $j++, 'type', $boxFormat  );
		$this->setCell( $worksheet, $i, $j++, 'value', $boxFormat  );
		$this->setCell( $worksheet, $i, $j++, 'image', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'required', $boxFormat  );
		$this->setCell( $worksheet, $i, $j++, 'quantity', $boxFormat  );
		$this->setCell( $worksheet, $i, $j++, 'subtract', $boxFormat  );
		$this->setCell( $worksheet, $i, $j++, 'price', $boxFormat  );
		$this->setCell( $worksheet, $i, $j++, "price\nprefix", $boxFormat  );
		$this->setCell( $worksheet, $i, $j++, 'points', $boxFormat  );
		$this->setCell( $worksheet, $i, $j++, "points\nprefix", $boxFormat  );
		$this->setCell( $worksheet, $i, $j++, 'weight', $boxFormat  );
		$this->setCell( $worksheet, $i, $j++, "weight\nprefix", $boxFormat  );
		$this->setCell( $worksheet, $i, $j++, 'sort_order', $boxFormat  );
		$worksheet->getRowDimension($i)->setRowHeight(30);
		
		// The actual options data
		$i += 1;
		$j = 0;
		$options = $this->getOptions( $database, $languageId );
		foreach ($options as $row) {
			$worksheet->getRowDimension($i)->setRowHeight(13);
			$this->setCell( $worksheet, $i, $j++, $row['product_id'] );
			$this->setCell( $worksheet, $i, $j++, $languageId );
			$this->setCell( $worksheet, $i, $j++, $row['option_name'] );
			$this->setCell( $worksheet, $i, $j++, $row['type'] );
			$this->setCell( $worksheet, $i, $j++, ($row['default_value']) ? $row['default_value'] : $row['option_value'] );
			$this->setCell( $worksheet, $i, $j++, $row['image'] );
			$this->setCell( $worksheet, $i, $j++, ($row['required']==0) ? "false" : "true", $textFormat );
			$this->setCell( $worksheet, $i, $j++, $row['quantity'] );
			if (is_null($row['option_value_id'])) {
				$subtract = '';
			} else {
				$subtract = ($row['subtract']==0) ? "false" : "true";
			}
			$this->setCell( $worksheet, $i, $j++, $subtract, $textFormat );
			$this->setCell( $worksheet, $i, $j++, $row['price'], $priceFormat );
			$this->setCell( $worksheet, $i, $j++, $row['price_prefix'], $textFormat );
			$this->setCell( $worksheet, $i, $j++, $row['points'] );
			$this->setCell( $worksheet, $i, $j++, $row['points_prefix'], $textFormat );
			$this->setCell( $worksheet, $i, $j++, $row['weight'], $weightFormat );
			$this->setCell( $worksheet, $i, $j++, $row['weight_prefix'], $textFormat );
			$this->setCell( $worksheet, $i, $j++, $row['sort_order'] );
			$i += 1;
			$j = 0;
		}
	}


	protected function getAttributes( &$database, $languageId ) {
		$query  = "SELECT pa.*, ag.attribute_group_id, ag.sort_order AS attribute_group_sort_order, agd.name AS attribute_group, a.attribute_id, a.sort_order, ad.name AS attribute_name ";
		$query .= "FROM `".DB_PREFIX."product_attribute` pa ";
		$query .= "LEFT JOIN `".DB_PREFIX."attribute` a ON a.attribute_id=pa.attribute_id ";
		$query .= "LEFT JOIN `".DB_PREFIX."attribute_description` ad ON ad.attribute_id=a.attribute_id AND ad.language_id=$languageId ";
		$query .= "INNER JOIN `".DB_PREFIX."attribute_group` ag ON ag.attribute_group_id=a.attribute_group_id ";
		$query .= "LEFT JOIN `".DB_PREFIX."attribute_group_description` agd ON agd.attribute_group_id=a.attribute_group_id AND agd.language_id=$languageId ";
		$query .= "WHERE pa.language_id=$languageId ";
		$query .= "ORDER BY pa.product_id, attribute_group_sort_order, ag.attribute_group_id, a.sort_order, a.attribute_id;";
		$result = $database->query( $query );
		return $result->rows;
	}


	protected function populateAttributesWorksheet( &$worksheet, &$database, $languageId, &$boxFormat, $textFormat )
	{
		// Set the column widths
		$j = 0;
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('product_id'),4)+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('language_id'),2)+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('attribute_group'),30)+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('attribute_name'),30)+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('text'),30)+1);
		
		// The attributes headings row
		$i = 1;
		$j = 0;
		$this->setCell( $worksheet, $i, $j++, 'product_id', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'language_id', $boxFormat  );
		$this->setCell( $worksheet, $i, $j++, 'attribute_group', $boxFormat  );
		$this->setCell( $worksheet, $i, $j++, 'attribute_name', $boxFormat  );
		$this->setCell( $worksheet, $i, $j++, 'text', $boxFormat  );
		$worksheet->getRowDimension($i)->setRowHeight(30);
		
		// The actual attributes data
		$i += 1;
		$j = 0;
		$attributes = $this->getAttributes( $database, $languageId );
		foreach ($attributes as $row) {
			$worksheet->getRowDimension($i)->setRowHeight(13);
			$this->setCell( $worksheet, $i, $j++, $row['product_id'] );
			$this->setCell( $worksheet, $i, $j++, $languageId );
			$this->setCell( $worksheet, $i, $j++, $row['attribute_group'] );
			$this->setCell( $worksheet, $i, $j++, $row['attribute_name'] );
			$this->setCell( $worksheet, $i, $j++, $row['text'] );
			$i += 1;
			$j = 0;
		}
	}


	protected function getSpecials( &$database, $languageId ) {
		$query  = "SELECT ps.*, cgd.name FROM `".DB_PREFIX."product_special` ps ";
		$query .= "LEFT JOIN `".DB_PREFIX."customer_group_description` cgd ON cgd.customer_group_id=ps.customer_group_id ";
		$query .= "  AND cgd.language_id=$languageId ";
		$query .= "ORDER BY ps.product_id, cgd.name";
		$result = $database->query( $query );
		return $result->rows;
	}


	protected function populateSpecialsWorksheet( &$worksheet, &$database, $languageId, &$priceFormat, &$boxFormat, &$textFormat )
	{
		// Set the column widths
		$j = 0;
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(strlen('product_id')+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(strlen('customer_group')+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(strlen('priority')+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('price'),10)+1,$priceFormat);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('date_start'),19)+1,$textFormat);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('date_end'),19)+1,$textFormat);
		
		// The heading row
		$i = 1;
		$j = 0;
		$this->setCell( $worksheet, $i, $j++, 'product_id', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'customer_group', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'priority', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'price', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'date_start', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'date_end', $boxFormat );
		$worksheet->getRowDimension($i)->setRowHeight(30);
		
		// The actual product specials data
		$i += 1;
		$j = 0;
		$specials = $this->getSpecials( $database, $languageId );
		foreach ($specials as $row) {
			$worksheet->getRowDimension($i)->setRowHeight(13);
			$this->setCell( $worksheet, $i, $j++, $row['product_id'] );
			$this->setCell( $worksheet, $i, $j++, $row['name'] );
			$this->setCell( $worksheet, $i, $j++, $row['priority'] );
			$this->setCell( $worksheet, $i, $j++, $row['price'], $priceFormat );
			$this->setCell( $worksheet, $i, $j++, $row['date_start'], $textFormat );
			$this->setCell( $worksheet, $i, $j++, $row['date_end'], $textFormat );
			$i += 1;
			$j = 0;
		}
	}


	protected function getDiscounts( &$database, $languageId ) {
		$query  = "SELECT pd.*, cgd.name FROM `".DB_PREFIX."product_discount` pd ";
		$query .= "LEFT JOIN `".DB_PREFIX."customer_group_description` cgd ON cgd.customer_group_id=pd.customer_group_id ";
		$query .= "  AND cgd.language_id=$languageId ";
		$query .= "ORDER BY pd.product_id, cgd.name";
		$result = $database->query( $query );
		return $result->rows;
	}


	protected function populateDiscountsWorksheet( &$worksheet, &$database, $languageId, &$priceFormat, &$boxFormat, &$textFormat )
	{
		// Set the column widths
		$j = 0;
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(strlen('product_id')+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(strlen('customer_group')+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(strlen('quantity')+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(strlen('priority')+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('price'),10)+1,$priceFormat);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('date_start'),19)+1,$textFormat);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('date_end'),19)+1,$textFormat);
		
		// The heading row
		$i = 1;
		$j = 0;
		$this->setCell( $worksheet, $i, $j++, 'product_id', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'customer_group', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'quantity', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'priority', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'price', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'date_start', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'date_end', $boxFormat );
		$worksheet->getRowDimension($i)->setRowHeight(30);
		
		// The actual product discounts data
		$i += 1;
		$j = 0;
		$discounts = $this->getDiscounts( $database, $languageId );
		foreach ($discounts as $row) {
			$worksheet->getRowDimension($i)->setRowHeight(13);
			$this->setCell( $worksheet, $i, $j++, $row['product_id'] );
			$this->setCell( $worksheet, $i, $j++, $row['name'] );
			$this->setCell( $worksheet, $i, $j++, $row['quantity'] );
			$this->setCell( $worksheet, $i, $j++, $row['priority'] );
			$this->setCell( $worksheet, $i, $j++, $row['price'], $priceFormat );
			$this->setCell( $worksheet, $i, $j++, $row['date_start'], $textFormat );
			$this->setCell( $worksheet, $i, $j++, $row['date_end'], $textFormat );
			$i += 1;
			$j = 0;
		}
	}


	protected function getRewards( &$database, $languageId ) {
		$query  = "SELECT pr.*, cgd.name FROM `".DB_PREFIX."product_reward` pr ";
		$query .= "LEFT JOIN `".DB_PREFIX."customer_group_description` cgd ON cgd.customer_group_id=pr.customer_group_id ";
		$query .= "  AND cgd.language_id=$languageId ";
		$query .= "ORDER BY pr.product_id, cgd.name";
		$result = $database->query( $query );
		return $result->rows;
	}


	protected function populateRewardsWorksheet( &$worksheet, &$database, $languageId, &$boxFormat )
	{
		// Set the column widths
		$j = 0;
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(strlen('product_id')+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(strlen('customer_group')+1);
		$worksheet->getColumnDimensionByColumn($j++)->setWidth(strlen('points')+1);
		
		// The heading row
		$i = 1;
		$j = 0;
		$this->setCell( $worksheet, $i, $j++, 'product_id', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'customer_group', $boxFormat );
		$this->setCell( $worksheet, $i, $j++, 'points', $boxFormat );
		$worksheet->getRowDimension($i)->setRowHeight(30);
		
		// The actual product rewards data
		$i += 1;
		$j = 0;
		$rewards = $this->getRewards( $database, $languageId );
		foreach ($rewards as $row) {
			$worksheet->getRowDimension($i)->setRowHeight(13);
			$this->setCell( $worksheet, $i, $j++, $row['product_id'] );
			$this->setCell( $worksheet, $i, $j++, $row['name'] );
			$this->setCell( $worksheet, $i, $j++, $row['points'] );
			$i += 1;
			$j = 0;
		}
	}


	protected function clearSpreadsheetCache() {
		$files = glob(DIR_CACHE . 'Spreadsheet_Excel_Writer' . '*');
		
		if ($files) {
			foreach ($files as $file) {
				if (file_exists($file)) {
					@unlink($file);
					clearstatcache();
				}
			}
		}
	}


	function download() {
		// we use our own error handler
		global $registry;
		$registry = $this->registry;
		set_error_handler('error_handler_for_export',E_ALL);
		register_shutdown_function('fatal_error_shutdown_handler_for_export');

		// we use the PHPExcel package from http://phpexcel.codeplex.com/
		$cwd = getcwd();
		chdir( DIR_SYSTEM.'PHPExcel' );
		require_once( 'Classes/PHPExcel.php' );
		chdir( $cwd );

		try {
			// set appropriate timeout limit
			set_time_limit( 1800 );

			$database =& $this->db;
			$languageId = $this->getDefaultLanguageId($database);

			// create a new workbook
			$workbook = new PHPExcel();

			// set default font name and size
			$workbook->getDefaultStyle()->getFont()->setName('Arial');
			$workbook->getDefaultStyle()->getFont()->setSize(10);
			$workbook->getDefaultStyle()->getAlignment()->setIndent(1);

			// pre-define some commonly used styles
			$boxFormat = array(
				'font' => array(
					'name' => 'Arial',
					'size' => '10',
				),
				'alignment' => array(
					'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
					'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
					'wrap'       => false,
					'indent'     => 0
				)
			);
			$textFormat = array(
				'font' => array(
					'name' => 'Arial',
					'size' => '10',
				),
				'numberformat' => array(
					'code' => PHPExcel_Style_NumberFormat::FORMAT_TEXT
				),
				'alignment' => array(
					'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
					'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
					'wrap'       => false,
					'indent'     => 0
				)
			);
			$priceFormat = array(
				'font' => array(
					'name' => 'Arial',
					'size' => '10',
				),
				'numberformat' => array(
					'code' => '######0.00'
				),
				'alignment' => array(
					'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
					'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
					'wrap'       => false,
					'indent'     => 0
				)
			);
			$weightFormat = array(
				'font' => array(
					'name' => 'Arial',
					'size' => '10',
				),
				'numberformat' => array(
					'code' => '##0.00'
				),
				'alignment' => array(
					'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
					'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
					'wrap'       => false,
					'indent'     => 0
				)
			);

			// creating the Categories worksheet
			$workbook->setActiveSheetIndex(0);
			$worksheet = $workbook->getActiveSheet();
			$worksheet->setTitle( 'Categories' );
			$this->populateCategoriesWorksheet( $worksheet, $database, $languageId, $boxFormat, $textFormat );
			$worksheet->freezePaneByColumnAndRow( 1, 2 );

			// creating the Products worksheet
			$workbook->createSheet();
			$workbook->setActiveSheetIndex(1);
			$worksheet = $workbook->getActiveSheet();
			$worksheet->setTitle( 'Products' );
			$this->populateProductsWorksheet( $worksheet, $database, $languageId, $priceFormat, $boxFormat, $weightFormat, $textFormat );
			$worksheet->freezePaneByColumnAndRow( 1, 2 );

			// creating the AdditionalImages worksheet
			$workbook->createSheet();
			$workbook->setActiveSheetIndex(2);
			$worksheet = $workbook->getActiveSheet();
			$worksheet->setTitle( 'AdditionalImages' );
			$this->populateAdditionalImagesWorksheet( $worksheet, $database, $boxFormat );
			$worksheet->freezePaneByColumnAndRow( 1, 2 );

			// creating the Options worksheet
			$workbook->createSheet();
			$workbook->setActiveSheetIndex(3);
			$worksheet = $workbook->getActiveSheet();
			$worksheet->setTitle( 'Options' );
			$this->populateOptionsWorksheet( $worksheet, $database, $languageId, $priceFormat, $boxFormat, $weightFormat, $textFormat );
			$worksheet->freezePaneByColumnAndRow( 1, 2 );

			// creating the Attributes worksheet
			$workbook->createSheet();
			$workbook->setActiveSheetIndex(4);
			$worksheet = $workbook->getActiveSheet();
			$worksheet->setTitle( 'Attributes' );
			$this->populateAttributesWorksheet( $worksheet, $database, $languageId, $boxFormat, $textFormat );
			$worksheet->freezePaneByColumnAndRow( 1, 2 );

			// creating the Specials worksheet
			$workbook->createSheet();
			$workbook->setActiveSheetIndex(5);
			$worksheet = $workbook->getActiveSheet();
			$worksheet->setTitle( 'Specials' );
			$this->populateSpecialsWorksheet( $worksheet, $database, $languageId, $priceFormat, $boxFormat, $textFormat );
			$worksheet->freezePaneByColumnAndRow( 1, 2 );

			// creating the Discounts worksheet
			$workbook->createSheet();
			$workbook->setActiveSheetIndex(6);
			$worksheet = $workbook->getActiveSheet();
			$worksheet->setTitle( 'Discounts' );
			$this->populateDiscountsWorksheet( $worksheet, $database, $languageId, $priceFormat, $boxFormat, $textFormat );
			$worksheet->freezePaneByColumnAndRow( 1, 2 );

			// creating the Rewards worksheet
			$workbook->createSheet();
			$workbook->setActiveSheetIndex(7);
			$worksheet = $workbook->getActiveSheet();
			$worksheet->setTitle( 'Rewards' );
			$this->populateRewardsWorksheet( $worksheet, $database, $languageId, $boxFormat );
			$worksheet->freezePaneByColumnAndRow( 1, 2 );

			$workbook->setActiveSheetIndex(0);

			// redirect output to client browser
			header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
			header('Content-Disposition: attachment;filename="backup_categories_products.xlsx"');
			header('Cache-Control: max-age=0');
			$objWriter = PHPExcel_IOFactory::createWriter($workbook, 'Excel2007');
			$objWriter->save('php://output');

			// Clear the spreadsheet caches
			$this->clearSpreadsheetCache();
			exit;

		} catch (Exception $e) {
			$errstr = $e->getMessage();
			$errline = $e->getLine();
			$errfile = $e->getFile();
			$errno = $e->getCode();
			$this->session->data['export_error'] = array( 'errstr'=>$errstr, 'errno'=>$errno, 'errfile'=>$errfile, 'errline'=>$errline );
			if ($this->config->get('config_error_log')) {
				$this->log->write('PHP ' . get_class($e) . ':  ' . $errstr . ' in ' . $errfile . ' on line ' . $errline);
			}
			return;
		}
	}

}
?>