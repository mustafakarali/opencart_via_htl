<?php
class ControllerModuleFeatured extends Controller {
	protected function index($setting) {
		$this->language->load('module/featured'); 

      	$this->data['_heading_title'] = $this->language->get('_heading_title');
		
		$this->data['button_cart'] = $this->language->get('button_cart');
		
		$this->load->model('catalog/product'); 
		
		$this->load->model('tool/image');

		$this->data['products'] = array();

		$products = explode(',', $this->config->get('featured_product'));		

		if (empty($setting['limit'])) {
			$setting['limit'] = 5;
		}
		
		$products = array_slice($products, 0, (int)$setting['limit']);
		
		foreach ($products as $product_id) {
			$product_info = $this->model_catalog_product->getProduct($product_id);
			
			if ($product_info) {
				if ($product_info['image']) {
					$image = $this->model_tool_image->resize($product_info['image'], $setting['image_width'], $setting['image_height']);
				} else {
					$image = false;
				}

				if (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) {
					$price = $this->currency->format($this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax')));
				} else {
					$price = false;
				}
						
				if ((float)$product_info['special']) {
					$special = $this->currency->format($this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax')));
				} else {
					$special = false;
				}
				
				if ($this->config->get('config_review_status')) {
					$rating = $product_info['rating'];
				} else {
					$rating = false;
				}
					
				$this->data['products'][] = array(
					'product_id' => $product_info['product_id'],
					'thumb'   	 => $image,
					'name'    	 => $product_info['name'],
					'price'   	 => $price,
					'special' 	 => $special,
					'rating'     => $rating,
					'reviews'    => sprintf($this->language->get('text_reviews'), (int)$product_info['reviews']),
					'href'    	 => $this->url->link('product/product', 'product_id=' . $product_info['product_id'])
				);
			}
		}


        //历史记录


        $viewed_products = array();

        if (isset($this->request->cookie['viewed'])) {
            $viewed_products = explode(',', $this->request->cookie['viewed']);
        } else if (isset($this->session->data['viewed'])) {
            $viewed_products = $this->session->data['viewed'];
        }

        if (isset($this->request->get['route']) && $this->request->get['route'] == 'product/product') {

            $product_id = $this->request->get['product_id'];

            $viewed_products = array_diff($viewed_products, array($product_id));

            array_unshift($viewed_products, $product_id);

            setcookie('viewed', implode(',',$viewed_products), time() + 60 * 60 * 24 * 30, '/', $this->request->server['HTTP_HOST']);

            if (!isset($this->session->data['viewed']) || $this->session->data['viewed'] != $viewed_products) {
                $this->session->data['viewed'] = $viewed_products;
            }
        }

        $show_on_product = $this->config->get('show_on_product');
        if (isset($this->request->get['route']) && $this->request->get['route'] == 'product/product' && (!isset($show_on_product) || !$show_on_product)) {
            return;
        }

        $viewed_count = $this->config->get('viewed_count');

        $products = array();

        if (isset($viewed_count) && $viewed_count > 0) {
            for ($i = 0; $i < $viewed_count; $i++) {

                $key = isset($product_id) ? $i + 1 : $i;

                if (isset($viewed_products[$key])) {
                    $products[] = $viewed_products[$key];
                }
            }
        }


        $this->language->load('module/viewed');

        $this->data['heading_title'] = $this->language->get('heading_title');

        $this->data['button_cart'] = $this->language->get('button_cart');

        $this->load->model('catalog/product');

        $this->load->model('tool/image');

        $this->data['viewd_products'] = array();

        foreach ($products as $product_id) {
            $product_info = $this->model_catalog_product->getProduct($product_id);

            if ($product_info) {
                if ($product_info['image']) {
                    $image = $this->model_tool_image->resize($product_info['image'], 100,100);
                } else {
                    $image = false;
                }

                if (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) {
                    $price = $this->currency->format($this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax')));
                } else {
                    $price = false;
                }

                if ((float)$product_info['special']) {
                    $special = $this->currency->format($this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax')));
                } else {
                    $special = false;
                }

                if ($this->config->get('config_review_status')) {
                    $rating = $product_info['rating'];
                } else {
                    $rating = false;
                }

                $this->data['viewd_products'][] = array(
                    'product_id' => $product_info['product_id'],
                    'thumb'   	 => $image,
                    'name'    	 => $product_info['name'],
                    'price'   	 => $price,
                    'special' 	 => $special,
                    'rating'     => $rating,
                    'reviews'    => sprintf($this->language->get('text_reviews'), (int)$product_info['reviews']),
                    'href'    	 => $this->url->link('product/product', 'product_id=' . $product_info['product_id']),
                );
            }
        }
        //历史记录 end
        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/module/featured.tpl')) {
			$this->template = $this->config->get('config_template') . '/template/module/featured.tpl';
		} else {
			$this->template = 'default/template/module/featured.tpl';
		}



		$this->render();
	}
}
?>