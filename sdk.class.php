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
define('AUTH_TOKEN', '9557dbc02260f70a82c982791f693eb6');
define('URL_DOMAIN', 'http://v1.api.algorithms.io');

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
    * @param string $type
    * @param string $friendly_name
    * @param string $friendly_description
    * @param string $version
    * @return string/json datasource_id_seq
    */
    public function upload( $file, $type, $friendly_name, $friendly_description, $version ){

        $url = URL_DOMAIN.'/dataset';

        $post_params['theFile'] = '@'.$file;
        $post_params['type'] = $type;
        $post_params['friendly_name'] = $friendly_name;
        $post_params['friendly_description'] = $friendly_description;
        $post_params['version'] = $version;

        $outcome = $this->curl->curlPost( AUTH_TOKEN, 'POST', $url, $post_params );

        return $outcome;
    }
    /**
    *
    * @return string json of file list
    */
    public function getFileList(){

        $url = URL_DOMAIN.'/dataset';
        $post_params = array();

        $outcome = $this->curl->curlPost( AUTH_TOKEN, 'GET', $url, $post_params );

        return $outcome;
    }
    /*
    * @param int $datasource_id_seq
    * @param string $field_user_id
    * @param string $field_item_id
    * @param string $field_preference
    * @return string json
    */
    public function mapFile( $datasource_id_seq, $field_user_id, $field_item_id, $field_preference ){

        $url = URL_DOMAIN.API_VERSION.'/class/Mapping/method/userFields';

        $post_params['authToken'] = AUTH_TOKEN;
        $post_params['datasource_id_seq'] = $datasource_id_seq;
        $post_params['field_user_id'] = $field_user_id;
        $post_params['field_item_id'] = $field_item_id;
        $post_params['field_preference'] = $field_preference;

        $outcome = $this->curl->curlPost( $url, $post_params );

        return $outcome;
    }
}




