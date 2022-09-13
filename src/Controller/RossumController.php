<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
//define('ROSSUM_API_ENDPOINT', 'https://api.elis.rossum.ai/v1/');

class RossumController extends AbstractController
{
    private $username;
    private $password;
    private $queue_id ;

    public function __construct(){
        $this->username = "alex@ars-global.com";
        $this->password = "alex000000";
        $this->queue_id = 79213;
    }

    /**
     * @Route("/rossum", name="rossum", methods={"GET"})
     */
    public function index(Request $request)
    {   
        $token = $this->getLoginToken();
        $url = $this->constructUrl("confirmed");
        $response = $this->makeRequest($url, $token);
        $annotations = $response->results;
        dd($annotations);

        return new Response('allo...');
    }

    public function getLoginToken($verbose = false) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.elis.rossum.ai/v1/auth/login');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 
            json_encode(array(
            'username' => $this->username,
            'password' => $this->password,
        )));

        $headers = array();
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $raw_response = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
        $response = json_decode($raw_response, true);
        if (!isset($response['key'])) {
            throw new Exception('Cannot obtain login token. Message: ' . $raw_response);
        }

        return $response['key'];
    }
    public function constructUrl($status = "exported", $doc_id = null) {
        $queryParams = array(
            'format' => 'json',
            'status' => $status,
        );
        if(!is_null($doc_id))
            $queryParams['id'] = $doc_id;

        return ROSSUM_API_ENDPOINT . 'queues/' . $this->queue_id . '/export?' . http_build_query($queryParams);
    }
    public function makeRequest($url, $token, $verbose = false) {
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: token ' . $token,
            ),
            CURLOPT_VERBOSE => $verbose,
        ));
        $raw_response = curl_exec($ch);
        curl_close($ch);
        return json_decode($raw_response);
    }
}