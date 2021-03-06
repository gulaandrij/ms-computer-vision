<?php

namespace MSComputerVisionBundle\Service;

use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Class ComputerVisionClient
 *
 * @package MSComputerVisionBundle\Service
 */
class ComputerVisionClient
{
    public const API_URL = 'https://westcentralus.api.cognitive.microsoft.com/vision/v2.0/ocr';

    /**
     *
     * @var string|string
     */
    private $apiKey;
    /**
     * @var Client
     */
    private $client;

    /**
     * ComputerVisionClient constructor.
     *
     * @param string $apiKey
     */
    public function __construct(string $apiKey)
    {

        $this->client = new Client();
        $this->apiKey = $apiKey;
    }

    /**
     * Main, recursive processing function
     *
     * @param string $source
     * @param string $language
     * @param string $detectOrientation
     *
     * @return array
     */
    public function process($source, $language, $detectOrientation = 'false')
    {
        if (is_file($source)) {
            $this->results[] = $this->processImage($source, $language, $detectOrientation);
        }
//        if (is_dir($source)) {
//            $scanned_directory = array_diff(scandir($source), ['..', '.']);
//            foreach ($scanned_directory as $found) {
//                $found = sprintf('%s/%s', $source, $found);
//                if (is_file($found)) {
//                    $this->results[] = $this->processImage($found, $language, $detectOrientation);
//                }
//                if (is_dir($found)) {
//                    array_merge($this->results, $this->process($found, $language, $detectOrientation));
//                }
//            }
//        }
        $this->results = array_filter(
            $this->results,
            function ($var) {
                return $var !== null;
            }
        );
        return $this->results;
    }

    /**
     * Image specific processing
     * @param string $source
     * @param string $language
     * @param string $detectOrientation
     * @return array|null
     * @throws \HttpException
     * @link https://westus.dev.cognitive.microsoft.com/docs/services/56f91f2d778daf23d8ec6739/operations/56f91f2e778daf14a499e1fc
     */
    public function processImage($source, $language, $detectOrientation)
    {
        $file = new File($source);

        $texts = [];
        $xml = new \SimpleXMLElement('<root/>');
        $res = $this->client->request('POST', self::API_URL, [
            'headers' => [
                'Ocp-Apim-Subscription-Key' => $this->apiKey,
                'Content-type' => 'application/octet-stream'
            ],
            'query' => [
                'language' => $language,
                'detectOrientation ' => $detectOrientation
            ],
            'body' => fopen($file->getPathname(), 'r')
        ]);
        if (200 !== $res->getStatusCode()) {
            throw new \HttpException(sprintf('Error: bad request or server issue: %s', $res->getBody()));
        }

        dd(json_decode($res->getBody(), true));
        // Json2XML, then xpath, to quick-retrieve all texts:
//        XMLHelper::arrayToXml(json_decode($res->getBody(), true), $xml);
//        foreach ($xml->xpath('//text') as $xml) {
//            $texts[] = (string)$xml;
//        }
        return ['source' => $source, 'text' => implode(' ', $texts)];
    }


}
