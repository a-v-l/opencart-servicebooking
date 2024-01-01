<?php
class ModelExtensionModuleServicebooking extends Model {

    public function loadSettings()
    {
      $setting_data = array();
  
      $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE `code` = 'module_servicebooking_settings'");
  
      foreach ($query->rows as $result) {
          $setting_data[$result['key']] = $result['value'];
      }
  
      return $setting_data;
    }

    public function getFullyBooked()
    {
      $max_future_weeks = $this->db->query("SELECT `value` FROM " . DB_PREFIX . "setting WHERE
                          `code` = 'module_servicebooking_settings' AND
                          `key` = 'max_future_weeks'")->row['value'];
      $services_per_day = $this->db->query("SELECT `value` FROM " . DB_PREFIX . "setting WHERE
                          `code` = 'module_servicebooking_settings' AND
                          `key` = 'services_per_day'")->row['value'];
      $query = $this->db->query("SELECT acceptance FROM " . DB_PREFIX . "servicebooking GROUP BY acceptance HAVING
                          acceptance >= CURRENT_DATE() AND
                          acceptance <= DATE_ADD(CURRENT_DATE(), INTERVAL ".($max_future_weeks*7)." DAY) AND
                          acceptance IN (
                            SELECT acceptance FROM " . DB_PREFIX . "servicebooking GROUP BY acceptance
                            HAVING COUNT(*) > ".($services_per_day-1)."
                          );")->rows;
      
      return array_column($query, 'acceptance');
    }

    public function getStatus($nr)
    {
      $sql = "SELECT `status`, `honorar` FROM " . DB_PREFIX . "servicebooking WHERE `servicebooking_nr` = " . (int)$nr;
      $query = $this->db->query($sql)->row;
  
      $honorar = '';
      if ($query) {
        $status = $query['status'];
        if ($status == 'Fertig') {
          $honorar = str_replace('.',',',(string)$query['honorar']);
        }
      } else {
        $status = 'notfound';
      }
  
      return array(
        'status' => $status,
        'honorar' => $honorar
      );
    }

    public function saveBooking($data)
    {
      $max_servicebooking_nr = $this->db->query("SELECT servicebooking_nr
                        FROM " . DB_PREFIX . "servicebooking
                        ORDER BY servicebooking_nr DESC LIMIT 0, 1")->row['servicebooking_nr'];
      $week_increment = $this->db->query("SELECT MAX(`week_increment`)+1
                        AS wi
                        FROM `oc_servicebooking`
                        WHERE YEARWEEK(`date_created`, 1) = YEARWEEK(CURDATE(), 1)")->row['wi'];
      $error = 'Der Eintrag konnte nicht gespeichert werden!';
      $re = '/\d{4}-\d{2}-\d{2}/';
      preg_match($re, $data['servicebooking_date'], $matches, PREG_OFFSET_CAPTURE, 0);
      if (count($matches) == 0) {
          $error = "Das Buchungsdatum hat ein falsches Format";
      }

      $success = $this->db->query("INSERT INTO " . DB_PREFIX . "servicebooking
      SET
        servicebooking_nr = '" . ($max_servicebooking_nr + 1) . "',
        week_increment = " . ($week_increment ? $week_increment : 1) . ",
        acceptance = '" . $data['servicebooking_enter'] . "',
        submission = '" . $data['servicebooking_leave'] . "',
        customer_name = '" . $data['servicebooking_name'] . "',
        customer_contact = '" . $data['servicebooking_contact'] . "',
        date_modified = NOW()");

    $then = DateTime::createFromFormat('Y-m-d', $data['servicebooking_enter']);
    $now = new DateTime();
    $interval = (int)date_diff($then, $now)->format('%a');
    switch ($interval) {
      case 0:
        $date = '<strong>heute noch';
        $greet = 'Bis gleich,';
        break;
      case 1:
        $date = '<strong>morgen';
        $greet = 'Bis morgen,';
        break;
      
      default:
        $date = 'am <strong>'.$then->format('d.m.Y');
        $greet = 'Bis in '.$interval.' Tagen,';
        break;
    }

    return $success ? '<h3>Die Buchung ist bei uns eingegangen!</h3><p>Bitte bringe das Rad/die Ski '.$date.'</strong> in unseren Laden!<br>Du kannst den Bearbeitungsstand abfragen, indem du die <strong>Bearbeitungs-Nr: '.$max_servicebooking_nr.'</strong> hier auf dieser Webseite eingibst.</p></p>'.$greet.'<br>dein Werkstatt-Team!</p>'  : $error;
    }

}