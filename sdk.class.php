<?php
/*
 * Copyright 2010-2012 Algorithms.io, Inc. or its affiliates. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License").
 * You may not use this file except in compliance with the License.
 * A copy of the License is located at
 *
 *  http://api.algorithms.io/apache2.0
 *
 * or in the "license" file accompanying this file. This file is distributed
 * on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either
 * express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 */


/**
 * CONSTANTS
 */
define('AUTH_TOKEN', '9f389c9e500dd15261268b34dbc38620');
define('URL_DOMAIN', 'http://www.algorithms.io/api/');
define('API_VERSION', 'v1');

/**
 * INCLUDES
 */
require_once 'utilities/Curl.php';

class Algorithms
{
    private $curl;

    public function __construct(){

        $this->curl = new Curl();    
    }
    /**
    *
    * @param string $file
    * @return int datasource_id_seq
    */
    public function upload( $file, $filename, $type, $friendly_name, $friendly_description, $version ){

        $url = URL_DOMAIN.API_VERSION.'/class/DataSources/method/upload';

        $post_params['theFile'] = '@'.$file;
        $post_params['authToken'] = AUTH_TOKEN;
        $post_params['type'] = $type;
        $post_params['friendly_name'] = $friendly_name;
        $post_params['friendly_description'] = $friendly_description;
        $post_params['version'] = $version;

        $outcome = $this->curl->curlPost( $url, $post_params );

        return $outcome;
    }


}



