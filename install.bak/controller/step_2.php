<?php
class ControllerStep2 extends Controller {
	private $error = array();
	
	public function index() {
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->redirect($this->url->link('step_3'));
		}

		if (isset($this->error['warning'])) {
			$this->data['error_warning'] = $this->error['warning'];
		} else {
			$this->data['error_warning'] = '';	
		}
		
		$this->data['action'] = $this->url->link('step_2');

		$this->data['config_catalog'] = DIR_OPENCART . 'config.php';
		$this->data['config_admin'] = DIR_OPENCART . 'admin/config.php';
		
		$this->data['cache'] = DIR_SYSTEM . 'cache';
		$this->data['logs'] = DIR_SYSTEM . 'logs';
		$this->data['image'] = DIR_OPENCART . 'image';
		$this->data['image_cache'] = DIR_OPENCART . 'image/cache';
		$this->data['image_data'] = DIR_OPENCART . 'image/data';
		$this->data['download'] = DIR_OPENCART . 'download';
		
		$this->data['back'] = $this->url->link('step_1');
		
		$this->template = 'step_2.tpl';
		$this->children = array(
			'header',
			'footer'
		);		
		
		$this->response->setOutput($this->render());
	}
	
	private function validate() {
		if (phpversion() < '5.0') {
			$this->error['warning'] = '警告： 您的服务器PHP版本不是PHP5或更高版本！';
		}

		if (!ini_get('file_uploads')) {
			$this->error['warning'] = '警告： 需要激活文件上传功能！';
		}
	
		if (ini_get('session.auto_start')) {
			$this->error['warning'] = '警告：不要激活 session.auto_start ！';
		}
		
		if (!extension_loaded('mysql')) {
			$this->error['warning'] = '警告： 需要开启MySQL扩展！';
		}
				
		if (!extension_loaded('gd')) {
			$this->error['warning'] = '警告： 需要加载 GD 扩展！';
		}

		if (!extension_loaded('curl')) {
			$this->error['warning'] = '警告： 需要加载 CURL 扩展！';
		}

		if (!function_exists('mcrypt_encrypt')) {
			$this->error['warning'] = '警告：需要加载 mCrypt 扩展！';
		}
				
		if (!extension_loaded('zlib')) {
			$this->error['warning'] = '警告： 需要加载 ZLIB 扩展！';
		}
		
		if (!file_exists(DIR_OPENCART . 'config.php')) {
			$this->error['warning'] = '警告： config.php 文件不存在，您需要手工将 config-dist.php 更名为 config.php！';
		} elseif (!is_writable(DIR_OPENCART . 'config.php')) {
			$this->error['warning'] = '警告： config.php 文件需要设置为可写！';
		}
		
		if (!file_exists(DIR_OPENCART . 'admin/config.php')) {
			$this->error['warning'] = '警告： admin/config.php 文件不存在，您需要手工将 admin/config-dist.php 更名为 admin/config.php！';
		} elseif (!is_writable(DIR_OPENCART . 'admin/config.php')) {
			$this->error['warning'] = '警告： admin/config.php 文件需要设置为可写！';
		}

		if (!is_writable(DIR_SYSTEM . 'cache')) {
			$this->error['warning'] = '警告： Cache 目录需要设置为可写！';
		}
		
		if (!is_writable(DIR_SYSTEM . 'logs')) {
			$this->error['warning'] = '警告： Logs 目录需要设置为可写！';
		}
		
		if (!is_writable(DIR_OPENCART . 'image')) {
			$this->error['warning'] = '警告： Image 目录需要设置为可写！';
		}

		if (!is_writable(DIR_OPENCART . 'image/cache')) {
			$this->error['warning'] = '警告： Image cache 目录需要设置为可写！';
		}
		
		if (!is_writable(DIR_OPENCART . 'image/data')) {
			$this->error['warning'] = '警告： Image data 目录需要设置为可写！';
		}
		
		if (!is_writable(DIR_OPENCART . 'download')) {
			$this->error['warning'] = '警告： Download 目录需要设置为可写！';
		}
		
    	if (!$this->error) {
      		return true;
    	} else {
      		return false;
    	}
	}
}
?>