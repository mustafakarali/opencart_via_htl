<?php
class ControllerModuleTab extends Controller {
	protected function index($setting) {
		$this->language->load('module/tab');

        $this->data['heading_title'] = $this->language->get('heading_title');
        $this->data['brand_heading_title'] = $this->language->get('brand_heading_title');
        $this->data['group_buy_heading_title'] = $this->language->get('group_buy_heading_title');
        $this->data['flash_sale_heading_title'] = $this->language->get('flash_sale_heading_title');
		
		$this->data['button_cart'] = $this->language->get('button_cart');
		
		$this->load->model('catalog/product'); 
		
		$this->load->model('tool/image');

		$this->data['products'] = array();

        //新品上架
		$new_products = explode(',', $this->config->get('tab_new_product'));

		if (empty($setting['limit'])) {
			$setting['limit'] = 5;
		}
		
		$new_products = array_slice($new_products, 0, (int)$setting['limit']);
		
		foreach ($new_products as $product_id) {
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
					
				$this->data['new_products'][] = array(
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
        //新品上架 end

        //品牌推荐
        $brand_products = explode(',',$this->config->get('tab_brand_product'));

        $brand_products = array_slice($brand_products, 0, (int)$setting['limit']);

        foreach ($brand_products as $product_id) {
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

                $this->data['brand_products'][] = array(
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
        //品牌推荐 end

        //团购商品
        $group_buy_products = explode(',',$this->config->get('tab_group_buy_product'));

        $group_buy_products = array_slice($group_buy_products, 0, (int)$setting['limit']);

        foreach ($group_buy_products as $product_id) {
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

                $this->data['group_buy_products'][] = array(
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
        //团购商品 end
        //限时抢购
        $flash_sale_products = explode(',',$this->config->get('tab_flash_sale_product'));

        $flash_sale_products = array_slice($flash_sale_products, 0, (int)$setting['limit']);

        foreach ($flash_sale_products as $product_id) {
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

                $this->data['flash_sale_products'][] = array(
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
        //限时抢购 end


		if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/module/tab.tpl')) {
			$this->template = $this->config->get('config_template') . '/template/module/tab.tpl';
		} else {
			$this->template = 'default/template/module/tab.tpl';
		}

		$this->render();
	}
}
?>