<?php
class ModelCatalogSearchSuggestion extends Model {

  /*
   * Modification of standart getProducts() metod, add support $data['filter_model'] and $data['filter_sku']
   */
  public function getProducts($data = array()) {
    
    $this->load->model('catalog/product');
    
    $search_suggestion_options = $this->config->get('search_suggestion_options');
    if(!$search_suggestion_options) {
      $search_suggestion_options = array();
    }
    $search_logic = isset($search_suggestion_options['search_logic']) ? $search_suggestion_options['search_logic'] : 'and';
		
		if ($this->customer->isLogged()) {
			$customer_group_id = $this->customer->getCustomerGroupId();
		} else {
			$customer_group_id = $this->config->get('config_customer_group_id');
		}	
		
    $product_data = array();
    if (isset($search_suggestion_options['search_cache'])) {
      $cache = md5(http_build_query($data + $search_suggestion_options));    
      $product_data = $this->cache->get('product.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . (int)$customer_group_id . '.' . $cache);
    }
				
		if (!$product_data) {
			$sql = "SELECT p.product_id, (SELECT AVG(rating) AS total FROM " . DB_PREFIX . "review r1 WHERE r1.product_id = p.product_id AND r1.status = '1' GROUP BY r1.product_id) AS rating FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id)"; 
			
			if (!empty($data['filter_tag'])) {
				$sql .= " LEFT JOIN " . DB_PREFIX . "product_tag pt ON (p.product_id = pt.product_id)";			
			}
						
			if (!empty($data['filter_category_id'])) {
				$sql .= " LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (p.product_id = p2c.product_id)";			
			}
			
			$sql .= " WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'"; 
			
			if (!empty($data['filter_name']) || !empty($data['filter_tag']) || !empty($data['filter_model']) || !empty($data['filter_sku'])) {
				$sql .= " AND (";
											
				if (!empty($data['filter_name'])) {
					$implode = array();
					
					$words = explode(' ', trim(preg_replace('/\s\s+/', ' ', $data['filter_name'])));
					
					foreach ($words as $word) {
						if (!empty($data['filter_description'])) {
							$implode[] = "(LCASE(pd.name) LIKE '%" . $this->db->escape(utf8_strtolower($word)) . "%' OR LCASE(pd.description) LIKE '%" . $this->db->escape(utf8_strtolower($word)) . "%')";
						} else {
							$implode[] = "LCASE(pd.name) LIKE '%" . $this->db->escape(utf8_strtolower($word)) . "%'";
						}				
					}
					
					if ($implode) {
						$sql .= " " . implode(" " . $search_logic . " ", $implode) . "";
					}
				}
				
				if (!empty($data['filter_name']) && !empty($data['filter_tag'])) {
					$sql .= " OR ";
				}
				
				if (!empty($data['filter_tag'])) {
					$implode = array();
					
					$words = explode(' ', trim(preg_replace('/\s\s+/', ' ', $data['filter_tag'])));
					
					foreach ($words as $word) {
						$implode[] = "LCASE(pt.tag) LIKE '%" . $this->db->escape(utf8_strtolower($word)) . "%'";
					}
					
					if ($implode) {
						$sql .= " " . implode(" OR ", $implode) . " AND pt.language_id = '" . (int)$this->config->get('config_language_id') . "'";
					}
				}
				
				if ((!empty($data['filter_name']) || !empty($data['filter_tag'])) && !empty($data['filter_model'])) {
					$sql .= " OR ";
				}

				if (!empty($data['filter_model'])) {
					$implode = array();
					
					$words = explode(' ', trim(preg_replace('/\s\s+/', ' ', $data['filter_model'])));
					
					foreach ($words as $word) {
						$implode[] = "LCASE(p.model) LIKE '%" . $this->db->escape(utf8_strtolower($word)) . "%'";
					}
					
					if ($implode) {
						$sql .= " " . implode(" OR ", $implode) . " ";
					}
				}

				if ((!empty($data['filter_name']) || !empty($data['filter_tag']) || !empty($data['filter_model'])) && !empty($data['filter_sku'])) {
					$sql .= " OR ";
				}

				if (!empty($data['filter_sku'])) {
					$implode = array();
					
					$words = explode(' ', trim(preg_replace('/\s\s+/', ' ', $data['filter_sku'])));
					
					foreach ($words as $word) {
						$implode[] = "LCASE(p.sku) LIKE '%" . $this->db->escape(utf8_strtolower($word)) . "%'";
					}
					
					if ($implode) {
						$sql .= " " . implode(" OR ", $implode) . " ";
					}
				}
			
				$sql .= ")";
			}
			
			if (!empty($data['filter_category_id'])) {
				if (!empty($data['filter_sub_category'])) {
					$implode_data = array();
					
					$implode_data[] = "p2c.category_id = '" . (int)$data['filter_category_id'] . "'";
					
					$this->load->model('catalog/category');
					
					$categories = $this->model_catalog_category->getCategoriesByParentId($data['filter_category_id']);
										
					foreach ($categories as $category_id) {
						$implode_data[] = "p2c.category_id = '" . (int)$category_id . "'";
					}
								
					$sql .= " AND (" . implode(' OR ', $implode_data) . ")";			
				} else {
					$sql .= " AND p2c.category_id = '" . (int)$data['filter_category_id'] . "'";
				}
			}		
					
			if (!empty($data['filter_manufacturer_id'])) {
				$sql .= " AND p.manufacturer_id = '" . (int)$data['filter_manufacturer_id'] . "'";
			}
			
			$sql .= " GROUP BY p.product_id";
			
			$sort_data = array(
				'pd.name',
				'p.model',
				'p.quantity',
				'p.price',
				'rating',
				'p.sort_order',
				'p.date_added'
			);	
			
			if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
				if ($data['sort'] == 'pd.name' || $data['sort'] == 'p.model') {
					$sql .= " ORDER BY LCASE(" . $data['sort'] . ")";
				} else {
					$sql .= " ORDER BY " . $data['sort'];
				}
			} else {
				$sql .= " ORDER BY p.sort_order";	
			}
			
			if (isset($data['order']) && ($data['order'] == 'DESC')) {
				$sql .= " DESC, LCASE(pd.name) DESC";
			} else {
				$sql .= " ASC, LCASE(pd.name) ASC";
			}
		
			if (isset($data['start']) || isset($data['limit'])) {
				if ($data['start'] < 0) {
					$data['start'] = 0;
				}				
	
				if ($data['limit'] < 1) {
					$data['limit'] = 20;
				}	
			
				$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
			}
			
			$product_data = array();
					
			$query = $this->db->query($sql);
		
			foreach ($query->rows as $result) {
				$product_data[$result['product_id']] = $this->model_catalog_product->getProduct($result['product_id']);
			}

      if (isset($search_suggestion_options['search_cache'])) {
  			$this->cache->set('product.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . (int)$customer_group_id . '.' . $cache, $product_data);
      }
			
		}
		
		return $product_data;
	}
	
  /*
   * Modification of standart getTotalProducts() metod, add support $data['filter_model'] and $data['filter_sku']
   */	
	public function getTotalProducts($data = array()) {
    
    $search_suggestion_options = $this->config->get('search_suggestion_options');
    if(!$search_suggestion_options) {
      $search_suggestion_options = array();
    }
    $search_logic = isset($search_suggestion_options['search_logic']) ? $search_suggestion_options['search_logic'] : 'and';
    
    $sql = "SELECT COUNT(DISTINCT p.product_id) AS total FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id)";

		if (!empty($data['filter_category_id'])) {
			$sql .= " LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (p.product_id = p2c.product_id)";			
		}
		
		if (!empty($data['filter_tag'])) {
			$sql .= " LEFT JOIN " . DB_PREFIX . "product_tag pt ON (p.product_id = pt.product_id)";			
		}
					
		$sql .= " WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'";
		
		if (!empty($data['filter_name']) || !empty($data['filter_tag']) || !empty($data['filter_model']) || !empty($data['filter_sku'])) {
			$sql .= " AND (";
								
			if (!empty($data['filter_name'])) {
				$implode = array();
				
				$words = explode(' ', trim(preg_replace('/\s\s+/', ' ', $data['filter_name'])));
				
				foreach ($words as $word) {
					if (!empty($data['filter_description'])) {
						$implode[] = "(LCASE(pd.name) LIKE '%" . $this->db->escape(utf8_strtolower($word)) . "%' OR LCASE(pd.description) LIKE '%" . $this->db->escape(utf8_strtolower($word)) . "%')";
					} else {
						$implode[] = "LCASE(pd.name) LIKE '%" . $this->db->escape(utf8_strtolower($word)) . "%'";
					}				
				}
				
				if ($implode) {
					$sql .= " " . implode(" " . $search_logic . " ", $implode) . "";
				}
			}
			
			if (!empty($data['filter_name']) && !empty($data['filter_tag'])) {
				$sql .= " OR ";
			}
			
			if (!empty($data['filter_tag'])) {
				$implode = array();
				
				$words = explode(' ', trim(preg_replace('/\s\s+/', ' ', $data['filter_tag'])));
				
				foreach ($words as $word) {
					$implode[] = "LCASE(pt.tag) LIKE '%" . $this->db->escape(utf8_strtolower($word)) . "%'";
				}
				
				if ($implode) {
					$sql .= " " . implode(" OR ", $implode) . " AND pt.language_id = '" . (int)$this->config->get('config_language_id') . "'";
				}
			}

				if ((!empty($data['filter_name']) || !empty($data['filter_tag'])) && !empty($data['filter_model'])) {
					$sql .= " OR ";
				}

				if (!empty($data['filter_model'])) {
					$implode = array();
					
					$words = explode(' ', trim(preg_replace('/\s\s+/', ' ', $data['filter_model'])));
					
					foreach ($words as $word) {
						$implode[] = "LCASE(p.model) LIKE '%" . $this->db->escape(utf8_strtolower($word)) . "%'";
					}
					
					if ($implode) {
						$sql .= " " . implode(" OR ", $implode) . " ";
					}
				}

				if ((!empty($data['filter_name']) || !empty($data['filter_tag']) || !empty($data['filter_model'])) && !empty($data['filter_sku'])) {
					$sql .= " OR ";
				}

				if (!empty($data['filter_sku'])) {
					$implode = array();
					
					$words = explode(' ', trim(preg_replace('/\s\s+/', ' ', $data['filter_sku'])));
					
					foreach ($words as $word) {
						$implode[] = "LCASE(p.sku) LIKE '%" . $this->db->escape(utf8_strtolower($word)) . "%'";
					}
					
					if ($implode) {
						$sql .= " " . implode(" OR ", $implode) . " ";
					}
				}
		
			$sql .= ")";
		}
		
		if (!empty($data['filter_category_id'])) {
			if (!empty($data['filter_sub_category'])) {
				$implode_data = array();
				
				$implode_data[] = "p2c.category_id = '" . (int)$data['filter_category_id'] . "'";
				
				$this->load->model('catalog/category');
				
				$categories = $this->model_catalog_category->getCategoriesByParentId($data['filter_category_id']);
					
				foreach ($categories as $category_id) {
					$implode_data[] = "p2c.category_id = '" . (int)$category_id . "'";
				}
							
				$sql .= " AND (" . implode(' OR ', $implode_data) . ")";			
			} else {
				$sql .= " AND p2c.category_id = '" . (int)$data['filter_category_id'] . "'";
			}
		}		
		
		if (!empty($data['filter_manufacturer_id'])) {
			$sql .= " AND p.manufacturer_id = '" . (int)$data['filter_manufacturer_id'] . "'";
		}
		
		$query = $this->db->query($sql);
		
		return $query->row['total'];
	}

}
