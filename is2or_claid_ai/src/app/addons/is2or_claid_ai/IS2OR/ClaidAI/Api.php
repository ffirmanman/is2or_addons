<?php

namespace IS2OR\ClaidAI;

use Tygh\Http;
use Tygh\Registry;

class Api {
    private $addon_settings;
    private $base_url = 'https://api.claid.ai/v1';
    // private $host = 'api.claid.ai';
    private $api_key;

    public function __construct()
    {
        $this->addon_settings = Registry::get('addons.is2or_claid_ai');
        $this->api_key = $this->addon_settings['api_key'];
    }

    private function getHeaders() {
        return [
            'Host: api.claid.ai',
            'Authorization: Bearer ' . $this->api_key,
            'Content-Type: application/json',
            'Accept: */*'
        ];
    }

    private function makeRequest($path, $data = [], $method = 'POST', $full_url = false) {
        $url = $full_url ? $path : $this->base_url . $path;

        $result = false;
        try {
            if ($method == 'POST') {
                $response = Http::post($url, json_encode($data), [
                    'headers' => $this->getHeaders()
                ]);
            } else {
                $response = Http::get($url, [], [
                    'headers' => [
                        'Authorization: Bearer ' . $this->api_key,
                        'Accept: */*'
                    ]
                ]);
            }

            $result = $this->processResponse($response);
        } catch (\Exception $e) {
            // Do nothing for now
        }

        return $result;
    }

    private function processResponse($response) {
        if ($response) {
            $response = json_decode($response, true);

            if (!empty($response['data'])) {
                if (!empty($response['data']['output'])) {
                    $output = $response['data']['output'];
    
                    if (!isset($output['ext']) && !empty($output[0])) {
                        return $output[0];
                    }
    
                    return $response['data']['output'];
                } else {
                    return $response['data'];
                }
            }
        }

        return false;
    }

    public function processImage($action, $url, $prompt = '') {
        // Testing purpose 
        // $url = 'https://claid.ai/doc-samples/bag.jpeg';
        
        // for bg generation
        // $url = 'https://pngimg.com/uploads/iphone17/iphone17_PNG21.png';

        if ($action == 'remove_background') {
            return $this->removeBackground($url);
        } else if ($action == 'enhance_image') {
            return $this->enhanceImage($url);
        } else if ($action == 'generate_background') {
            return $this->generateBackground($url, $prompt);
        }

        return false;
    }

    private function removeBackground($url) {
        $data = [
            'input' => $url,
            'operations' => [
                'background' => [
                    'remove' => [
                        'category' => 'products'
                    ],
                    'color' => 'transparent'
                ]
            ],
            'output' => [
                'format' => [
                    'type' => 'png'
                ]
            ]

        ];

        return $this->makeRequest('/image/edit', $data);
    }

    private function enhanceImage($url) {
        $data = [
            'input' => $url,
            'operations' => [
                'adjustments' => [
                    'hdr' => (int) ($this->addon_settings['hdr'] ?? 60),
                    'sharpness' => (int) ($this->addon_settings['sharpness'] ?? 40)
                ]
            ],
            'output' => [
                'format' => [
                    'type' => 'jpeg',
                    'quality' => 90
                ]
            ]
        ];

        return $this->makeRequest('/image/edit', $data);
    }

    private function generateBackground($url, $prompt) {
        $data = [
            'object' => [
                'image_url' => $url,
                'placement_type' => 'absolute',
                'scale' => 0.75,
            ],
            'scene' => [
                'model' => 'v2',
                'prompt' => [
                    'generate' => true,
                    'guidelines' => $prompt
                ],
            ],
            'output' => [
                'number_of_images' => 1,
                'format' => 'jpeg'
            ]
        ];

        return $this->makeRequest('/scene/create', $data);
    }

    public function tryon($model, $clothing) {
        // Testing purpose
        // $model = "https://images.claid.ai/models/ai-fashion-model/d0ad3dafbd1d4fcfac4012ee810e7463.jpg";
        // $clothing = [
        //     "https://images.claid.ai/photoshoot-templates/assets/images/f4945a28e9874eaa89fd43313f373040.png",
        //     "https://images.claid.ai/photoshoot-templates/assets/images/b63641ea19dd4dac8fdc02a6195873f0.jpeg"
        // ];

        $data = [
            'output' => [
                'number_of_images' => 1,
                'format' => 'png'
            ],
            'input' => [
                'model' => $model,
                'clothing' => $clothing
            ],
            'options' => [
                'aspect_ratio' => '1:1'
            ]
        ];

        // Debugg
        // $response = '{"data":{"id":228654,"status":"ACCEPTED","created_at":"2025-12-04T09:04:31.347890+00:00","request":{"output":{"number_of_images":1,"format":"png"},"input":{"model":"https://images.claid.ai/models/ai-fashion-model/d0ad3dafbd1d4fcfac4012ee810e7463.jpg","clothing":["https://images.claid.ai/photoshoot-templates/assets/images/b63641ea19dd4dac8fdc02a6195873f0.jpeg"]},"options":{"aspect_ratio":"1:1"}},"result_url":"http://api.claid.ai/v1/image/ai-fashion-models/228654"}}';
        // $result = $this->processResponse($response);


        $result = $this->makeRequest('/image/ai-fashion-models', $data);

        if ($result !== false && !empty($result['id'])) {
            $url = $this->base_url .'/image/ai-fashion-models/' . $result['id'];

            // Debug
            // $response = ' {"data":{"id":228654,"status":"DONE","created_at":"2025-12-04T09:04:31.347890+00:00","request":{"input":{"model":"https://images.claid.ai/models/ai-fashion-model/d0ad3dafbd1d4fcfac4012ee810e7463.jpg","clothing":["https://images.claid.ai/photoshoot-templates/assets/images/b63641ea19dd4dac8fdc02a6195873f0.jpeg"]},"output":{"format":"png","number_of_images":1},"options":{"aspect_ratio":"1:1"}},"errors":[],"result":{"input_objects":[{"ext":"jpeg","mps":1.048576,"mime":"image/jpeg","format":"JPEG","width":1024,"height":1024},{"ext":"jpg","mps":1.022976,"mime":"image/jpeg","format":"JPEG","width":864,"height":1184}],"output_objects":[{"ext":"png","mps":1.048576,"mime":"image/png","format":"PNG","width":1024,"height":1024,"tmp_url":"https://dl.claid.ai/Claid_bcad1ef590384f6c9de6ecb814ba683b.png","object_key":null,"object_bucket":null,"object_uri":null,"claid_storage_uri":null}]}}}';
            // return $this->processResponse($response);

            sleep(5);
            $async_response = $this->makeRequest($url, [], 'GET', true);
            while ($async_response !== false && !empty($async_response['status']) && $async_response['status'] != 'DONE') {
                sleep(5);
                $async_response = $this->makeRequest($url, [], 'GET', true);
            }

            return $async_response;
        }

        return false;
    }
}