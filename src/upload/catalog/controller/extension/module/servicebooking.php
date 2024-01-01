<?php
class ControllerExtensionModuleServicebooking extends Controller {

    private $openssl_key = "o6b2ZyNM8nkP6xN4etP3f8U398o3jSKVKYKJmUjNNS7FY";
    private $openssl_method = "aes-256-ctr";
    private $openssl_iv = "6xN4etP398o3jSKV";

    public function index() {
        $data = array();
		$this->document->setTitle($this->config->get('config_meta_title'));
		$this->document->setDescription($this->config->get('config_meta_description'));
		$this->document->setKeywords($this->config->get('config_meta_keyword'));

        $this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment-with-locales.min.js');
        $this->document->addScript('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.js');
        $this->document->addStyle('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.css');

        $this->load->model('extension/module/servicebooking');

        // available dates
        $settings = $this->model_extension_module_servicebooking->loadSettings();
        $fully_booked = $this->model_extension_module_servicebooking->getFullyBooked();
        // array of dates
        $dates = '';
        for ($i=0; $i < ($settings['max_future_weeks'] * 7); $i++) {
            $next = $i * 24 * 60 * 60;
            $date = date('Y-m-d', time() + $next);
            $dow = date('w', time() + $next);
            $dow = $dow == 0 ? 7 : $dow;
            $dates .= ((!in_array($date, $fully_booked) && isset($settings['day_'.$dow]))? '' : "'" . $date . "',\n");
        }
        $data['max_future'] = ($settings['max_future_weeks'] * 7) - 1;
        $data['settings'] = $settings;
        $now = time();
        $now = openssl_encrypt($now, $this->openssl_method, $this->openssl_key, OPENSSL_RAW_DATA, $this->openssl_iv);
        $data['tolken'] =  rawurlencode(base64_encode(password_hash($now, PASSWORD_BCRYPT) . $now));
        $data['dates'] = $dates;

        $data['url'] = $_SERVER["REQUEST_SCHEME"]."://".$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];

		return $this->load->view('extension/module/servicebooking', $data);
    }

    public function getStatus()
    {
        $this->load->model('extension/module/servicebooking');
        $this->load->language('extension/module/servicebooking');

        $status = 'nopost';
        $honorar = '';
        $post = $this->request->post;
        if (isset($post['servicebooking_nr']) && $post['servicebooking_nr'] != '') {
            $result = $this->model_extension_module_servicebooking->getStatus($post['servicebooking_nr']);
            $status = $result['status'];
        }

        $msg = sprintf($this->language->get("msg_" . str_replace(" ", "_", $status)), $honorar);

        $this->response->setOutput($msg);

    }

    public function saveBooking()
    {
        $this->load->model('extension/module/servicebooking');

        $data = $this->request->post;
        $now = time();
        $then = rawurldecode(base64_decode($data['tolken']));
        $hash = substr($then, 0 ,60);
        $then = substr($then, 60);
        if (!password_verify($then, $hash)) {
            $this->response->setOutput("<b>Session verfallen!</b><br>Die Seite bitte neu laden und Buchung nochmal abschicken – Danke!");
        }
        $then = openssl_decrypt($then, $this->openssl_method, $this->openssl_key, OPENSSL_RAW_DATA, $this->openssl_iv);
        // Form darf nicht schneller als 3 Sekunden und nicht länger als 5 min gepostet werden
        if ($then < ($now - 3) && $then > ($now - 300)) {
            if (isset($data['servicebooking_name']) && isset($data['servicebooking_enter']) && isset($data['servicebooking_contact'])) {
                $msg = $this->model_extension_module_servicebooking->saveBooking($data);
            } else {
                $msg = "Bitte alle Felder ausfüllen!";
            }
        } else {
            $msg = "<b>Session verfallen!</b><br>Die Seite bitte neu laden und Buchung nochmal abschicken – Danke!";
        }

        $this->response->setOutput($msg);

    }
}