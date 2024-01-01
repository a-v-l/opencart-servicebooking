<?php
class ModelExtensionModuleServicebooking extends Model {

	public function init(): void
	{
		$table_exists = $this->db->query("SELECT count(*) AS tab
		FROM information_schema.TABLES
		WHERE TABLE_SCHEMA = '" . DB_DATABASE . "'
		AND TABLE_NAME = '" . DB_PREFIX . "servicebooking'");
		if ($table_exists->row['tab'] === "0") {
			$this->createTable();
			$this->setDefaults();
		}
	}

  public function addServicebooking($data) {

	$week_increment = $this->db->query("SELECT MAX(`week_increment`)+1 AS wi
					FROM " . DB_PREFIX . "servicebooking
					WHERE YEARWEEK(`date_created`, 1) = YEARWEEK(CURDATE(), 1)")->row['wi'];
	
	$success = $this->db->query("INSERT INTO " . DB_PREFIX . "servicebooking
					SET
						servicebooking_nr = '" . (int)$data['servicebooking_nr'] . "',
						week_increment = " . ($week_increment ? $week_increment : 1) . ",
						priority = " . (isset($data['priority']) ? 1 : "NULL") . ",
						acceptance = '" . $data['acceptance'] . "',
						submission = '" . $data['submission'] . "',
						customer_name = '" . $data['customer_name'] . "',
						customer_contact = '" . $data['customer_contact'] . "',
						comment = '" . $data['comment'] . "',
						status = '" . $data['status'] . "',
						typ = '" . $data['typ'] . "',
						honorar = '" . round($data['honorar'], 2) . "',
						date_modified = NOW(),
						date_created = NOW()");
    $servicebooking_id = $this->db->getLastId();

	return $success ? 'Der Eintrag (id: '.$servicebooking_id.') wurde erfolgreich gespeichert.' : 'Der Eintrag konnte nicht gespeichert werden!';
  }

  public function editServicebooking($data) {
	$typ = isset($data['typ']) ? "'" . $data['typ'] . "'" : "NULL";
	$honorar = (isset($data['honorar']) && $data['honorar'] != '') ? "'" . round($data['honorar'], 2) . "'" : "NULL";

	$success = $this->db->query("UPDATE " . DB_PREFIX . "servicebooking
					SET
						servicebooking_nr = '" . (int)$data['servicebooking_nr'] . "',
						priority = " . (isset($data['priority']) ? 1 : "NULL") . ",
						acceptance = '" . $data['acceptance'] . "',
						submission = '" . $data['submission'] . "',
						customer_name = '" . $data['customer_name'] . "',
						customer_contact = '" . $data['customer_contact'] . "',
						comment = '" . $data['comment'] . "',
						status = '" . $data['status'] . "',
						typ = " . $typ . ",
						honorar = " . $honorar . ",
						date_modified = NOW()
					WHERE servicebooking_id = '" . (int)$data['servicebooking_id'] . "'");
	return $success ? 'Der Eintrag wurde erfolgreich aktuallisiert.' : 'Der Eintrag konnte nicht aktuallisiert werden!';
  }

  public function deleteServicebooking($servicebooking_id) {
	$this->db->query("DELETE FROM " . DB_PREFIX . "servicebooking WHERE servicebooking_id = '" . (int)$servicebooking_id . "'");
  }

  public function getTotalServicebookings(Array $data = array())
  {
		$sql = "SELECT COUNT(DISTINCT s.servicebooking_id) AS total FROM " . DB_PREFIX . "servicebooking s";

		if (isset($data['status']) && $data['status'] != '') {
			$sql .= " WHERE s.status='" . $data['status'] . "'";
		}

		$query = $this->db->query($sql);

		return $query->row['total'];
  }

  /**
   * get servicebooking entries
   *
   * optionaly filtered, sorted, croped by $data
   *
   * @param Array $data['sort', 'order', 'start', 'limit', 'status']
   * @return Object mysqli_result
   **/
  public function getServicebookings(Array $data = array())
  {
    $sql = "SELECT * FROM " . DB_PREFIX . "servicebooking s";

		$sort_data = array(
			's.servicebooking_nr',
			's.acceptance',
			's.status',
			's.typ',
			's.customer_name'
		);

		if (isset($data['status']) && $data['status'] != '') {
			$sql .= " WHERE s.status='" . $data['status'] . "'";
		}

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY s.servicebooking_nr";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC";
		} else {
			$sql .= " ASC";
		}

		if (isset($data['start']) || isset($data['limit'])) {
			$data['start'] = !isset($data['start']) ? 0 : ($data['start'] < 0 ? 0 : $data['start']);
			$data['limit'] = ($data['limit'] < 1) ? 20 : $data['limit'];

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$query = $this->db->query($sql);

		return $query->rows;
  }

  public function saveSettings($data)
  {
	// $data = array(
	// 	'day-1' => 1,
	// 	'day-2' => 1,
	// 	'day-3' => 0,
	// 	'day-4' => 1,
	// 	'day-5' => 1,
	// 	'day-6' => 1,
	// 	'day-7' => 0,
	// 	'services_per_day' => 3,
	// 	'service_duration' => 5,
	// 	'max_future_weeks' => 3,
	//	'module_servicebooking_status' => 1
	// );
	$this->db->query("DELETE FROM " . DB_PREFIX . "setting WHERE `code` = 'module_servicebooking_settings'");

	foreach ($data as $key => $value) {
		$code = $key === "module_servicebooking_status" ? "module_servicebooking" : "module_servicebooking_settings";
		$this->db->query("INSERT INTO " . DB_PREFIX . "setting
					SET
						`code` = '" . $code . "',
						`key` = '" . $this->db->escape($key) . "',
						`value` = '" . $this->db->escape($value) . "'");
	}
  }

  public function loadSettings()
  {
	$setting_data = array();

	$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE `code` = 'module_servicebooking_settings' OR `code` = 'module_servicebooking'");

	foreach ($query->rows as $result) {
		$setting_data[$result['key']] = $result['value'];
	}

	return $setting_data;
  }

  private function createTable() : void
  {
	$this->db->query("CREATE TABLE `" . DB_PREFIX . "servicebooking` (
	  `servicebooking_id` int(11) NOT NULL,
	  `servicebooking_nr` int(11) NOT NULL,
	  `week_increment` smallint(3) NOT NULL,
	  `priority` tinyint(4) DEFAULT NULL,
	  `acceptance` date NOT NULL,
	  `submission` date DEFAULT NULL,
	  `status` varchar(12) NOT NULL DEFAULT 'Angemeldet',
	  `typ` enum('Kleine Inspektion','Instandsetzung','Große Inspektion','Winterservice') DEFAULT NULL,
	  `honorar` decimal(7,2) DEFAULT NULL,
	  `customer_id` int(11) DEFAULT NULL,
	  `customer_name` varchar(255) NOT NULL,
	  `customer_contact` text DEFAULT NULL,
	  `comment` text DEFAULT NULL,
	  `date_modified` date NOT NULL,
	  `date_created` date NOT NULL DEFAULT current_timestamp()
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

	$this->db->query("ALTER TABLE `" . DB_PREFIX . "servicebooking`
	  ADD PRIMARY KEY (`servicebooking_id`),
	  ADD UNIQUE KEY `nr` (`servicebooking_nr`);");

	$this->db->query("ALTER TABLE `" . DB_PREFIX . "servicebooking`
	  MODIFY `servicebooking_id` int(11) NOT NULL AUTO_INCREMENT;");
  }

  private function setDefaults() : void {
	$this->db->query("INSERT INTO `" . DB_PREFIX . "setting` (`store_id`, `code`, `key`, `value`, `serialized`) VALUES
	(0, 'module_servicebooking_settings', 'max_future_weeks', '3', 0),
	(0, 'module_servicebooking_settings', 'service_duration', '3', 0),
	(0, 'module_servicebooking_settings', 'services_per_day', '5', 0),
	(0, 'module_servicebooking_settings', 'day_1', '1', 0),
	(0, 'module_servicebooking_settings', 'day_2', '1', 0),
	(0, 'module_servicebooking_settings', 'day_3', '1', 0),
	(0, 'module_servicebooking_settings', 'day_4', '1', 0),
	(0, 'module_servicebooking_settings', 'day_5', '1', 0),
	(0, 'module_servicebooking_settings', 'day_6', '1', 0)");
  }

}
