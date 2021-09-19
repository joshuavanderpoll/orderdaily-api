<?php

namespace Orderdaily;

use Exception;

/**
 * 
 * Orderdaily API for partners/developer.
 * Copyright 2020 Orderdaily.
 *
 * @package    Client
 * @author     Original Author <joshua@orderdaily.nl>
 * @version    1.1.0
 * @link       https://orderdaily.nl
 * 
 */
class Client {

    const USER_AGENT_SUFFIX = "orderdaily-api-php/";
    const MAIN_API_BASE_PATH = 'https://orderdaily.nl';
    const PARTNER_API_BASE_PATH = 'https://partner.orderdaily.nl';

    /**
     * @var array $config
     */
    private $config;

    /**
     * Construct Orderdaily API.
     * @param array $config|null
     * @return void
     */
    public function __construct(array $config = []) {
        $this->config = array_merge([
            'application_name' => '',
            'main_api_key' => '',
            'partner_api_key' => ''
        ], $config);
    }

    /**
     * Set the application name, this is included in the User-Agent HTTP header.
     * @param string $name
     * @return void
     */
    public function set_application_name($name) {
        $this->config['application_name'] = $name;
    }

    /**
     * Set API authentication key.
     * @param string $key
     * @return void
     */
    public function set_main_api_key($key) {
        $this->config['main_api_key'] = $key;
    }

    /**
     * Set API authentication key.
     * @param string $key
     * @return void
     */
    public function set_partner_api_key($key) {
        $this->config['partner_api_key'] = $key;
    }

    /**
     * Set config value.
     * @param string $name
     * @param $value
     * @return void
     */
    public function set_config(string $name, $value) {
        $this->config[$name] = $value;
    }
  
    /**
     * Get config value.
     * @param string $name
     * @return void
     * @param $default
     */
    public function get_config(string $name, $default = null) {
        return isset($this->config[$name]) ? $this->config[$name] : $default;
    }

    /**
     * Returns API headers for authentication.
     * @throws Exception
     * @return array
     */
    private function get_main_api_headers() {
        if($this->config['application_name'] == '') throw new Exception('Application is not defined.');
        if($this->config['main_api_key'] == '') throw new Exception('Main API Key is not defined.');

        return [
            "Authorization: Bearer ".$this->config['main_api_key'],
            "User-Agent: ".self::USER_AGENT_SUFFIX.$this->config['application_name'],
            "Content-Type: application/json"
        ];
    }

    /**
     * Returns API headers for authentication.
     * @throws Exception
     * @return array
     */
    private function get_partner_api_headers() {
        if($this->config['application_name'] == '') throw new Exception('Application is not defined.');
        if($this->config['partner_api_key'] == '') throw new Exception('Partner API Key is not defined.');

        return [
            "Authorization: ".$this->config['partner_api_key'],
            "User-Agent: ".self::USER_AGENT_SUFFIX.$this->config['application_name'],
            "Content-Type: application/json"
        ];
    }

    /**
     * Checks if input is JSON formatted.
     * @param string $string
     * @return bool
     */
    private function is_json($string) {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Checks if input is Base64 encoded.
     * @param string $string
     * @return bool
     */
    private function is_base64($string) {
        if(($b = base64_decode($string, TRUE)) === false) return false;
        return (in_array(mb_detect_encoding($b), array('UTF-8', 'ASCII')));
    }

    /**
     * Checks if string is UTF8
     * @param string $string
     * @return bool
     */
    private function seems_utf8(string $string) {
        $length = strlen($string);
        for ($i = 0; $i < $length; $i++) {
            $c = ord($string[$i]);
            if ($c < 0x80) { $n = 0;
            } elseif (($c & 0xE0) == 0xC0) { $n = 1;
            } elseif (($c & 0xF0) == 0xE0) { $n = 2;
            } elseif (($c & 0xF8) == 0xF0) { $n = 3;
            } elseif (($c & 0xFC) == 0xF8) { $n = 4;
            } elseif (($c & 0xFE) == 0xFC) { $n = 5;
            } else { return false;
            }
            for ($j = 0; $j < $n; $j++) {
                if ((++$i == $length) || ((ord($string[$i]) & 0xC0) != 0x80)) return false;
            }
        }
        return true;
    }

    /**
     * URI encoded UTF8 string
     * @param string $utf8_string
     * @param int|0 $length
     * @return bool
     */
    private function utf8_uri_encode(string $utf8_string, int $length = 0) {
        $unicode = ''; $values = array(); $num_octets = 1; $unicode_length = 0; $string_length = strlen($utf8_string);
        for ($i = 0; $i < $string_length; $i++) {
            $value = ord($utf8_string[$i]);
            if ($value < 128) {
                if ($length && ($unicode_length >= $length)) break;
                $unicode .= chr($value);
                $unicode_length++;
            } else {
                if (count($values) == 0) {
                    if ($value < 224) {
                        $num_octets = 2;
                    } elseif ($value < 240) {
                        $num_octets = 3;
                    } else {
                        $num_octets = 4;
                    }
                }
                $values[] = $value;
                if ($length && ($unicode_length + ($num_octets * 3)) > $length) break;
                
                if (count($values) == $num_octets) {
                    for ($j = 0; $j < $num_octets; $j++) {
                        $unicode .= '%' . dechex($values[$j]);
                    }
                    $unicode_length += $num_octets * 3;
                    $values = array();
                    $num_octets = 1;
                }
            }
        }
        return $unicode;
    }

    /**
     * Generate random numbers
     * @param int|5 $length
     * @return int
     */
    function generate_numbers(int $length = 5) {
        $min = (10 ** $length) / 10;
        $max = (10 ** $length) - 1;
        $random = mt_rand($min, $max);
        return $random;
    }

    /**
     * Converts string to URI friendly string
     * @param string $string
     * @return string
     */
    private function slugify($string) {
        $string = strip_tags($string);
        $string = preg_replace('|%([a-fA-F0-9][a-fA-F0-9])|', '---$1---', $string);
        $string = str_replace('%', '', $string);
        $string = preg_replace('|---([a-fA-F0-9][a-fA-F0-9])---|', '%$1', $string);
     
        if ($this->seems_utf8($string)) {
            if (function_exists('mb_strtolower')) $string = mb_strtolower($string, 'UTF-8');
            $string = $this->utf8_uri_encode($string, 200);
        }
     
        $string = strtolower($string);
     
        if ('save' === "display") {
            $string = str_replace(array('%c2%a0', '%e2%80%93', '%e2%80%94'), '-', $string);
            $string = str_replace(array('&nbsp;', '&#160;', '&ndash;', '&#8211;', '&mdash;', '&#8212;'), '-', $string);
            $string = str_replace('/', '-', $string);
            $string = str_replace(['%c2%ad', '%c2%a1', '%c2%bf', '%c2%ab', '%c2%bb', '%e2%80%b9', '%e2%80%ba', '%e2%80%98', '%e2%80%99', '%e2%80%9c', '%e2%80%9d', '%e2%80%9a', '%e2%80%9b', '%e2%80%9e', '%e2%80%9f', '%e2%80%a2', '%c2%a9', '%c2%ae', '%c2%b0', '%e2%80%a6', '%e2%84%a2', '%c2%b4', '%cb%8a', '%cc%81', '%cd%81', '%cc%80', '%cc%84', '%cc%8c'], '', $string);
            $string = str_replace('%c3%97', 'x', $string);
        }
     
        $string = preg_replace('/&.+?;/', '', $string);
        $string = str_replace('.', '-', $string);
        $string = preg_replace('/[^%a-z0-9 _-]/', '', $string);
        $string = preg_replace('/\s+/', '-', $string);
        $string = preg_replace('|-+|', '-', $string);
        $string = trim($string, '-');
        $string = str_replace('%', '', $string);
     
        return $string;
    }

    /**
     * Returns API formatted response.
     * @param string $url
     * @param array $headers|null Example: [Content-Type: application/json]
     * @param string $method|GET https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods
     * @return array
     */
    private function api_get(string $url, array $headers = [], string $method = "GET") {
        $ch = curl_init(); // CURL request
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if(!$this->is_json($response)) return ["error" => true, "value" => 'Invalid JSON response.']; // Validate JSON
        $response = json_decode($response, true); // Convert to array

        switch ($code) {
            case '400':
                return ["error" => true, "value" => "API endpoint not found."];
            case '404':
                return ["error" => true, "value" => "Item not found."];
            case '403':
                return ["error" => true, "value" => "API key invalid. Please check if your configuration is correct."];
            case '500':
                sleep(1);
                return $this->api_get($url, $headers, $method);
            case '200':
                return ["error" => false, "value" => $response];
            default:
                return ["error" => true, "value" => "Unknown error. Code: $code"];
        }
    }

    /**
     * Returns API formatted JSON POST request response.
     * @param string $url
     * @param array $data
     * @param array $headers|null Example: [Content-Type: application/json]
     * @param string $method|GET https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods
     * @return array
     */
    private function api_post_json(string $url, array $data, array $headers = [], string $method = "POST") {
        $ch = curl_init(); // CURL request
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        print_r($response);

        if(!$this->is_json($response)) return ["error" => true, "value" => 'Invalid JSON response.']; // Validate JSON
        $response = json_decode($response, true); // Convert to array

        switch ($code) {
            case '400':
                return ["error" => true, "value" => "API endpoint not found."];
            case '404':
                return ["error" => true, "value" => "Item not found."];
            case '403':
                return ["error" => true, "value" => "API key invalid. Please check if your configuration is correct."];
            case '422':
                return ["error" => true, "value" => "Data can't be processed."];
            case '500':
                sleep(1);
                return $this->api_post_json($url, $data, $headers, $method);
            case '200':
                return ["error" => false, "value" => $response];
            case '201':
                return ["error" => false, "value" => $response];
            default:
                return ["error" => true, "value" => "Unknown error. Code: $code"];
        }
    }

    /**
     * Check if image exists on web.
     * @param string $url
     * @return bool
     */
    function image_exists(string $url) {
        $ch = curl_init(); // CURL request
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_NOBODY, 1);
        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = (curl_exec($ch) !== FALSE);
        curl_close($ch);

        return $result;
    }

    /**
     * Returns all shops. [Admin]
     * @param int $per_page|10
     * @param int $page|1
     * @return array
     */
    public function get_shops(int $per_page = 10, int $page = 1) {
        return $this->api_get(self::MAIN_API_BASE_PATH."/api/v1/shops?per_page=$per_page&page=$page", $this->get_main_api_headers());
    }

    /**
     * Returns all orders. [Admin]
     * @param string $order_by|DESC Values: [DESC/ASC]
     * @param string $status|null Values: [pending/paid/shipped/completed/returned/canceled]
     * @param int $per_page|10
     * @param int $page|1
     * @return array
     */
    public function get_orders(string $order_by = "DESC", string $status = null, int $per_page = 10, int $page = 1) {
        $sort_params = ["asc","desc"];
        $status_params = ["pending","paid","shipped","completed","returned","canceled"];

        if(!in_array(strtolower($order_by), $sort_params)) return ["error" => true, "value" => "Invalid order_by parameter."];
        if($status !== null) if(!in_array(strtolower($status), $status_params)) return ["error" => true, "value" => "Invalid status parameter."];
        $status_param = ($status == null) ? null : "&status=$status";
        return $this->api_get(self::MAIN_API_BASE_PATH."/api/v1/orders?per_page=$per_page&page=$page&order_by=$order_by".$status_param, $this->get_main_api_headers());
    }

    /**
     * Returns shop information. [Shop Manager]
     * @param int $shop_id
     * @return array
     */
    public function get_shop(int $shop_id) {
        return $this->api_get(self::MAIN_API_BASE_PATH."/api/v1/shops/$shop_id", $this->get_main_api_headers());
    }

    /**
     * Returns certain shop orders. [Shop Manager]
     * @param int $shop_id
     * @param int $per_page|10
     * @param int $page|1
     * @return array
     */
    public function get_shop_orders(int $shop_id, int $per_page = 10, int $page = 1) {
        return $this->api_get(self::MAIN_API_BASE_PATH."/api/v1/shops/$shop_id/orders?per_page=$per_page&page=$page", $this->get_main_api_headers());
    }

    /**
     * Returns certain shop products. [Shop Manager]
     * @param int $shop_id
     * @param int $per_page|10
     * @param int $page|1
     * @return array
     */
    public function get_shop_products(int $shop_id, int $per_page = 10, int $page = 1) {
        return $this->api_get(self::MAIN_API_BASE_PATH."/api/v1/shops/$shop_id/products?per_page=$per_page&page=$page", $this->get_main_api_headers());
    }

    /**
     * Returns all categories. [Admin]
     * @param int $per_page|10
     * @param int $page|1
     * @return array
     */
    public function get_categories(int $per_page = 10, int $page = 1) {
        return $this->api_get(self::MAIN_API_BASE_PATH."/api/v1/categories?per_page=$per_page&page=$page", $this->get_main_api_headers());
    }

    /**
     * Returns certain category. [Admin]
     * @param int $category_id
     * @param int $per_page|10
     * @param int $page|1
     * @return array
     */
    public function get_category(int $category_id) {
        return $this->api_get(self::MAIN_API_BASE_PATH."/api/v1/categories/$category_id", $this->get_main_api_headers());
    }

    /**
     * Returns all attributes. [Admin]
     * @param int $per_page|10
     * @param int $page|1
     * @return array
     */
    public function get_attributes(int $per_page = 10, int $page = 1) {
        return $this->api_get(self::MAIN_API_BASE_PATH."/api/v1/attributes?per_page=$per_page&page=$page", $this->get_main_api_headers());
    }

    /**
     * Returns certain attribute. [Admin]
     * @param int $attribute_id
     * @param int $per_page|10
     * @param int $page|1
     * @return array
     */
    public function get_attribute(int $attribute_id) {
        return $this->api_get(self::MAIN_API_BASE_PATH."/api/v1/attributes/$attribute_id", $this->get_main_api_headers());
    }

    /**
     * Creates a attribute. [Admin]
     * @param string $name
     * @param string $description
     * @return array
     */
    public function create_attribute(string $name, string $description) {
        $data = [
            "name" => $name,
            "slug" => $this->slugify($name)."-".$this->generate_numbers(),
            "description" => $description
        ];

        return $this->api_post_json(self::MAIN_API_BASE_PATH."/api/v1/attributes", $data, $this->get_main_api_headers(), "POST");
    }


    /**
     * Deletes a attribute. [Admin]
     * @param int $attribute_id
     * @return array
     */
    public function delete_attribute(int $attribute_id) {
        return $this->api_post_json(self::MAIN_API_BASE_PATH."/api/v1/attributes/$attribute_id", [], $this->get_main_api_headers(), "DELETE");
    }

    /**
     * Returns all attributes values. [Admin]
     * @param int $attribute_id
     * @param int $per_page|10
     * @param int $page|1
     * @return array
     */
    public function get_attribute_values(int $attribute_id, int $per_page = 10, int $page = 1) {
        return $this->api_get(self::MAIN_API_BASE_PATH."/api/v1/attributes/$attribute_id/attribute_values?per_page=$per_page&page=$page", $this->get_main_api_headers());
    }

    /**
     * Returns certain attribute value. [Admin]
     * @param int $attribute_id
     * @param int $attribute_value_id
     * @param int $per_page|10
     * @param int $page|1
     * @return array
     */
    public function get_attribute_value(int $attribute_id, int $attribute_value_id) {
        return $this->api_get(self::MAIN_API_BASE_PATH."/api/v1/attributes/$attribute_id/attribute_values/$attribute_value_id", $this->get_main_api_headers());
    }

    /**
     * Creates a attribute value. [Admin]
     * @param int $attribute_id
     * @param string $name
     * @return array
     */
    public function create_attribute_value(int $attribute_id, string $name) {
        $data = [
            "value" => $name
        ];

        return $this->api_post_json(self::MAIN_API_BASE_PATH."/api/v1/attributes/$attribute_id/attribute_values", $data, $this->get_main_api_headers(), "POST");
    }

    /**
     * Deletes a attribute value. [Admin]
     * @param int $attribute_id
     * @param int $attribute_value_id
     * @return array
     */
    public function delete_attribute_value(int $attribute_id, int $attribute_value_id) {
        return $this->api_post_json(self::MAIN_API_BASE_PATH."/api/v1/attributes/$attribute_id/attribute_values/$attribute_value_id", [], $this->get_main_api_headers(), "DELETE");
    }

    /**
     * Returns all products. [Admin]
     * @param int $per_page|10
     * @param int $page|1
     * @return array
     */
    public function get_products(int $per_page = 10, int $page = 1) {
        return $this->api_get(self::MAIN_API_BASE_PATH."/api/v1/products?per_page=$per_page&page=$page", $this->get_main_api_headers());
    }

    /**
     * Returns certain product. [Admin]
     * @param int $product_id
     * @return array
     */
    public function get_product(int $product_id) {
        return $this->api_get(self::MAIN_API_BASE_PATH."/api/v1/products/$product_id", $this->get_main_api_headers());
    }

    /**
     * Creates a product. [Admin]
     * @param string $name
     * @param int $shop_id
     * @param string $status
     * @param string $description
     * @param float $price
     * @param bool $is_large
     * @param int $weight
     * @param string $sku
     * @param bool $manage_stock
     * @param int $stock_value
     * @param string $vat_type
     * @param string $seo_title
     * @param string $seo_description
     * @param array $images
     * @param array $category_ids
     * @param array $variations
     * @return array
     */
    public function create_product(string $name, int $shop_id, string $status, string $description, float $price, bool $is_large, int $weight, string $sku, bool $manage_stock, int $stock_value, string $vat_type, string $seo_title, string $seo_description, array $images, array $category_ids, array $variations) {
        $status_params = ['draft', 'available', 'archived'];
        $vat_params = ['high', 'low'];
        if($status !== null) if(!in_array(strtolower($status), $status_params)) return ["error" => true, "value" => "Invalid status parameter."];
        if($vat_type !== null) if(!in_array(strtolower($vat_type), $vat_params)) return ["error" => true, "value" => "Invalid VAT parameter."];

        // Validate images
        $filtered_images = [];
        foreach($images as $image) {
            if(!is_string($image)) continue;
            if(filter_var(filter_var($image, FILTER_SANITIZE_URL), FILTER_VALIDATE_URL) !== false) {
                if($this->image_exists($image)) {
                    $size = get_headers($image, 1)["Content-Length"];
                    if($size <= 10485760) $filtered_images[] = $image;
                }
            } elseif($this->is_base64($image)) {
                $size = strlen(base64_decode($image));
                if($size <= 10485760) $filtered_images[] = $image;
            }
        }

        // Validate categories
        $filtered_categories = [];
        foreach($category_ids as $category) if(is_int($category)) $filtered_categories[] = $category;

        // Validate variations
        $filtered_variations = [];
        foreach($variations as $variation) {
            if(array_key_exists('attribute_value_id', $variation) && array_key_exists('manage_stock', $variation) && array_key_exists('stock', $variation)) {
                if(!is_int($variation['attribute_value_id'])) continue;
                if(!is_bool($variation['manage_stock'])) continue;
                if(!is_int($variation['stock'])) continue;

                $filtered_variations[] = [
                    'attribute_value_id' => $variation['attribute_value_id'],
                    'manage_stock' => $variation['manage_stock'],
                    'stock' => $variation['stock']
                ];
            }
        }

        $data = [
            "name" => $name,
            "slug" => $this->slugify($name)."-".$this->generate_numbers(),
            "shop_id" => $shop_id,
            "status" => $status,
            "content" => $description,
            "price" => $price,
            "large" => $is_large,
            "weight" => $weight,
            "sku" => $sku,
            "manage_stock" => $manage_stock,
            "stock" => $stock_value,
            "vat_type" => $vat_type,
            "seo_title" => $seo_title,
            "seo_description" => $seo_description,
            "images" => $filtered_images,
            "categories" => $filtered_categories,
            "variations" => $filtered_variations
        ];

        return $this->api_post_json(self::MAIN_API_BASE_PATH."/api/v1/products", $data, $this->get_main_api_headers(), "POST");
    }

    /**
     * Updates a product. [Admin]
     * @param int $product_id
     * @param string $name|null
     * @param int $shop_id|null
     * @param string $status|null
     * @param string $description|null
     * @param float $price|null
     * @param bool $is_large|null
     * @param int $weight|null
     * @param string $sku|null
     * @param bool $manage_stock|null
     * @param int $stock_value|null
     * @param string $vat_type|null
     * @param string $seo_title|null
     * @param string $seo_description|null
     * @param array $images|null
     * @param array $category_ids|null
     * @param array $variations|null
     * @return array
     */
    public function update_product(int $product_id, string $name = null, int $shop_id = null, string $status = null, string $description = null, float $price = null, bool $is_large = null, int $weight = null, string $sku = null, bool $manage_stock = null, int $stock_value = null, string $vat_type = null, string $seo_title = null, string $seo_description = null, array $images = null, array $category_ids = null, array $variations = null) {
        $status_params = ['draft', 'available', 'archived'];
        $vat_params = ['high', 'low'];
        if($status !== null) if(!in_array(strtolower($status), $status_params)) return ["error" => true, "value" => "Invalid status parameter."];
        if($vat_type !== null) if(!in_array(strtolower($vat_type), $vat_params)) return ["error" => true, "value" => "Invalid VAT parameter."];

        // Validate images
        $filtered_images = [];
        foreach($images as $image) {
            if(filter_var(filter_var($image, FILTER_SANITIZE_URL), FILTER_VALIDATE_URL) !== false) {
                if($this->image_exists($image)) {
                    $size = get_headers($image, 1)["Content-Length"];
                    if($size <= 10485760) $filtered_images[] = $image;
                }
            } elseif($this->is_base64($image)) {
                $size = strlen(base64_decode($image));
                if($size <= 10485760) $filtered_images[] = $image;
            }
        }

        // Validate categories
        $filtered_categories = [];
        foreach($category_ids as $category) if(is_int($category)) $filtered_categories[] = $category;

        // Validate variations
        $filtered_variations = [];
        foreach($variations as $variation) {
            if(array_key_exists('attribute_value_id', $variation) && array_key_exists('manage_stock', $variation) && array_key_exists('stock', $variation)) {
                if(!is_int($variation['attribute_value_id'])) continue;
                if(!is_bool($variation['manage_stock'])) continue;
                if(!is_int($variation['stock'])) continue;

                $filtered_variations[] = [
                    'attribute_value_id' => $variation['attribute_value_id'],
                    'manage_stock' => $variation['manage_stock'],
                    'stock' => $variation['stock']
                ];
            }
        }

        $data = [];
        if($name !== null) $data["name"] = $name;
        if($name !== null) $data["slug"] = $this->slugify($name)."-".$this->generate_numbers();
        if($shop_id !== null) $data["shop_id"] = $shop_id;
        if($status !== null) $data["status"] = $status;
        if($description !== null) $data["content"] = $description;
        if($price !== null) $data["price"] = $price;
        if($is_large !== null) $data["large"] = $is_large;
        if($weight !== null) $data["weight"] = $weight;
        if($sku !== null) $data["sku"] = $sku;
        if($manage_stock !== null) $data["manage_stock"] = $manage_stock;
        if($stock_value !== null) $data["stock"] = $stock_value;
        if($vat_type !== null) $data["vat_type"] = $vat_type;
        if($seo_title !== null) $data["seo_title"] = $seo_title;
        if($seo_description !== null) $data["seo_description"] = $seo_description;
        if($images !== null) $data["images"] = $filtered_images;
        if($category_ids !== null) $data["categories"] = $filtered_categories;
        if($variations !== null) $data["variations"] = $filtered_variations;

        return $this->api_post_json(self::MAIN_API_BASE_PATH."/api/v1/products/$product_id", $data, $this->get_main_api_headers(), "PUT");
    }

    /**
     * Deletes a product. [Admin]
     * @param int $product_id
     * @return array
     */
    public function delete_product(int $product_id) {
        return $this->api_post_json(self::MAIN_API_BASE_PATH."/api/v1/products/$product_id", [], $this->get_main_api_headers(), "DELETE");
    }

    /**
     * Creates a new order. [Per Shop]
     * @param string $firstname
     * @param string $lastname
     * @param string $street
     * @param string $house_number
     * @param string $postal_code
     * @param string $city
     * @param string $note|null
     */
    public function create_order(string $firstname, string $lastname, string $street, string $house_number, string $postal_code, string $city, string $note = null) {
        $data = [
            "firstname" => $firstname,
            "lastname" => $lastname,
            "street" => $street,
            "house_number" => $house_number,
            "postal_code" => $postal_code,
            "city" => $city,
            "note" => $note
        ];

        return $this->api_post_json(self::PARTNER_API_BASE_PATH."/api/orders/create", $data, $this->get_partner_api_headers(), "POST");
    }
}
