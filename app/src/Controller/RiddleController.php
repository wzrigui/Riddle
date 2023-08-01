<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Routing\Annotation\Route;

class RiddleController extends AbstractController
{
    const TARGET_URL = 'http://95.217.79.100:1042/';

    /**
     * @param string $html
     * @return array|null
     */
    private function extractJSONFromComments(string $html): ?array
    {
        $pattern = '/<!--(.*?)JSON:\s*({.*?})/s';

        if (preg_match_all($pattern, $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $jsonString = $match[2];
                return json_decode($jsonString, true);
            }
        }
        return null;
    }

    /**
     * @param array $data
     * @return int|null
     */
    private function performTask(array $data): mixed
    {
        $result = null;

        if (isset($data['task'])) {
            $task = $data['task'];
            if (preg_match('/^ADD:\s+(\d+)\s+(\d+)$/', $task, $matches)) {
                $number1 = intval($matches[1]);
                $number2 = intval($matches[2]);
                $result = $number1 + $number2;
            }
           if (preg_match('/^XOR:\s+(\d+)\s+(\d+)$/', $task, $matches)) {
                $number1 = intval($matches[1]);
                $number2 = intval($matches[2]);
                $result = $number1 ^ $number2;
            } 
            if (preg_match('/^MD5:\s+(\d+)$/', $task, $matches)) {
                $number1 = intval($matches[1]);
                $result = md5($number1);
            } 
            if (preg_match('/CURL:\s*\/\?p=(\d+)/i', $task, $matches)) {
                $curlResponse = $this->makeRequest(self::TARGET_URL . "/?p=" . $matches[1]);
                if ($curlResponse) {
                    $jsonResponse = json_decode($curlResponse, true);
                    $result = $jsonResponse['answer'];
                }
            } 
        }
        return $result;
    }

    /**
     * @param string $url
     * @param bool $post
     * @param array $postData
     * @return string
     */
    private function makeRequest(string $url, bool $post = false, array $postData = []): string
    {
        $httpClient = HttpClient::create();
        $options = [];

        if ($post) {
            $options['body'] = $postData;
        }

        $response = $httpClient->request($post ? 'POST' : 'GET', $url, $options);

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('Error performing the request.');
        }

        return $response->getContent();
    }

    /**
     * @param string $html
     * @return string
     */
    private function analyseResponse(string $html, string $resultHtml): string
    {
        if (!$html) {
            return 'Unable to fetch the website content.';
        }

        $jsonData = $this->extractJSONFromComments($html);

        if (!$jsonData) {
            return 'The specified element was not found on the website.';
        }

        $result = $this->performTask($jsonData);

        // Output the result
        if ($result !== null) {
            $postData = [
                'answer' => $result,
                'token' => $jsonData['token'],
                'robot' => 1,
            ];
            $response = $this->makeRequest(self::TARGET_URL, true, $postData);
            $resultHtml .= $response;
            return $this->analyseResponse($response, $resultHtml);
        }

        return $resultHtml;
    }

    /**
     * @return Response
     */
    #[Route(path: '/yoomday', name: 'yoomday', methods: ['GET'])]
    public function index(): Response
    {
        $html = $this->makeRequest(self::TARGET_URL);
        $resultHtml = '';
        $result = $this->analyseResponse($html, $resultHtml);

        return $this->render('riddle/solution.html.twig', ['result' => $result]);
    }
}
