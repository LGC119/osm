<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
** 上传图片
*/
class Attachment extends ME_Controller {

	public function __construct()
	{
		parent::__construct();
        // $this ->load ->library('fileupload');
	}

    public function index()
    {
        if (empty($_FILES))
        {
            $this->meret('Upload file is empty', MERET_EMPTY);
            return;
        }
        else
        {
            // 等确定配置信息后，将配置信息写入config目录
            // the data field name from form
            $field_name = $this->input->post('field_name', TRUE) ? $this->input->post('field_name', TRUE) : 'upfile';

            // the data type: image or sound
            $file_type = $this->input->post('file_type', TRUE) ? $this->input->post('file_type', TRUE) : 'image';

            if ('image' != $file_type && 'sound' != $file_type)
            {
                $this->meret('Attachment is not valid', MERET_OTHER);
                return;
            }

            $upload_root = 'image' == $file_type ? '../uploads/images' : '../uploads/sounds';

            $format = date('Y-m');
            $format_arr = explode('-', $format);
            $year = $format_arr[0];
            $month = $format_arr[1];
            $upload_path = $upload_root.'/'.$year.'/'.$month;
            if (! file_exists($upload_path))
            {
                if (! mkdir($upload_path, 0777, TRUE))
                {
                    $this->meret('Make upload path '.$upload_path.' failed.', MERET_OTHER);
                    return;
                }
            }
            
            $config['upload_path'] = $upload_path;
            $config['allowed_types'] = 'gif|jpg|jpeg|png|bmp|mp3|amr|wmv|mp4';
            $config['max_size'] = '5000'; // 上传文件大小在5M以内
            $config['file_name'] = date('YmdHis').mt_rand(1, 1000);
            $this->load->library('upload', $config);
            $this->upload->initialize($config);
            if ($this->upload->do_upload('upfile'))
            {
                $data = $this->upload->data();
                $data['upload_path'] = ltrim($upload_path.'/'.$data['file_name'], '\.\./');
                $status = MERET_OK;
            }
            else
            {
                $data = 'Failed to move attachment to dir '.$upload_path.'. Please check mime type';
                $status = MERET_OTHER;
            }
            $this->meret($data, $status, implode("\n", $this->upload->error_msg));
            return;
        }
    }
}

/* End of file Attachment.php */
/* Location: ./application/controllers/common/attachment.php */