<?php
class ModelExtensionNews extends Model {	
	public function getNews($id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "news n LEFT JOIN " . DB_PREFIX . "news_description nd ON n.news_id = nd.news_id WHERE n.news_id = '" . (int)$id . "' AND nd.language_id = '" . $this->config->get('config_language_id') . "'");
		
		return $query->row;
	}
 
	public function getAllNews($data) {
		$sql = "SELECT * FROM " . DB_PREFIX . "news n LEFT JOIN " . DB_PREFIX . "news_description nd ON n.news_id = nd.news_id WHERE nd.language_id = '" . $this->config->get('config_language_id') . "' AND n.status = '1' ORDER BY date_added DESC";
		
		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}		
				if ($data['limit'] < 1) {
				$data['limit'] = 10;
			}	
		
			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}	
		
		$query = $this->db->query($sql);
		
		return $query->rows;
	}
	
	public function countNews() {
		$count = $this->db->query("SELECT * FROM " . DB_PREFIX . "news");
	
		return $count->num_rows;
	}
}
?>