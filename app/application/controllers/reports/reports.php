<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Reports extends Base_Controller {

    public function __construct()
    {
        parent::__construct();
        // if (! $this->input->is_cli_request()) exit;
    }

    public function index($day = "")
    {
        $day = !empty($day) ? $day : date("Y-m-d", strtotime("yesterday"));

        $company_ids = $this->_get_company_ids();

        foreach ($company_ids as $company_id)
        {
            $this->_get_communication_data($company_id, $day);

            exit;
        }

    }

    private function _get_communication_data($company_id, $day = "")
    {
        $this->load->model("report/report_model", "report_model");

        $communication_data = $this->report_model->get_all_type_hour_communication_data($company_id, $day);
    }

    private function _check_customer_dir_exist($dir = "")
    {
        $dir = !empty($dir) ? $dir : '../uploads/csv';
        if (! file_exists($dir))
        {
            mkdir($dir, 0755, true);
        }

    }

    private function _get_company_ids()
    {
        $this->load->model("report/report_model", "report_model");
        $company_ids = $this->report_model->get_company_ids();

        return $company_ids;
    }

    public function _make_csv($data, $title = "", $filename = "", $path = "")
    {

        if (empty($data)) return FALSE;

        $path = empty($path) ? "../upload/csv" : $path;
        $filename = empty($filename) ? date("YmdHis") . mt_rand(0, 1000) . ".csv" : $filename . ".csv";

        $fp = fopen($path.'/'.$filename, 'w');

        if (! empty($title))
        {
            foreach ($title as $key => $fields)
            {
                $title[$key] = iconv("UTF-8", "GB2312", $fields);
            }
            fputcsv($fp, $title);
        }

        foreach ($data as $fields)
        {
            foreach ($fields as $key => $val)
            {
                $fields[$key] = iconv("UTF-8", "GB2312//TRANSLIT//IGNORE", $val);
            }
            fputcsv($fp, $fields);
        }

        fclose($fp);
        return TRUE;
    }

}
