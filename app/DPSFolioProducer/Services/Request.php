<?php
namespace DPSFolioProducer\Services;

class Request
{
    public $is_retry = false;
    public $options = null;
    public $response_headers = array();
    public $response = null;
    public $url = null;

    public function __construct($url, $options)
    {
        $this->options = $options;
        $this->url = $url;
    }

    public function run($filename=null)
    {
        if ($filename) {
            $this->upload_file($filename);
        }
        $context = stream_context_create($this->options);
        $response = file_get_contents($this->url, false, $context);

        if (isset($http_response_header)) {
            $this->response_headers = $http_response_header;
        }
        $this->response = json_decode($response);
        if ($this->response === null) {
            if (json_last_error() !== JSON_ERROR_NONE) {
                user_error(json_last_error());
            }
        }

        return $this->response;
    }

    public function get_response_code()
    {
        $response_code = null;
        if ($this->response_headers && count($this->response_headers)) {
            preg_match('/^(([a-zA-Z]+)\/([\d\.]+))\s([\d\.]+)\s(.*)$/', $this->response_headers[0], $matches);
            if ($matches) {
                $response_code = intval($matches[4]);
            }
        }

        return $response_code;
    }

    public function upload_file($filename)
    {
        $data = '';
        $handle = fopen($filename, 'rb');
        fseek($handle, 0);
        $binary = fread($handle, filesize($filename));
        fclose($handle);

        $separator = md5(microtime());

        $eol = "\r\n";
        $data = '';
        $data .=  '--' . $separator . $eol;
        $data .='Content-Disposition: form-data; name=""; filename="' . $filename . '"' . $eol;
        $data .='Content-Type: ' . $eol;
        $data .='Content-Transfer-Encoding: binary' . $eol . $eol;
        $data .= $binary . $eol;
        $data .= '--' . $separator . '--' . $eol;

        array_shift($this->options['http']['header']); // remove Content-Type header
        array_push($this->options['http']['header'], 'Content-Type: multipart/form-data; boundary='.$separator);
        $this->options['http']['content'] = $data;
    }

    public function retry()
    {
        $this->response_headers = null;
        $this->response = null;
        $this->is_retry = true;
        $this->run();
    }
}
